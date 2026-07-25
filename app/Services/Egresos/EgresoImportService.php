<?php

namespace App\Services\Egresos;

use App\Models\Egresos\Cie10;
use App\Models\Egresos\Egreso;
use App\Models\Egresos\Importacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

final class EgresoImportService
{
    private const FIELDS = [
        'renipress', 'e_ubig', 'e_cdpto', 'e_cprov', 'e_cdist', 'cod_disa',
        'cod_red', 'cod_mred', 'numhc', 'nomb', 'apell', 'doc_iden', 'etnia',
        'sexo', 'edad', 'tipoedad', 'ubigeo', 'cdpto', 'cprov', 'cdist', 'fecing',
        'fecegr', 'totalest', 'ups', 'condicion', 'financia', 'coddiag1',
        'coddiag2', 'coddiag3', 'coddiag4', 'cemorb1', 'cemorb2', 'codcpt1',
        'codcpt2', 'codcpt3', 'codcpt4', 'estadio', 'valor_t', 'valor_n',
        'valor_m', 'tratamien', 'prof_parto', 'fecparto', 'rnvivo', 'rnmuerto',
        'codpsal', 'fechareg', 'estado',
    ];

    private const ALIASES = [
        'historia_clinica' => 'numhc',
        'hc' => 'numhc',
        'documento' => 'doc_iden',
        'dni' => 'doc_iden',
        'nombres' => 'nomb',
        'apellidos' => 'apell',
        'fecha_ingreso' => 'fecing',
        'fecha_egreso' => 'fecegr',
        'servicio' => 'ups',
        'financiamiento' => 'financia',
        'diagnostico_1' => 'coddiag1',
        'diagnostico_2' => 'coddiag2',
        'diagnostico_3' => 'coddiag3',
        'diagnostico_4' => 'coddiag4',
    ];

