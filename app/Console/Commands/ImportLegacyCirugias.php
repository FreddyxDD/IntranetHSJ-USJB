<?php

namespace App\Console\Commands;

use App\Services\Migration\LegacyRowFingerprint;
use App\Services\Migration\MySqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ImportLegacyCirugias extends Command
{
    private const SOURCE_SYSTEM = 'hospital_ueei';

    private const SOURCE_SHA256 = '2D663F528F21E236DC7EDBB9FB7521761081FF8F63A59191681864E02E66C8C6';

    private const EXPECTED_COUNTS = [
        'cirugias' => 798,
        'historial_importaciones_cirugias' => 11,
        'personal_medico' => 50,
    ];

    private const PARTICIPANT_COLUMNS = [
        'cirujano_1' => ['cirujano', 1],
        'cirujano_2' => ['cirujano', 2],
        'anestesiologo' => ['anestesiologo', 1],
        'enfermera_instrumentista' => ['enfermeria_instrumentista', 1],
        'anestesiologo_recuperacion' => ['anestesiologo_recuperacion', 1],
        'enfermera_recuperacion' => ['enfermeria_recuperacion', 1],
        'tecnico_enfermeria_1' => ['tecnico_enfermeria', 1],
        'tecnico_enfermeria_2' => ['tecnico_enfermeria', 2],
    ];

    protected $signature = 'hsj:import-cirugias
        {dump : Ruta absoluta de HSJ_DATA.sql}
        {--apply : Ejecutar la importación; sin esta opción solo valida}';

    protected $description = 'Importa Cirugías e historial desde el respaldo aprobado de hospital_ueei.';

    public function handle(): int
    {
        $reader = new MySqlDumpReader((string) $this->argument('dump'));

        if (! hash_equals(self::SOURCE_SHA256, strtoupper($reader->sha256()))) {
            throw new RuntimeException(
                'La huella SHA-256 no coincide con HSJ_DATA.sql aprobado.'
            );
        }

        $counts = $this->sourceCounts($reader);
        $this->table(
            ['Entidad', 'Conteo fuente', 'Esperado'],
            collect($counts)->map(
                fn (int $count, string $table): array => [
                    $table,
                    $count,
                    self::EXPECTED_COUNTS[$table],
                ]
            )->values()->all()
        );

        if (! $this->option('apply')) {
            $this->warn('Simulación completada. No se importaron datos.');

            return self::SUCCESS;
        }

        $database = DB::connection();
        $runId = $this->startRun($database, $reader, array_sum($counts));

        try {
            $database->transaction(function () use ($database, $reader): void {
                $this->importImportHistory($database, $reader);
                $this->importSpecialties($database, $reader);
                $this->importSurgeries($database, $reader);
                $this->importParticipants($database, $reader);
            }, 3);

            $this->validateTarget($database);
            $this->finishRun($database, $runId, 'completed', $counts);
        } catch (Throwable $exception) {
            $this->finishRun($database, $runId, 'failed', $counts, $exception);

            throw $exception;
        }

        $this->info('Cirugías importadas y validadas correctamente.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    private function sourceCounts(MySqlDumpReader $reader): array
    {
        $counts = [];

        foreach (self::EXPECTED_COUNTS as $table => $expected) {
            $count = $reader->count($table);

            if ($count !== $expected) {
                throw new RuntimeException(
                    "Conteo inválido para {$table}: {$count}; se esperaban {$expected}."
                );
            }

            $counts[$table] = $count;
        }

        return $counts;
    }

    private function importImportHistory(
        ConnectionInterface $database,
        MySqlDumpReader $reader
    ): void {
        $identityMap = $this->identityMapByUsername($database);
        $rows = [];

        foreach ($reader->rows('historial_importaciones_cirugias') as $source) {
            $actor = $identityMap[$this->normalizeName($source['usuario'])] ?? null;
            $rows[] = [
                'source_system' => self::SOURCE_SYSTEM,
                'source_id' => (int) $source['id'],
                'nombre_archivo' => $source['nombre_archivo'],
                'hoja' => $source['hoja'],
                'total_registros' => (int) $source['total_registros'],
                'registros_validos' => (int) $source['registros_validos'],
                'registros_observados' => (int) $source['registros_observados'],
                'actor_account_id' => $actor?->identity_account_id,
                'actor_person_id' => $actor?->identity_person_id,
                'actor_username' => $source['usuario'],
                'source_fingerprint' => LegacyRowFingerprint::make($source),
                'source_created_at' => $source['fecha_carga'],
                'imported_at' => now(),
                'created_at' => $source['fecha_carga'],
                'updated_at' => now(),
            ];
        }

        $this->upsert(
            $database,
            'cirugias.importaciones',
            $rows,
            ['source_system', 'source_id'],
            50
        );
    }

    private function importSpecialties(
        ConnectionInterface $database,
        MySqlDumpReader $reader
    ): void {
        $specialties = [];

        foreach ($reader->rows('cirugias') as $source) {
            $name = trim((string) ($source['especialidad'] ?? ''));

            if ($name !== '') {
                $specialties[$this->normalizeName($name)] = $name;
            }
        }

        $rows = [];

        foreach ($specialties as $normalized => $name) {
            $rows[] = [
                'nombre' => $name,
                'nombre_normalizado' => $normalized,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->upsert(
            $database,
            'cirugias.especialidades',
            $rows,
            ['nombre_normalizado'],
            100
        );
    }

    private function importSurgeries(
        ConnectionInterface $database,
        MySqlDumpReader $reader
    ): void {
        $rows = [];

        foreach ($reader->rows('cirugias') as $source) {
            $row = [
                'source_system' => self::SOURCE_SYSTEM,
                'source_id' => (int) $source['id'],
                'importacion_id' => null,
            ];

            foreach ($this->surgeryColumns() as $column) {
                $row[$column] = $source[$column];
            }

            $row['edad'] = $this->nullableInt($source['edad']);
            $row['source_fingerprint'] = LegacyRowFingerprint::make($source);
            $row['source_created_at'] = $source['creado_en'];
            $row['source_updated_at'] = $source['actualizado_en'];
            $row['imported_at'] = now();
            $row['created_at'] = $source['creado_en'];
            $row['updated_at'] = now();
            $rows[] = $row;
        }

        $this->upsert(
            $database,
            'cirugias.cirugias',
            $rows,
            ['source_system', 'source_id'],
            25
        );
    }

    private function importParticipants(
        ConnectionInterface $database,
        MySqlDumpReader $reader
    ): void {
        $surgeryMap = $database->table('cirugias.cirugias')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->pluck('id', 'source_id');
        $personnelByName = $this->personnelByName($reader);
        $stagingBySourceId = $database->table('staging.personnel_map')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('source_table', 'personal_medico')
            ->get()
            ->keyBy(fn (object $row): string => (string) $row->source_personnel_id);
        $rows = [];

        foreach ($reader->rows('cirugias') as $source) {
            $surgeryId = $surgeryMap->get((int) $source['id']);

            if (! $surgeryId) {
                throw new RuntimeException(
                    "No se resolvió la cirugía legada {$source['id']}."
                );
            }

            foreach (self::PARTICIPANT_COLUMNS as $column => [$role, $order]) {
                $displayName = trim((string) ($source[$column] ?? ''));

                if ($displayName === '') {
                    continue;
                }

                $personnelMatches = $personnelByName[$this->normalizeName($displayName)] ?? [];
                $sourcePersonnelId = count($personnelMatches) === 1
                    ? (string) $personnelMatches[0]['id']
                    : null;
                $mapping = $sourcePersonnelId !== null
                    ? $stagingBySourceId->get($sourcePersonnelId)
                    : null;

                $rows[] = [
                    'cirugia_id' => $surgeryId,
                    'rol' => $role,
                    'orden' => $order,
                    'source_display_name' => $displayName,
                    'source_personnel_id' => $sourcePersonnelId,
                    'identity_person_id' => $mapping?->identity_person_id,
                    'identity_personnel_record_id' => $mapping?->identity_personnel_record_id,
                    'identity_assignment_id' => $mapping?->identity_assignment_id,
                    'match_status' => $mapping?->review_status ?? (
                        count($personnelMatches) > 1 ? 'ambiguous' : 'pending'
                    ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $this->upsert(
            $database,
            'cirugias.participantes',
            $rows,
            ['cirugia_id', 'rol', 'orden'],
            50
        );
    }

    private function validateTarget(ConnectionInterface $database): void
    {
        $surgeries = $database->table('cirugias.cirugias')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->count();
        $imports = $database->table('cirugias.importaciones')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->count();
        $orphanParticipants = $database->table('cirugias.participantes as p')
            ->leftJoin('cirugias.cirugias as c', 'c.id', '=', 'p.cirugia_id')
            ->whereNull('c.id')
            ->count();

        if (
            $surgeries !== self::EXPECTED_COUNTS['cirugias']
            || $imports !== self::EXPECTED_COUNTS['historial_importaciones_cirugias']
            || $orphanParticipants !== 0
        ) {
            throw new RuntimeException(
                "Validación de Cirugías fallida: cirugías={$surgeries}, "
                ."importaciones={$imports}, participantes huérfanos={$orphanParticipants}."
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     */
    private function upsert(
        ConnectionInterface $database,
        string $table,
        array $rows,
        array $uniqueBy,
        int $chunkSize
    ): void {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $updateColumns = array_values(array_diff(
                array_keys($chunk[0]),
                [...$uniqueBy, 'created_at']
            ));
            $database->table($table)->upsert($chunk, $uniqueBy, $updateColumns);
        }
    }

    /**
     * @return list<string>
     */
    private function surgeryColumns(): array
    {
        return [
            'fecha', 'hora', 'historia_clinica', 'dni', 'nombres_apellidos',
            'tipo_orden', 'especialidad', 'edad', 'sexo', 'tipo_seguro',
            'prueba_covid', 'suspension', 'motivo_suspension',
            'diagnostico_preoperatorio', 'codigo_cie10', 'operacion_realizada',
            'comorbilidad', 'reintervencion', 'ram_medicamentos',
            'discrepancia_diagnostica', 'tiempo_total', 'tiempo_anestesia',
            'tiempo_operacion', 'complicaciones_intraoperatorias', 'cirujano_1',
            'cirujano_2', 'anestesiologo', 'enfermera_instrumentista',
            'anestesiologo_recuperacion', 'enfermera_recuperacion',
            'tecnico_enfermeria_1', 'tecnico_enfermeria_2', 'tipo_anestesia',
            'cirugia_mayor', 'cirugia_menor', 'sop', 'destino', 'tiempo_urpa',
            'observaciones', 'hoja_origen', 'origen_registro',
        ];
    }

    /**
     * @return array<string, list<array<string, string|null>>>
     */
    private function personnelByName(MySqlDumpReader $reader): array
    {
        $result = [];

        foreach ($reader->rows('personal_medico') as $row) {
            $key = $this->normalizeName($row['apellidos_nombres']);

            if ($key !== '') {
                $result[$key][] = $row;
            }
        }

        return $result;
    }

    /**
     * @return array<string, object>
     */
    private function identityMapByUsername(ConnectionInterface $database): array
    {
        $result = [];
        $rows = $database->table('staging.identity_user_map')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('review_status', 'matched')
            ->get();

        foreach ($rows as $row) {
            $key = $this->normalizeName($row->source_username);

            if ($key !== '') {
                $result[$key] = $row;
            }
        }

        return $result;
    }

    private function normalizeName(mixed $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return mb_strtoupper($value ?? '');
    }

    private function nullableInt(?string $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function startRun(
        ConnectionInterface $database,
        MySqlDumpReader $reader,
        int $sourceCount
    ): int {
        return (int) $database->table('staging.import_runs')->insertGetId([
            'run_uuid' => (string) Str::uuid(),
            'source_system' => self::SOURCE_SYSTEM,
            'source_file_name' => basename((string) $this->argument('dump')),
            'source_file_sha256' => strtoupper($reader->sha256()),
            'entity' => 'cirugias:all',
            'status' => 'running',
            'dry_run' => false,
            'source_count' => $sourceCount,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function finishRun(
        ConnectionInterface $database,
        int $runId,
        string $status,
        array $counts,
        ?Throwable $exception = null
    ): void {
        $summary = [
            'source_counts' => $counts,
            'target_counts' => [
                'cirugias' => $database->table('cirugias.cirugias')
                    ->where('source_system', self::SOURCE_SYSTEM)->count(),
                'importaciones' => $database->table('cirugias.importaciones')
                    ->where('source_system', self::SOURCE_SYSTEM)->count(),
                'participantes' => $database->table('cirugias.participantes')->count(),
            ],
        ];

        if ($exception) {
            $summary['error_class'] = $exception::class;
        }

        $database->table('staging.import_runs')->where('id', $runId)->update([
            'status' => $status,
            'validation_summary' => json_encode($summary, JSON_UNESCAPED_UNICODE),
            'error_count' => $exception ? 1 : 0,
            'finished_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
