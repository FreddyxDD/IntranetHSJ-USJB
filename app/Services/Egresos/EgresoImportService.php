<?php

namespace App\Services\Egresos;

use App\Models\Egresos\Cie10;
use App\Models\Egresos\Egreso;
use App\Models\Egresos\Importacion;
use App\Models\Egresos\ImportacionFila;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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

    public function __construct(private readonly SighPatientSource $patientSource) {}

    public function preview(UploadedFile $file, array $actor, Request $request): Importacion
    {
        $hash = hash_file('sha256', $file->getRealPath());
        $previous = Importacion::query()
            ->where('file_sha256', $hash)
            ->whereIn('estado', ['pending', 'running', 'completed'])
            ->latest('id')
            ->first();

        if ($previous) {
            $message = $previous->estado === 'completed'
                ? "Este archivo ya fue importado en el lote #{$previous->id}."
                : "Este archivo ya tiene el análisis pendiente #{$previous->id}.";

            throw ValidationException::withMessages(['archivo' => $message]);
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
            $this->validateHeaders($map);
            $parsed = collect($rows)
                ->map(fn (array $row, int $offset): array => [
                    'fila' => $offset + 2,
                    'vacia' => $this->emptyRow($row),
                    'datos' => $this->prepareIdentity($this->rowData($row, $map)),
                ])
                ->reject(fn (array $row): bool => $row['vacia'])
                ->values();

            [$masterPatients, $masterError] = $this->masterPatients($parsed);
            $cie10 = Cie10::query()->pluck('codigo_normalizado')->flip();
            $profiles = $this->existingProfiles();
            $stagedProfiles = [
                'episodes' => collect(),
                'histories' => collect(),
                'documents' => collect(),
            ];
            $stagedRows = $parsed->map(function (array $row) use (
                $cie10,
                $masterPatients,
                $masterError,
                $profiles,
                $stagedProfiles
            ): array {
                $data = $row['datos'];
                $messages = $this->validateRow($data, $cie10);
                $history = $this->clean($data['numhc'] ?? null);
                $master = $masterPatients->get($history);

                if ($master) {
                    [$data, $masterMessages] = $this->applyMasterPatient($data, $master);
                    $messages = [...$messages, ...$masterMessages];
                } elseif ($masterError) {
                    $messages[] = $this->message(
                        'warning',
                        'patient_source_unavailable',
                        'No fue posible validar esta fila contra la fuente maestra de pacientes.'
                    );
                } else {
                    $messages[] = $this->message(
                        'warning',
                        'patient_not_found',
                        'La historia clínica no fue encontrada en la fuente maestra; requiere verificación.'
                    );
                }

                $errors = collect($messages)->where('severity', 'error');
                $episodeMatch = $this->episodeMatch($data, $profiles['episodes'], $stagedProfiles['episodes']);
                $identity = $this->identityClassification($data, $profiles, $stagedProfiles, (bool) $master);

                if ($errors->isNotEmpty()) {
                    $status = 'error';
                } elseif ($episodeMatch) {
                    $status = 'duplicado';
                    $messages[] = $this->message(
                        'info',
                        'duplicate_episode',
                        'Este episodio ya existe con la misma identidad, ingreso, egreso y servicio.'
                    );
                } elseif ($identity['conflict']) {
                    $status = 'observado';
                    $messages = [...$messages, ...$identity['messages']];
                } elseif ($identity['known']) {
                    $status = 'reingreso';
                    $messages[] = $this->message(
                        'info',
                        'readmission',
                        'Paciente conocido: se agregará como un nuevo episodio en su línea de tiempo.'
                    );
                } else {
                    $status = 'nuevo';
                }

                $this->registerStagedProfile($data, $stagedProfiles);

                return [
                    'importacion_id' => null,
                    'fila' => $row['fila'],
                    'estado' => $status,
                    'paciente_clave' => $this->patientKey($data),
                    'numhc' => $data['numhc'] ?? null,
                    'doc_iden' => $data['doc_iden'] ?? null,
                    'patient_source_id' => $data['patient_source_id'] ?? null,
                    'existing_egreso_id' => $episodeMatch['id'] ?? null,
                    'datos' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'mensajes' => $messages ? json_encode($messages, JSON_UNESCAPED_UNICODE) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            DB::transaction(function () use ($import, $stagedRows, $actor, $request, $masterError): void {
                $rows = $stagedRows->map(function (array $row) use ($import): array {
                    $row['importacion_id'] = $import->id;

                    return $row;
                });
                foreach ($rows->chunk(100) as $chunk) {
                    DB::table('egresos.importacion_filas')->insert($chunk->all());
                }

                $summary = $this->summary($rows);
                $import->update([
                    'insertados' => 0,
                    'omitidos' => $summary['duplicado'],
                    'errores' => $summary['error'] + $summary['observado'],
                    'detalle' => [
                        'resumen' => $summary,
                        'fuente_pacientes_disponible' => ! $masterError,
                        'mensaje_fuente' => $masterError,
                    ],
                    'estado' => 'pending',
                ]);
                $this->audit(
                    $import,
                    'import.previewed',
                    'preview',
                    ['archivo' => $import->archivo, 'resumen' => $summary],
                    $actor,
                    $request
                );
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

    public function commit(Importacion $import, array $actor, Request $request): Importacion
    {
        return DB::transaction(function () use ($import, $actor, $request): Importacion {
            $locked = Importacion::query()->lockForUpdate()->findOrFail($import->id);
            if ($locked->estado !== 'pending') {
                throw ValidationException::withMessages([
                    'importacion' => 'El lote no está pendiente de confirmación.',
                ]);
            }

            $this->lockEgresoWrites();
            $episodeProfiles = $this->existingProfiles()['episodes'];
            $inserted = 0;
            $concurrentDuplicates = 0;

            ImportacionFila::query()
                ->where('importacion_id', $locked->id)
                ->whereIn('estado', ['nuevo', 'reingreso'])
                ->orderBy('fila')
                ->get()
                ->each(function (ImportacionFila $row) use (
                    $locked,
                    $episodeProfiles,
                    &$inserted,
                    &$concurrentDuplicates
                ): void {
                    $data = $row->datos;
                    $match = $this->episodeMatch($data, $episodeProfiles, collect());
                    if ($match) {
                        $messages = $row->mensajes ?? [];
                        $messages[] = $this->message(
                            'info',
                            'concurrent_duplicate',
                            'El episodio fue registrado por otro proceso antes de confirmar este lote.'
                        );
                        $row->update([
                            'estado' => 'duplicado',
                            'existing_egreso_id' => $match['id'],
                            'mensajes' => $messages,
                        ]);
                        $concurrentDuplicates++;

                        return;
                    }

                    $egreso = Egreso::query()->create($this->databaseData($data, $locked));
                    $row->update([
                        'estado' => 'insertado',
                        'imported_egreso_id' => $egreso->id,
                    ]);
                    foreach ($this->episodeKeys($data) as $key) {
                        $episodeProfiles->put($key, ['id' => $egreso->id]);
                    }
                    $inserted++;
                });

            $statuses = ImportacionFila::query()
                ->where('importacion_id', $locked->id)
                ->select('estado')
                ->selectRaw('COUNT(*) total')
                ->groupBy('estado')
                ->pluck('total', 'estado');
            $summary = collect(['nuevo', 'reingreso', 'duplicado', 'observado', 'error', 'insertado'])
                ->mapWithKeys(fn (string $status): array => [$status => (int) ($statuses[$status] ?? 0)])
                ->all();

            $locked->update([
                'insertados' => $inserted,
                'omitidos' => $summary['duplicado'],
                'errores' => $summary['error'] + $summary['observado'],
                'detalle' => [
                    ...($locked->detalle ?? []),
                    'resumen_final' => $summary,
                    'duplicados_concurrentes' => $concurrentDuplicates,
                ],
                'estado' => 'completed',
                'finished_at' => now(),
            ]);
            $this->audit(
                $locked,
                'import.completed',
                'import',
                [
                    'archivo' => $locked->archivo,
                    'insertados' => $inserted,
                    'omitidos' => $summary['duplicado'],
                    'observados' => $summary['observado'] + $summary['error'],
                ],
                $actor,
                $request
            );

            return $locked->fresh();
        }, 3);
    }

    private function validateHeaders(array $map): void
    {
        if (! in_array('fecegr', $map, true)
            || (! in_array('numhc', $map, true) && ! in_array('doc_iden', $map, true))) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo debe incluir fecha de egreso y una columna de historia clínica o documento.',
            ]);
        }
    }

    private function masterPatients(Collection $rows): array
    {
        try {
            $patients = $this->patientSource->byHistories(
                $rows->pluck('datos.numhc')->filter()->unique()->values()->all()
            );

            return [$patients, null];
        } catch (Throwable) {
            return [collect(), 'La fuente maestra de pacientes no estuvo disponible durante el análisis.'];
        }
    }

    private function applyMasterPatient(array $data, object $patient): array
    {
        $messages = [];
        $masterDocument = $this->normalizeDocument($patient->NroDocumento ?? null)['number'];
        $fileDocument = $this->clean($data['doc_iden'] ?? null);
        if ($masterDocument !== '' && $masterDocument !== $fileDocument) {
            $messages[] = $this->message(
                'warning',
                'document_corrected_from_master',
                $fileDocument === ''
                    ? "Se completó el documento {$masterDocument} desde la fuente maestra."
                    : "El documento {$fileDocument} fue reemplazado por {$masterDocument} según la fuente maestra."
            );
            $data['doc_iden'] = $masterDocument;
            $data['doc_numero'] = $masterDocument;
        }

        $data['doc_tipo_id'] = $patient->IdDocIdentidad ?: ($data['doc_tipo_id'] ?? null);
        $data['patient_source_id'] = $patient->IdPaciente;
        $data['doc_source'] = $this->patientSource->sourceCode();
        $data['document_verified_at'] = now()->toISOString();

        return [$data, $messages];
    }

    private function existingProfiles(): array
    {
        $episodes = collect();
        $histories = collect();
        $documents = collect();

        Egreso::query()
            ->get([
                'id', 'numhc', 'doc_numero', 'fecing', 'fecegr', 'ups',
                'nomb', 'apell',
            ])
            ->each(function (Egreso $egreso) use ($episodes, $histories, $documents): void {
                $data = [
                    'numhc' => $egreso->numhc,
                    'doc_iden' => $egreso->documento,
                    'fecing' => $egreso->fecing?->format('Y-m-d'),
                    'fecegr' => $egreso->fecegr?->format('Y-m-d'),
                    'ups' => $egreso->ups,
                ];
                foreach ($this->episodeKeys($data) as $key) {
                    $episodes->put($key, ['id' => $egreso->id]);
                }
                $this->addProfile($histories, $this->clean($egreso->numhc), [
                    'id' => $egreso->id,
                    'document' => $this->clean($egreso->documento),
                    'name' => $this->normalizedName($egreso->nomb, $egreso->apell),
                ]);
                $this->addProfile($documents, $this->clean($egreso->documento), [
                    'id' => $egreso->id,
                    'history' => $this->clean($egreso->numhc),
                    'name' => $this->normalizedName($egreso->nomb, $egreso->apell),
                ]);
            });

        return compact('episodes', 'histories', 'documents');
    }

    private function identityClassification(
        array $data,
        array $profiles,
        array $staged,
        bool $masterConfirmed
    ): array {
        $history = $this->clean($data['numhc'] ?? null);
        $document = $this->clean($data['doc_iden'] ?? null);
        $historyProfiles = collect($profiles['histories']->get($history, []))
            ->merge($staged['histories']->get($history, []));
        $documentProfiles = collect($profiles['documents']->get($document, []))
            ->merge($staged['documents']->get($document, []));
        $messages = [];
        $conflict = false;

        $otherDocuments = $historyProfiles->pluck('document')->filter()->unique();
        if ($document !== '' && $otherDocuments->isNotEmpty() && ! $otherDocuments->contains($document)) {
            if ($masterConfirmed) {
                $messages[] = $this->message(
                    'warning',
                    'history_document_reconciled',
                    'La historia tenía otro documento en registros previos; se acepta el documento confirmado por la fuente maestra.'
                );
            } else {
                $conflict = true;
                $messages[] = $this->message(
                    'error',
                    'history_document_conflict',
                    "La historia clínica {$history} está relacionada con otro documento."
                );
            }
        }

        $otherHistories = $documentProfiles->pluck('history')->filter()->unique();
        if ($history !== '' && $otherHistories->isNotEmpty() && ! $otherHistories->contains($history)) {
            if ($masterConfirmed) {
                $messages[] = $this->message(
                    'warning',
                    'document_history_reconciled',
                    'El documento tenía otra historia en registros previos; la fuente maestra confirmó la historia de esta fila.'
                );
            } else {
                $conflict = true;
                $messages[] = $this->message(
                    'error',
                    'document_history_conflict',
                    "El documento {$document} está relacionado con otra historia clínica."
                );
            }
        }

        return [
            'known' => $historyProfiles->isNotEmpty() || $documentProfiles->isNotEmpty(),
            'conflict' => $conflict,
            'messages' => $messages,
        ];
    }

    private function registerStagedProfile(array $data, array $profiles): void
    {
        $history = $this->clean($data['numhc'] ?? null);
        $document = $this->clean($data['doc_iden'] ?? null);
        foreach ($this->episodeKeys($data) as $key) {
            $profiles['episodes']->put($key, ['id' => null]);
        }
        $this->addProfile($profiles['histories'], $history, [
            'id' => null,
            'document' => $document,
            'name' => $this->normalizedName($data['nomb'] ?? null, $data['apell'] ?? null),
        ]);
        $this->addProfile($profiles['documents'], $document, [
            'id' => null,
            'history' => $history,
            'name' => $this->normalizedName($data['nomb'] ?? null, $data['apell'] ?? null),
        ]);
    }

    private function addProfile(Collection $profiles, string $key, array $profile): void
    {
        if ($key === '') {
            return;
        }

        $values = collect($profiles->get($key, []));
        $values->push($profile);
        $profiles->put($key, $values->all());
    }

    private function episodeMatch(array $data, Collection $existing, Collection $staged): ?array
    {
        foreach ($this->episodeKeys($data) as $key) {
            if ($existing->has($key)) {
                return $existing->get($key);
            }
            if ($staged->has($key)) {
                return $staged->get($key);
            }
        }

        return null;
    }

    private function episodeKeys(array $data): array
    {
        $admission = substr((string) ($data['fecing'] ?? ''), 0, 10);
        $discharge = substr((string) ($data['fecegr'] ?? ''), 0, 10);
        $ups = mb_strtoupper($this->clean($data['ups'] ?? null));
        $suffix = "{$admission}:{$discharge}:{$ups}";
        $keys = [];
        $history = mb_strtoupper($this->clean($data['numhc'] ?? null));
        $document = mb_strtoupper($this->clean($data['doc_numero'] ?? $data['doc_iden'] ?? null));

        if ($history !== '') {
            $keys[] = "hc:{$history}:{$suffix}";
        }
        if ($document !== '') {
            $keys[] = "doc:{$document}:{$suffix}";
        }

        return $keys;
    }

    private function databaseData(array $data, Importacion $import): array
    {
        $fields = [
            ...self::FIELDS,
            'doc_tipo_id',
            'patient_source_id',
            'doc_source',
            'document_verified_at',
        ];
        $values = Arr::only($data, $fields);
        $values['doc_numero'] = $data['doc_iden'] ?? null;
        $values['doc_iden_original'] = $data['doc_iden_original'] ?? $data['doc_iden'] ?? null;
        $values['source_system'] = 'intranet_hsj_import';
        $values['source_id'] = null;
        $values['importacion_id'] = $import->id;
        $values['source_fingerprint'] = $this->fingerprint($data);
        $values['episode_fingerprint'] = $this->episodeFingerprint($data);
        $values['doc_source'] = $data['doc_source'] ?? 'intranet_hsj_import';
        $values['imported_at'] = now();

        return $values;
    }

    private function summary(Collection $rows): array
    {
        $counts = $rows->countBy('estado');

        return collect(['nuevo', 'reingreso', 'duplicado', 'observado', 'error'])
            ->mapWithKeys(fn (string $status): array => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    private function prepareIdentity(array $data): array
    {
        $document = $this->normalizeDocument($data['doc_iden'] ?? null);
        $data['doc_iden_original'] = $this->clean($data['doc_iden'] ?? null) ?: null;
        $data['doc_iden'] = $document['number'] ?: null;
        $data['doc_numero'] = $document['number'] ?: null;
        $data['doc_tipo_id'] = $document['type'];
        $data['nomb'] = $this->clean($data['nomb'] ?? null) ?: null;
        $data['apell'] = $this->clean($data['apell'] ?? null) ?: null;

        return $data;
    }

    private function normalizeDocument(mixed $value): array
    {
        $raw = $this->clean($value);
        if (preg_match('/^([123])(\d{8,})$/', $raw, $matches)) {
            return ['number' => $matches[2], 'type' => (int) $matches[1]];
        }
        if (in_array($raw, ['', '0', '9'], true)) {
            return ['number' => '', 'type' => null];
        }

        return ['number' => $raw, 'type' => null];
    }

    private function validateRow(array $data, Collection $cie10): array
    {
        $messages = [];
        if (empty($data['numhc']) && empty($data['doc_iden'])) {
            $messages[] = $this->message('error', 'missing_identity', 'Falta historia clínica o documento.');
        }
        foreach ([
            'nomb' => 'nombres',
            'apell' => 'apellidos',
            'fecing' => 'fecha de ingreso',
            'fecegr' => 'fecha de egreso',
            'ups' => 'UPS',
            'coddiag1' => 'diagnóstico principal',
        ] as $field => $label) {
            if (empty($data[$field])) {
                $messages[] = $this->message('error', "missing_{$field}", "Falta {$label}.");
            }
        }
        foreach (['fecing', 'fecegr', 'fecparto', 'fechareg'] as $field) {
            if (($data[$field] ?? null) === '__INVALID_DATE__') {
                $messages[] = $this->message('error', "invalid_{$field}", "La columna {$field} contiene una fecha inválida.");
            }
        }
        if (! empty($data['fecing'])
            && ! empty($data['fecegr'])
            && $data['fecing'] !== '__INVALID_DATE__'
            && $data['fecegr'] !== '__INVALID_DATE__'
            && $data['fecegr'] < $data['fecing']) {
            $messages[] = $this->message('error', 'invalid_date_range', 'La fecha de egreso es anterior al ingreso.');
        }
        foreach (range(1, 4) as $position) {
            $code = $data["coddiag{$position}"] ?? null;
            if ($code && ! $cie10->has($code)) {
                $messages[] = $this->message(
                    'error',
                    "invalid_cie10_{$position}",
                    "El diagnóstico {$code} no existe en CIE-10."
                );
            }
        }
        if (empty($data['doc_iden'])) {
            $messages[] = $this->message(
                'warning',
                'missing_document',
                'El documento está vacío; el episodio se identificará por historia clínica.'
            );
        }

        return $messages;
    }

    private function message(string $severity, string $code, string $message): array
    {
        return compact('severity', 'code', 'message');
    }

    private function patientKey(array $data): string
    {
        $history = $this->clean($data['numhc'] ?? null);
        $document = $this->clean($data['doc_iden'] ?? null);

        return $history !== '' ? 'hc:'.$history : 'doc:'.$document;
    }

    private function normalizedName(mixed $names, mixed $surnames): string
    {
        return mb_strtoupper($this->clean($names).' '.$this->clean($surnames));
    }

    private function fingerprint(array $data): string
    {
        return hash('sha256', json_encode(collect($data)->sortKeys()->all(), JSON_UNESCAPED_UNICODE));
    }

    private function episodeFingerprint(array $data): string
    {
        return hash('sha256', $this->episodeKeys($data)[0] ?? $this->fingerprint($data));
    }

    private function audit(
        Importacion $import,
        string $eventType,
        string $action,
        array $data,
        array $actor,
        Request $request
    ): void {
        DB::table('auditoria.eventos')->insert([
            'event_uuid' => (string) Str::uuid(),
            'application_code' => 'intranet_hsj',
            'module' => 'egresos',
            'event_type' => $eventType,
            'action' => $action,
            'subject_type' => Importacion::class,
            'subject_id' => (string) $import->id,
            'actor_account_id' => $actor['account_id'],
            'actor_username' => $actor['username'],
            'actor_display_name' => $actor['display_name'],
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 510),
            'data_after' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function read(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'dbf') {
            return $this->readDbf($file->getRealPath());
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        $headers = array_shift($rows) ?? [];

        return [$headers, $rows];
    }

    private function headerMap(array $headers): array
    {
        return collect($headers)->map(function ($header): ?string {
            $key = Str::of((string) $header)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
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
                $text = trim((string) $value);
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $text, $parts)) {
                    $first = (int) $parts[1];
                    $second = (int) $parts[2];
                    $format = $first > 12 ? 'd/m/Y' : ($second > 12 ? 'm/d/Y' : 'm/d/Y');

                    return Carbon::createFromFormat($format, $text)->format('Y-m-d');
                }

                return Carbon::parse($text)->format('Y-m-d');
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

    private function clean(mixed $value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?: '';
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
