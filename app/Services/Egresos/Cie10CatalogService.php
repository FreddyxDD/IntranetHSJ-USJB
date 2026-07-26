<?php

namespace App\Services\Egresos;

use App\Models\Egresos\Cie10;
use App\Models\Egresos\Cie10Importacion;
use App\Models\Egresos\Cie10ImportacionFila;
use App\Support\Cie10Code;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class Cie10CatalogService
{
    private const STATES = ['ACTIVO', 'INACTIVO'];

    private const SEXES = ['AMBOS', 'HOMBRE', 'MUJER'];

    public function __construct(private readonly Cie10Trace $trace) {}

    public function create(array $data, array $actor, Request $request): Cie10
    {
        $prepared = $this->validatedData($data);
        $prepared['source_system'] = 'intranet_hsj_manual';
        $prepared['source_id'] = null;
        $prepared['source_fingerprint'] = $this->fingerprint($prepared);
        $prepared['imported_at'] = now();

        return DB::transaction(function () use ($prepared, $actor, $request): Cie10 {
            $this->lockWrites();
            if (Cie10::query()->where('codigo_normalizado', $prepared['codigo_normalizado'])->exists()) {
                throw ValidationException::withMessages([
                    'codigo' => 'El código CIE-10 ya existe en el catálogo central.',
                ]);
            }

            $cie10 = Cie10::query()->create($prepared);
            $this->trace->record(
                'created',
                'create',
                (string) $cie10->id,
                null,
                $this->snapshot($cie10),
                $actor,
                $request
            );

            return $cie10;
        });
    }

    public function update(
        Cie10 $cie10,
        array $data,
        string $version,
        array $actor,
        Request $request
    ): Cie10 {
        return DB::transaction(function () use ($cie10, $data, $version, $actor, $request): Cie10 {
            $this->lockWrites();
            $locked = Cie10::query()->lockForUpdate()->findOrFail($cie10->id);
            $this->assertVersion($locked, $version);
            $before = $this->snapshot($locked);
            $prepared = $this->validatedData($data, $locked->codigo);
            $prepared['source_system'] = 'intranet_hsj_manual';
            $prepared['source_fingerprint'] = $this->fingerprint($prepared);
            $prepared['source_updated_at'] = now();
            $locked->update($prepared);
            $locked->refresh();
            $this->trace->record(
                'updated',
                'update',
                (string) $locked->id,
                $before,
                $this->snapshot($locked),
                $actor,
                $request
            );

            return $locked;
        });
    }

    public function deactivate(
        Cie10 $cie10,
        string $version,
        array $actor,
        Request $request
    ): Cie10 {
        return DB::transaction(function () use ($cie10, $version, $actor, $request): Cie10 {
            $this->lockWrites();
            $locked = Cie10::query()->lockForUpdate()->findOrFail($cie10->id);
            $this->assertVersion($locked, $version);
            $before = $this->snapshot($locked);
            $data = [
                'codigo' => $locked->codigo,
                'codigo_normalizado' => $locked->codigo_normalizado,
                'descripcion' => $locked->descripcion,
                'estado' => 'INACTIVO',
                'cotejo_sexo' => $locked->cotejo_sexo ?: 'AMBOS',
            ];
            $locked->update([
                'estado' => 'INACTIVO',
                'source_system' => 'intranet_hsj_manual',
                'source_fingerprint' => $this->fingerprint($data),
                'source_updated_at' => now(),
            ]);
            $locked->refresh();
            $this->trace->record(
                'deactivated',
                'deactivate',
                (string) $locked->id,
                $before,
                $this->snapshot($locked),
                $actor,
                $request,
                ['physical_delete' => false]
            );

            return $locked;
        });
    }

    public function previewImport(
        UploadedFile $file,
        array $actor,
        Request $request
    ): Cie10Importacion {
        $hash = hash_file('sha256', $file->getRealPath());
        $previous = Cie10Importacion::query()
            ->where('file_sha256', $hash)
            ->whereIn('estado', ['analizado', 'confirmado'])
            ->latest('id')
            ->first();

        if ($previous) {
            throw ValidationException::withMessages([
                'archivo' => "Este archivo ya fue procesado en el lote CIE-10 #{$previous->id}.",
            ]);
        }

        [$headers, $rows] = $this->read($file);
        $map = $this->headerMap($headers);
        foreach (['codigo', 'descripcion'] as $required) {
            if (! array_key_exists($required, $map)) {
                throw ValidationException::withMessages([
                    'archivo' => "Falta la columna obligatoria {$required}.",
                ]);
            }
        }

        $existing = Cie10::query()->get()->keyBy('codigo_normalizado');
        $seen = collect();
        $staged = collect($rows)
            ->map(fn (array $row, int $offset): array => $this->stageRow(
                $row,
                $offset + 2,
                $map,
                $existing,
                $seen
            ))
            ->reject(fn (array $row): bool => $row['empty'])
            ->values();
        $counts = $staged->countBy('estado');

        return DB::transaction(function () use (
            $file,
            $hash,
            $actor,
            $request,
            $staged,
            $counts
        ): Cie10Importacion {
            $batch = Cie10Importacion::query()->create([
                'archivo' => mb_substr($file->getClientOriginalName(), 0, 255),
                'file_sha256' => $hash,
                'estado' => 'analizado',
                'actor_account_id' => $actor['account_id'],
                'actor_username' => $actor['username'],
                'actor_display_name' => $actor['display_name'],
                'nuevos' => (int) ($counts['nuevo'] ?? 0),
                'actualizaciones' => (int) ($counts['actualizar'] ?? 0),
                'sin_cambios' => (int) ($counts['sin_cambios'] ?? 0),
                'errores' => (int) ($counts['error'] ?? 0),
            ]);
            // SQL Server admite como máximo 2,100 parámetros por sentencia.
            $staged->chunk(150)->each(function (Collection $chunk) use ($batch): void {
                Cie10ImportacionFila::query()->insert($chunk->map(function (array $row) use ($batch): array {
                    unset($row['empty']);
                    $row['importacion_id'] = $batch->id;

                    return $row;
                })->all());
            });
            $this->trace->record(
                'import_analyzed',
                'preview_import',
                (string) $batch->id,
                null,
                $this->batchSummary($batch),
                $actor,
                $request,
                ['file_sha256' => $hash]
            );

            return $batch;
        });
    }

    public function confirmImport(
        Cie10Importacion $batch,
        array $actor,
        Request $request
    ): Cie10Importacion {
        return DB::transaction(function () use ($batch, $actor, $request): Cie10Importacion {
            $this->lockWrites();
            $lockedBatch = Cie10Importacion::query()->lockForUpdate()->findOrFail($batch->id);
            if ($lockedBatch->estado !== 'analizado') {
                throw ValidationException::withMessages([
                    'importacion' => 'Este lote ya fue confirmado o dejó de estar disponible.',
                ]);
            }
            if ($lockedBatch->errores > 0) {
                throw ValidationException::withMessages([
                    'importacion' => 'Corrija el archivo: los lotes con errores no pueden confirmarse.',
                ]);
            }

            $inserted = 0;
            $updated = 0;
            $conflicts = 0;
            Cie10ImportacionFila::query()
                ->where('importacion_id', $lockedBatch->id)
                ->whereIn('estado', ['nuevo', 'actualizar'])
                ->orderBy('id')
                ->chunkById(300, function ($rows) use (&$inserted, &$updated, &$conflicts): void {
                    foreach ($rows as $row) {
                        $data = $row->datos;
                        $current = Cie10::query()
                            ->where('codigo_normalizado', $data['codigo_normalizado'])
                            ->lockForUpdate()
                            ->first();

                        if ($row->estado === 'nuevo') {
                            if ($current) {
                                $row->update([
                                    'estado' => 'error',
                                    'mensajes' => [['severity' => 'error', 'message' => 'El código fue creado después del análisis.']],
                                ]);
                                $conflicts++;

                                continue;
                            }
                            $created = Cie10::query()->create([
                                ...$data,
                                'source_system' => 'intranet_hsj_cie10_import',
                                'source_id' => null,
                                'source_fingerprint' => $this->fingerprint($data),
                                'imported_at' => now(),
                            ]);
                            $row->update(['estado' => 'insertado', 'cie10_id' => $created->id]);
                            $inserted++;

                            continue;
                        }

                        $expectedVersion = $row->datos_anteriores['source_fingerprint'] ?? null;
                        if (! $current || ! hash_equals((string) $expectedVersion, (string) $current->source_fingerprint)) {
                            $row->update([
                                'estado' => 'error',
                                'mensajes' => [['severity' => 'error', 'message' => 'El registro cambió después del análisis.']],
                            ]);
                            $conflicts++;

                            continue;
                        }
                        $current->update([
                            ...$data,
                            'source_system' => 'intranet_hsj_cie10_import',
                            'source_fingerprint' => $this->fingerprint($data),
                            'source_updated_at' => now(),
                        ]);
                        $row->update(['estado' => 'actualizado', 'cie10_id' => $current->id]);
                        $updated++;
                    }
                });

            if ($conflicts > 0) {
                throw ValidationException::withMessages([
                    'importacion' => "Se detectaron {$conflicts} cambios concurrentes. Vuelva a analizar el archivo.",
                ]);
            }

            $lockedBatch->update([
                'estado' => 'confirmado',
                'confirmed_at' => now(),
                'nuevos' => $inserted,
                'actualizaciones' => $updated,
            ]);
            $lockedBatch->refresh();
            $this->trace->record(
                'import_confirmed',
                'confirm_import',
                (string) $lockedBatch->id,
                null,
                $this->batchSummary($lockedBatch),
                $actor,
                $request,
                ['file_sha256' => $lockedBatch->file_sha256]
            );

            return $lockedBatch;
        });
    }

    private function stageRow(
        array $row,
        int $line,
        array $map,
        Collection $existing,
        Collection $seen
    ): array {
        $rawCode = trim((string) ($row[$map['codigo']] ?? ''));
        $description = trim((string) ($row[$map['descripcion']] ?? ''));
        $empty = $rawCode === '' && $description === '';
        $code = Cie10Code::format($rawCode);
        $normalized = Cie10Code::normalize($code);
        $current = $existing->get($normalized);
        $state = mb_strtoupper(trim((string) ($map['estado'] ?? null) !== ''
            ? ($row[$map['estado']] ?? '')
            : '')) ?: ($current?->estado ?: 'ACTIVO');
        $sex = mb_strtoupper(trim((string) ($map['cotejo_sexo'] ?? null) !== ''
            ? ($row[$map['cotejo_sexo']] ?? '')
            : '')) ?: ($current?->cotejo_sexo ?: 'AMBOS');
        $messages = [];

        if (! $empty && ! Cie10Code::isValid($code)) {
            $messages[] = ['severity' => 'error', 'message' => 'El código no tiene un formato CIE-10 válido.'];
        }
        if (! $empty && $description === '') {
            $messages[] = ['severity' => 'error', 'message' => 'La descripción es obligatoria.'];
        }
        if (! in_array($state, self::STATES, true)) {
            $messages[] = ['severity' => 'error', 'message' => 'Estado permitido: ACTIVO o INACTIVO.'];
        }
        if (! in_array($sex, self::SEXES, true)) {
            $messages[] = ['severity' => 'error', 'message' => 'Cotejo de sexo permitido: AMBOS, HOMBRE o MUJER.'];
        }
        if ($normalized !== '' && $seen->has($normalized)) {
            $messages[] = ['severity' => 'error', 'message' => "Código repetido en la fila {$seen->get($normalized)}."];
        }
        if ($normalized !== '') {
            $seen->put($normalized, $line);
        }

        $data = [
            'codigo' => $code,
            'codigo_normalizado' => $normalized,
            'descripcion' => $description,
            'estado' => $state,
            'cotejo_sexo' => $sex,
        ];
        $same = $current && collect($data)->every(
            fn ($value, $key): bool => trim((string) $current->{$key}) === trim((string) $value)
        );
        $status = $messages ? 'error' : (! $current ? 'nuevo' : ($same ? 'sin_cambios' : 'actualizar'));

        return [
            'fila' => $line,
            'estado' => $status,
            'cie10_id' => $current?->id,
            'codigo' => $code ?: null,
            'codigo_normalizado' => $normalized ?: null,
            'datos' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'datos_anteriores' => $current
                ? json_encode($this->snapshot($current), JSON_UNESCAPED_UNICODE)
                : null,
            'mensajes' => $messages ? json_encode($messages, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
            'updated_at' => now(),
            'empty' => $empty,
        ];
    }

    private function read(UploadedFile $file): array
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());
        if ($extension === 'csv') {
            $raw = file_get_contents($file->getRealPath());
            $text = mb_check_encoding($raw, 'UTF-8')
                ? $raw
                : mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
            $firstLine = strtok($text, "\r\n") ?: '';
            $delimiters = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
            arsort($delimiters);
            $delimiter = (string) array_key_first($delimiters);
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $text);
            rewind($stream);
            $rows = [];
            while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                $rows[] = $row;
            }
            fclose($stream);
        } else {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        }

        if (count($rows) < 2) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no contiene filas de datos.']);
        }

        return [array_shift($rows), $rows];
    }

    private function headerMap(array $headers): array
    {
        $aliases = [
            'codigo' => 'codigo', 'code' => 'codigo', 'cie10' => 'codigo', 'codigo_cie10' => 'codigo',
            'descripcion' => 'descripcion', 'description' => 'descripcion', 'diagnostico' => 'descripcion',
            'estado' => 'estado', 'status' => 'estado',
            'cotejo_sexo' => 'cotejo_sexo', 'sexo' => 'cotejo_sexo',
        ];
        $map = [];
        foreach ($headers as $index => $header) {
            $key = Str::of(Str::ascii((string) $header))->lower()->trim()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            if (isset($aliases[$key])) {
                $map[$aliases[$key]] = $index;
            }
        }

        return $map;
    }

    private function validatedData(array $data, ?string $fixedCode = null): array
    {
        $code = Cie10Code::format($fixedCode ?? $data['codigo'] ?? '');
        $description = trim((string) ($data['descripcion'] ?? ''));
        $state = mb_strtoupper(trim((string) ($data['estado'] ?? 'ACTIVO')));
        $sex = mb_strtoupper(trim((string) ($data['cotejo_sexo'] ?? 'AMBOS')));
        $errors = [];
        if (! Cie10Code::isValid($code)) {
            $errors['codigo'] = 'Use un código válido, por ejemplo A00, A00.1 o U07.1.';
        }
        if ($description === '' || mb_strlen($description) > 1000) {
            $errors['descripcion'] = 'La descripción es obligatoria y admite hasta 1,000 caracteres.';
        }
        if (! in_array($state, self::STATES, true)) {
            $errors['estado'] = 'Estado no permitido.';
        }
        if (! in_array($sex, self::SEXES, true)) {
            $errors['cotejo_sexo'] = 'Cotejo de sexo no permitido.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'codigo' => $code,
            'codigo_normalizado' => Cie10Code::normalize($code),
            'descripcion' => $description,
            'estado' => $state,
            'cotejo_sexo' => $sex,
        ];
    }

    private function fingerprint(array $data): string
    {
        return hash('sha256', json_encode([
            'codigo' => $data['codigo'],
            'codigo_normalizado' => $data['codigo_normalizado'],
            'descripcion' => $data['descripcion'],
            'estado' => $data['estado'],
            'cotejo_sexo' => $data['cotejo_sexo'],
        ], JSON_UNESCAPED_UNICODE));
    }

    private function snapshot(Cie10 $cie10): array
    {
        return [
            'id' => (int) $cie10->id,
            'codigo' => $cie10->codigo,
            'codigo_normalizado' => $cie10->codigo_normalizado,
            'descripcion' => $cie10->descripcion,
            'estado' => $cie10->estado,
            'cotejo_sexo' => $cie10->cotejo_sexo,
            'source_system' => $cie10->source_system,
            'source_fingerprint' => $cie10->source_fingerprint,
            'updated_at' => optional($cie10->updated_at)->toIso8601String(),
        ];
    }

    private function assertVersion(Cie10 $cie10, string $version): void
    {
        if ($version === '' || ! hash_equals((string) $cie10->source_fingerprint, $version)) {
            throw ValidationException::withMessages([
                'version' => 'El registro cambió desde que fue consultado. Actualice la lista antes de continuar.',
            ]);
        }
    }

    private function batchSummary(Cie10Importacion $batch): array
    {
        return [
            'id' => (int) $batch->id,
            'archivo' => $batch->archivo,
            'estado' => $batch->estado,
            'nuevos' => (int) $batch->nuevos,
            'actualizaciones' => (int) $batch->actualizaciones,
            'sin_cambios' => (int) $batch->sin_cambios,
            'errores' => (int) $batch->errores,
        ];
    }

    private function lockWrites(): void
    {
        $result = DB::selectOne(
            "DECLARE @result INT;
             EXEC @result = sys.sp_getapplock
                @Resource = N'intranet_hsj:catalogos:cie10:write',
                @LockMode = N'Exclusive',
                @LockOwner = N'Transaction',
                @LockTimeout = 10000;
             SELECT @result AS result;"
        );
        if ((int) ($result->result ?? -999) < 0) {
            throw new \RuntimeException('No fue posible bloquear temporalmente el catálogo CIE-10.');
        }
    }
}