    public function import(UploadedFile $file, array $actor, Request $request): Importacion
    {
        $hash = hash_file('sha256', $file->getRealPath());
        if (Importacion::query()->where('file_sha256', $hash)->where('estado', 'completed')->exists()) {
            throw ValidationException::withMessages([
                'archivo' => 'Este mismo archivo ya fue importado correctamente.',
            ]);
        }

        $import = Importacion::query()->create([
            'source_system' => 'intranet_hsj',
            'archivo' => mb_substr($file->getClientOriginalName(), 0, 255),
            'actor_account_id' => $actor['account_id'],
            'actor_username' => $actor['username'],
            'actor_display_name' => $actor['display_name'],
            'file_sha256' => $hash,
            'estado' => 'running',
            'started_at' => now(),
        ]);

        try {
            [$headers, $rows] = $this->read($file);
            $map = $this->headerMap($headers);
            if (! in_array('fecegr', $map, true) || (! in_array('numhc', $map, true) && ! in_array('doc_iden', $map, true))) {
                throw ValidationException::withMessages([
                    'archivo' => 'El archivo debe incluir fecha de egreso y una columna de historia clínica o documento.',
                ]);
            }

            $cie10 = Cie10::query()->pluck('codigo_normalizado')->flip();
            $existing = Egreso::query()
                ->get(['numhc', 'doc_numero', 'doc_iden', 'fecegr'])
                ->flatMap(fn (Egreso $item) => $this->duplicateKeys($item->toArray()))
                ->flip();
            $valid = [];
            $observations = [];
            $omitted = 0;

            foreach ($rows as $offset => $row) {
                $line = $offset + 2;
                if ($this->emptyRow($row)) {
                    continue;
                }
                $data = $this->rowData($row, $map);
                $errors = $this->validateRow($data, $cie10);
                if ($errors) {
                    $observations[] = ['fila' => $line, 'errores' => $errors];

                    continue;
                }
                $keys = $this->duplicateKeys($data);
                if (collect($keys)->contains(fn ($key) => $existing->has($key))) {
                    $omitted++;

                    continue;
                }
                foreach ($keys as $key) {
                    $existing->put($key, true);
                }
                $valid[] = $data;
            }

            DB::transaction(function () use ($valid, $import, $actor, $request, $observations, $omitted): void {
                $this->lockEgresoWrites();
                $current = Egreso::query()
                    ->get(['numhc', 'doc_numero', 'doc_iden', 'fecegr'])
                    ->flatMap(fn (Egreso $item) => $this->duplicateKeys($item->toArray()))
                    ->flip();
                $accepted = [];
                $concurrentOmitted = 0;

                foreach ($valid as $data) {
                    $keys = $this->duplicateKeys($data);
                    if (collect($keys)->contains(fn ($key) => $current->has($key))) {
                        $concurrentOmitted++;

                        continue;
                    }
                    foreach ($keys as $key) {
                        $current->put($key, true);
                    }
                    $accepted[] = $data;
                }

                foreach ($accepted as $data) {
                    Egreso::query()->create([
                        ...$data,
                        'source_system' => 'intranet_hsj_import',
                        'doc_numero' => $data['doc_iden'] ?? null,
                        'doc_iden_original' => $data['doc_iden'] ?? null,
                        'doc_source' => 'intranet_hsj_import',
                        'source_id' => null,
                        'importacion_id' => $import->id,
                        'source_fingerprint' => $this->fingerprint($data),
                        'imported_at' => now(),
                    ]);
                }

                $import->update([
                    'insertados' => count($accepted),
                    'omitidos' => $omitted + $concurrentOmitted,
                    'errores' => count($observations),
                    'detalle' => ['observaciones' => array_slice($observations, 0, 1000)],
                    'estado' => 'completed',
                    'finished_at' => now(),
                ]);

                DB::table('auditoria.eventos')->insert([
                    'event_uuid' => (string) Str::uuid(),
                    'application_code' => 'intranet_hsj',
                    'module' => 'egresos',
                    'event_type' => 'import.completed',
                    'action' => 'import',
                    'subject_type' => Importacion::class,
                    'subject_id' => (string) $import->id,
                    'actor_account_id' => $actor['account_id'],
                    'actor_username' => $actor['username'],
                    'actor_display_name' => $actor['display_name'],
                    'ip' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 510),
                    'data_after' => json_encode([
                        'archivo' => $import->archivo,
                        'insertados' => count($accepted),
                        'omitidos' => $omitted + $concurrentOmitted,
                        'observados' => count($observations),
                    ], JSON_UNESCAPED_UNICODE),
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return $import->fresh();
        } catch (Throwable $exception) {
            $import->update([
                'estado' => 'failed',
                'errores' => 1,
                'detalle' => ['error' => $exception->getMessage()],
                'finished_at' => now(),
            ]);
            throw $exception;
        }
    }

    private function read(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'dbf') {
            return $this->readDbf($file->getRealPath());
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $headers = array_shift($rows) ?? [];

        return [$headers, $rows];
    }

    private function headerMap(array $headers): array
    {
        return collect($headers)->map(function ($header): ?string {
            $key = Str::of((string) $header)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            $key = self::ALIASES[$key] ?? $key;

            return in_array($key, self::FIELDS, true) ? $key : null;
        })->all();
    }

    private function rowData(array $row, array $map): array
    {
        $data = [];
        foreach ($map as $index => $field) {
            if ($field !== null) {
                $data[$field] = $this->normalize($field, $row[$index] ?? null);
            }
        }

        return $data;
    }

    private function normalize(string $field, mixed $value): mixed
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if (in_array($field, ['fecing', 'fecegr', 'fecparto', 'fechareg'], true)) {
            try {
                if (is_numeric($value)) {
                    return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
                }

                return Carbon::parse(str_replace('/', '-', trim((string) $value)))->format('Y-m-d');
            } catch (Throwable) {
                return '__INVALID_DATE__';
            }
        }
        $value = trim((string) $value);
        if (str_starts_with($field, 'coddiag')) {
            return strtoupper(str_replace('.', '', $value));
        }

        return $value;
    }

    private function validateRow(array $data, $cie10): array
    {
        $errors = [];
        if (empty($data['numhc']) && empty($data['doc_iden'])) {
            $errors[] = 'Falta historia clínica o documento.';
        }
        foreach (['nomb' => 'nombres', 'apell' => 'apellidos', 'fecing' => 'fecha de ingreso', 'fecegr' => 'fecha de egreso', 'ups' => 'UPS', 'coddiag1' => 'diagnóstico principal'] as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "Falta {$label}.";
            }
        }
        foreach (['fecing', 'fecegr', 'fecparto', 'fechareg'] as $field) {
            if (($data[$field] ?? null) === '__INVALID_DATE__') {
                $errors[] = "La columna {$field} contiene una fecha inválida.";
            }
        }
        if (! empty($data['fecing']) && ! empty($data['fecegr']) && $data['fecing'] !== '__INVALID_DATE__' && $data['fecegr'] !== '__INVALID_DATE__' && $data['fecegr'] < $data['fecing']) {
            $errors[] = 'La fecha de egreso es anterior al ingreso.';
        }
        foreach (range(1, 4) as $position) {
            $code = $data["coddiag{$position}"] ?? null;
            if ($code && ! $cie10->has($code)) {
                $errors[] = "El diagnóstico {$code} no existe en CIE-10.";
            }
        }

        return $errors;
    }

    private function duplicateKeys(array $data): array
    {
        $date = $data['fecegr'] instanceof \DateTimeInterface
            ? $data['fecegr']->format('Y-m-d')
            : substr((string) ($data['fecegr'] ?? ''), 0, 10);
        $keys = [];
        if (! empty($data['numhc'])) {
            $keys[] = 'hc:'.mb_strtoupper(trim((string) $data['numhc'])).':'.$date;
        }
        $document = $data['doc_numero'] ?? $data['doc_iden'] ?? null;
        if (! empty($document)) {
            $keys[] = 'doc:'.mb_strtoupper(trim((string) $document)).':'.$date;
        }

        return $keys;
    }

    private function fingerprint(array $data): string
    {
        return hash('sha256', json_encode(collect($data)->sortKeys()->all(), JSON_UNESCAPED_UNICODE));
    }

    private function lockEgresoWrites(): void
    {
        $result = DB::selectOne(
            "DECLARE @result INT;
             EXEC @result = sys.sp_getapplock
                @Resource = N'intranet_hsj:egresos:write',
                @LockMode = N'Exclusive',
                @LockOwner = N'Transaction',
                @LockTimeout = 10000;
             SELECT @result AS result;"
        );

        if ((int) ($result->result ?? -999) < 0) {
            throw new \RuntimeException('No fue posible obtener el bloqueo de escritura de Egresos.');
        }
    }

    private function emptyRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => $value === null || trim((string) $value) === '');
    }

    private function readDbf(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || strlen($content) < 32) {
            throw ValidationException::withMessages(['archivo' => 'El archivo DBF no es válido.']);
        }
        $count = unpack('V', substr($content, 4, 4))[1] ?? 0;
        $headerLength = unpack('v', substr($content, 8, 2))[1] ?? 0;
        $recordLength = unpack('v', substr($content, 10, 2))[1] ?? 0;
        $fields = [];
        for ($offset = 32; $offset < $headerLength && ord($content[$offset]) !== 0x0D; $offset += 32) {
            $descriptor = substr($content, $offset, 32);
            $fields[] = [
                'name' => trim(str_replace("\0", '', substr($descriptor, 0, 11))),
                'type' => strtoupper($descriptor[11] ?? 'C'),
                'length' => ord($descriptor[16] ?? "\0"),
            ];
        }
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $record = substr($content, $headerLength + ($i * $recordLength), $recordLength);
            if (($record[0] ?? '*') === '*') {
                continue;
            }
            $row = [];
            $position = 1;
            foreach ($fields as $field) {
                $raw = trim(substr($record, $position, $field['length']));
                $position += $field['length'];
                $value = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
                if ($field['type'] === 'D' && preg_match('/^\d{8}$/', $value)) {
                    $value = substr($value, 0, 4).'-'.substr($value, 4, 2).'-'.substr($value, 6, 2);
                }
                $row[] = $value;
            }
            $rows[] = $row;
        }

        return [array_column($fields, 'name'), $rows];
    }
}
