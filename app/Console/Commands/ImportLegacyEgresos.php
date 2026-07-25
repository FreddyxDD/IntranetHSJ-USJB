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

final class ImportLegacyEgresos extends Command
{
    private const SOURCE_SYSTEM = 'egresos_legacy';

    private const SOURCE_SHA256 = '8C23AE92C4E706C07514D23C87AB258043F1363D071CAE1B8CF0D10501E5934D';

    private const EXPECTED_COUNTS = [
        'cie10' => 13023,
        'egresos' => 5872,
        'importaciones' => 16,
        'configuracion_constancias' => 1,
        'constancias_egresos' => 37,
        'constancias_egresos_historial' => 41,
    ];

    protected $signature = 'hsj:import-egresos
        {dump : Ruta absoluta de egresos_BD.sql}
        {--apply : Ejecutar la importación; sin esta opción solo valida}
        {--stage=all : all, cie10, egresos o constancias}';

    protected $description = 'Importa de forma idempotente el respaldo aprobado de Egresos a Intranet_HSJ.';

    public function handle(): int
    {
        $reader = new MySqlDumpReader((string) $this->argument('dump'));
        $this->assertApprovedSource($reader);
        $sourceCounts = $this->validateSourceCounts($reader);
        $stage = strtolower((string) $this->option('stage'));

        if (! in_array($stage, ['all', 'cie10', 'egresos', 'constancias'], true)) {
            $this->error('La etapa debe ser all, cie10, egresos o constancias.');

            return self::INVALID;
        }

        $this->table(
            ['Entidad', 'Conteo fuente', 'Esperado'],
            collect($sourceCounts)->map(
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
        $runId = $this->startRun($database, $reader, $stage, array_sum($sourceCounts));

        try {
            if (in_array($stage, ['all', 'cie10'], true)) {
                $this->importCie10($database, $reader);
                $this->assertTargetCount($database, 'catalogos.cie10', self::EXPECTED_COUNTS['cie10']);
            }

            if (in_array($stage, ['all', 'egresos'], true)) {
                $this->importEgresos($database, $reader);
                $this->assertTargetCount($database, 'egresos.egresos', self::EXPECTED_COUNTS['egresos']);
            }

            if (in_array($stage, ['all', 'constancias'], true)) {
                $this->importConstanciasTransaction($database, $reader);
                $this->validateConstancias($database);
            }

            $this->finishRun($database, $runId, 'completed', $sourceCounts);
        } catch (Throwable $exception) {
            $this->finishRun($database, $runId, 'failed', $sourceCounts, $exception);

            throw $exception;
        }

        $this->info('Importación completada y validada.');

        return self::SUCCESS;
    }

    private function assertApprovedSource(MySqlDumpReader $reader): void
    {
        if (! hash_equals(self::SOURCE_SHA256, strtoupper($reader->sha256()))) {
            throw new RuntimeException(
                'La huella SHA-256 no coincide con el respaldo de Egresos aprobado.'
            );
        }
    }

    /**
     * @return array<string, int>
     */
    private function validateSourceCounts(MySqlDumpReader $reader): array
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

    private function importCie10(
        ConnectionInterface $database,
        MySqlDumpReader $reader
    ): void {
        $this->info('Importando CIE-10...');

        $database->transaction(function () use ($database, $reader): void {
            $this->upsertRows(
                $database,
                'catalogos.cie10',
                $this->mapRows($reader->rows('cie10'), function (array $row): array {
                    return [
                        'source_system' => self::SOURCE_SYSTEM,
                        'source_id' => (int) $row['id'],
                        'codigo' => $row['codigo'],
                        'codigo_normalizado' => $row['codigo_normalizado'],
                        'descripcion' => $row['descripcion'],
                        'estado' => $row['estado'],
                        'cotejo_sexo' => $row['cotejo_sexo'],
                        'source_fingerprint' => LegacyRowFingerprint::make($row),
                        'source_created_at' => $row['creado_en'],
                        'source_updated_at' => $row['actualizado_en'],
                        'imported_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }),
                ['source_system', 'source_id'],
                100
            );
        });
    }

    private function importEgresos(
        ConnectionInterface $database,
        MySqlDumpReader $reader
    ): void {
        $this->info('Importando lotes históricos y egresos...');

        $database->transaction(function () use ($database, $reader): void {
            $identityMap = $this->identityMap($database);

            $this->upsertRows(
                $database,
                'egresos.importaciones',
                $this->mapRows(
                    $reader->rows('importaciones'),
                    function (array $row) use ($identityMap): array {
                        $actor = $identityMap[(string) ($row['usuario'] ?? '')] ?? null;

                        return [
                            'source_system' => self::SOURCE_SYSTEM,
                            'source_id' => (int) $row['id'],
                            'archivo' => $row['archivo'],
                            'actor_account_id' => $actor?->identity_account_id,
                            'actor_person_id' => $actor?->identity_person_id,
                            'actor_username' => $row['usuario'],
                            'actor_display_name' => null,
                            'insertados' => (int) $row['insertados'],
                            'omitidos' => (int) $row['omitidos'],
                            'errores' => (int) $row['errores'],
                            'detalle' => $this->normalizeJson($row['detalle']),
                            'file_sha256' => null,
                            'estado' => 'completed',
                            'source_created_at' => $row['created_at'],
                            'started_at' => null,
                            'finished_at' => $row['created_at'],
                            'created_at' => $row['created_at'],
                            'updated_at' => now(),
                        ];
                    }
                ),
                ['source_system', 'source_id'],
                50
            );

            $this->upsertRows(
                $database,
                'egresos.egresos',
                $this->mapRows($reader->rows('egresos'), function (array $row): array {
                    $mapped = [
                        'source_system' => self::SOURCE_SYSTEM,
                        'source_id' => (int) $row['id'],
                        'importacion_id' => null,
                    ];

                    foreach ($this->egresoColumns() as $column) {
                        $mapped[$column] = $row[$column];
                    }

                    $mapped['source_fingerprint'] = LegacyRowFingerprint::make($row);
                    $mapped['source_created_at'] = $row['created_at'];
                    $mapped['imported_at'] = now();
                    $mapped['created_at'] = $row['created_at'];
                    $mapped['updated_at'] = now();

                    return $mapped;
                }),
                ['source_system', 'source_id'],
                25
            );

            foreach ($reader->rows('configuracion_constancias') as $row) {
                $database->table('egresos.configuracion_constancias')->updateOrInsert(
                    ['id' => (int) $row['id']],
                    [
                        'iniciales_director' => $row['iniciales_director'],
                        'iniciales_jefe' => $row['iniciales_jefe'],
                        'iniciales_ccp' => $row['iniciales_ccp'],
                        'nombre_director' => $row['nombre_director'],
                        'nombre_jefe' => $row['nombre_jefe'],
                        'cargo_director' => $row['cargo_director'],
                        'cargo_jefe' => $row['cargo_jefe'],
                        'observacion' => $row['observacion'],
                        'updated_by_username' => $row['actualizado_por'],
                        'source_created_at' => $row['creado_en'],
                        'source_updated_at' => $row['actualizado_en'],
                        'created_at' => $row['creado_en'],
                        'updated_at' => now(),
                    ]
                );
            }
        });
    }

    private function importConstanciasTransaction(
        ConnectionInterface $database,
        MySqlDumpReader $reader
    ): void {
        $this->info('Importando constancias e historial en una transacción...');

        $database->transaction(function () use ($database, $reader): void {
            $egresoMap = $database->table('egresos.egresos')
                ->where('source_system', self::SOURCE_SYSTEM)
                ->pluck('id', 'source_id');
            $identityById = $this->identityMapBySourceId($database);

            $this->upsertRows(
                $database,
                'egresos.constancias',
                $this->mapRows(
                    $reader->rows('constancias_egresos'),
                    function (array $row) use ($egresoMap, $identityById): array {
                        $egresoId = $row['egreso_id'] !== null
                            ? $egresoMap->get((int) $row['egreso_id'])
                            : null;

                        if ($row['egreso_id'] !== null && ! $egresoId) {
                            throw new RuntimeException(
                                "No se resolvió el egreso legado {$row['egreso_id']}."
                            );
                        }

                        $issuer = $identityById[(string) ($row['emitido_por_id'] ?? '')] ?? null;
                        $cancelledBy = $identityById[(string) ($row['anulado_por_id'] ?? '')] ?? null;

                        return [
                            'source_system' => self::SOURCE_SYSTEM,
                            'source_id' => (int) $row['id'],
                            'egreso_id' => $egresoId,
                            'numero' => (int) $row['numero'],
                            'anio' => (int) $row['anio'],
                            'sequence_owner_key' => $issuer?->identity_account_id
                                ? 'account:'.$issuer->identity_account_id
                                : 'legacy:egresos:'.($row['emitido_por_id'] ?? $row['emitido_por_usuario']),
                            'issuer_account_id' => $issuer?->identity_account_id,
                            'issuer_person_id' => $issuer?->identity_person_id,
                            'issuer_legacy_user_id' => $this->nullableInt($row['emitido_por_id']),
                            'issuer_username' => $row['emitido_por_usuario'],
                            'issuer_display_name' => null,
                            'numhc' => $row['numhc'],
                            'doc_iden' => $row['doc_iden'],
                            'paciente' => $row['paciente'],
                            'nombres' => $row['nombres'],
                            'apellidos' => $row['apellidos'],
                            'fecing' => $row['fecing'],
                            'fecegr' => $row['fecegr'],
                            'ups' => $row['ups'],
                            'servicio' => $row['servicio'],
                            'condicion' => $row['condicion'],
                            'financia' => $row['financia'],
                            'coddiag1' => $row['coddiag1'],
                            'descdiag1' => $row['descdiag1'],
                            'coddiag2' => $row['coddiag2'],
                            'descdiag2' => $row['descdiag2'],
                            'coddiag3' => $row['coddiag3'],
                            'descdiag3' => $row['descdiag3'],
                            'coddiag4' => $row['coddiag4'],
                            'descdiag4' => $row['descdiag4'],
                            'iniciales_director' => $row['iniciales_director'],
                            'iniciales_jefe' => $row['iniciales_jefe'],
                            'iniciales_ccp' => $row['iniciales_ccp'],
                            'sigla_servicio' => $row['sigla_servicio'],
                            'nombre_pdf' => $row['nombre_pdf'],
                            'observacion' => $row['observacion'],
                            'estado' => $row['estado'],
                            'motivo_anulacion' => $row['motivo_anulacion'],
                            'cancelled_by_account_id' => $cancelledBy?->identity_account_id,
                            'cancelled_by_person_id' => $cancelledBy?->identity_person_id,
                            'cancelled_by_legacy_user_id' => $this->nullableInt($row['anulado_por_id']),
                            'cancelled_by_username' => $row['anulado_por_usuario'],
                            'cancelled_by_display_name' => null,
                            'cancelled_at' => $row['anulado_en'],
                            'source_fingerprint' => LegacyRowFingerprint::make($row),
                            'source_created_at' => $row['creado_en'],
                            'source_updated_at' => $row['actualizado_en'],
                            'imported_at' => now(),
                            'created_at' => $row['creado_en'],
                            'updated_at' => now(),
                        ];
                    }
                ),
                ['source_system', 'source_id'],
                25
            );

            $constanciaMap = $database->table('egresos.constancias')
                ->where('source_system', self::SOURCE_SYSTEM)
                ->pluck('id', 'source_id');

            $this->upsertRows(
                $database,
                'egresos.constancia_historial',
                $this->mapRows(
                    $reader->rows('constancias_egresos_historial'),
                    function (array $row) use ($constanciaMap, $identityById): array {
                        $constanciaId = $constanciaMap->get((int) $row['constancia_id']);

                        if (! $constanciaId) {
                            throw new RuntimeException(
                                "No se resolvió la constancia legada {$row['constancia_id']}."
                            );
                        }

                        $actor = $identityById[(string) ($row['usuario_id'] ?? '')] ?? null;

                        return [
                            'source_system' => self::SOURCE_SYSTEM,
                            'source_id' => (int) $row['id'],
                            'constancia_id' => $constanciaId,
                            'accion' => $row['accion'],
                            'descripcion' => $row['descripcion'],
                            'datos_anteriores' => $this->normalizeJson($row['datos_anteriores']),
                            'datos_nuevos' => $this->normalizeJson($row['datos_nuevos']),
                            'actor_account_id' => $actor?->identity_account_id,
                            'actor_person_id' => $actor?->identity_person_id,
                            'actor_legacy_user_id' => $this->nullableInt($row['usuario_id']),
                            'actor_username' => $row['usuario'],
                            'actor_display_name' => null,
                            'ip' => $row['ip'],
                            'source_fingerprint' => LegacyRowFingerprint::make($row),
                            'occurred_at' => $row['creado_en'],
                            'imported_at' => now(),
                            'created_at' => $row['creado_en'],
                            'updated_at' => now(),
                        ];
                    }
                ),
                ['source_system', 'source_id'],
                50
            );

            $correlatives = $database->table('egresos.constancias')
                ->selectRaw('sequence_owner_key, anio, MAX(numero) AS ultimo_numero')
                ->groupBy('sequence_owner_key', 'anio')
                ->get();

            foreach ($correlatives as $correlative) {
                $database->table('egresos.correlativos')->updateOrInsert(
                    [
                        'sequence_owner_key' => $correlative->sequence_owner_key,
                        'anio' => $correlative->anio,
                    ],
                    [
                        'ultimo_numero' => $correlative->ultimo_numero,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }, 3);
    }

    private function validateConstancias(ConnectionInterface $database): void
    {
        $this->assertTargetCount(
            $database,
            'egresos.constancias',
            self::EXPECTED_COUNTS['constancias_egresos']
        );
        $this->assertTargetCount(
            $database,
            'egresos.constancia_historial',
            self::EXPECTED_COUNTS['constancias_egresos_historial']
        );

        $orphanConstancias = $database->table('egresos.constancias as c')
            ->leftJoin('egresos.egresos as e', 'e.id', '=', 'c.egreso_id')
            ->whereNotNull('c.egreso_id')
            ->whereNull('e.id')
            ->count();
        $orphanHistory = $database->table('egresos.constancia_historial as h')
            ->leftJoin('egresos.constancias as c', 'c.id', '=', 'h.constancia_id')
            ->whereNull('c.id')
            ->count();
        $withoutHistory = $database->table('egresos.constancias as c')
            ->leftJoin('egresos.constancia_historial as h', 'h.constancia_id', '=', 'c.id')
            ->whereNull('h.id')
            ->count();

        if ($orphanConstancias + $orphanHistory + $withoutHistory !== 0) {
            throw new RuntimeException(
                "Validación relacional fallida: constancias huérfanas={$orphanConstancias}, "
                ."historial huérfano={$orphanHistory}, sin historial={$withoutHistory}."
            );
        }
    }

    private function assertTargetCount(
        ConnectionInterface $database,
        string $table,
        int $expected
    ): void {
        $actual = $database->table($table)
            ->where('source_system', self::SOURCE_SYSTEM)
            ->count();

        if ($actual !== $expected) {
            throw new RuntimeException(
                "Conteo destino inválido para {$table}: {$actual}; se esperaban {$expected}."
            );
        }
    }

    /**
     * @param  iterable<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     */
    private function upsertRows(
        ConnectionInterface $database,
        string $table,
        iterable $rows,
        array $uniqueBy,
        int $chunkSize
    ): void {
        $chunk = [];

        foreach ($rows as $row) {
            $chunk[] = $row;

            if (count($chunk) >= $chunkSize) {
                $this->upsertChunk($database, $table, $chunk, $uniqueBy);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->upsertChunk($database, $table, $chunk, $uniqueBy);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $chunk
     * @param  list<string>  $uniqueBy
     */
    private function upsertChunk(
        ConnectionInterface $database,
        string $table,
        array $chunk,
        array $uniqueBy
    ): void {
        $updateColumns = array_values(array_diff(array_keys($chunk[0]), [
            ...$uniqueBy,
            'created_at',
        ]));

        $database->table($table)->upsert($chunk, $uniqueBy, $updateColumns);
    }

    /**
     * @param  iterable<array<string, string|null>>  $rows
     * @return iterable<array<string, mixed>>
     */
    private function mapRows(iterable $rows, callable $mapper): iterable
    {
        foreach ($rows as $row) {
            yield $mapper($row);
        }
    }

    /**
     * @return list<string>
     */
    private function egresoColumns(): array
    {
        return [
            'renipress', 'e_ubig', 'e_cdpto', 'e_cprov', 'e_cdist',
            'cod_disa', 'cod_red', 'cod_mred', 'numhc', 'nomb', 'apell',
            'doc_iden', 'etnia', 'sexo', 'edad', 'tipoedad', 'ubigeo',
            'cdpto', 'cprov', 'cdist', 'fecing', 'fecegr', 'totalest',
            'ups', 'condicion', 'financia', 'coddiag1', 'coddiag2',
            'coddiag3', 'coddiag4', 'cemorb1', 'cemorb2', 'codcpt1',
            'codcpt2', 'codcpt3', 'codcpt4', 'estadio', 'valor_t',
            'valor_n', 'valor_m', 'tratamien', 'prof_parto', 'fecparto',
            'rnvivo', 'rnmuerto', 'codpsal', 'fechareg', 'estado',
        ];
    }

    /**
     * @return array<string, object>
     */
    private function identityMap(ConnectionInterface $database): array
    {
        $result = [];
        $rows = $database->table('staging.identity_user_map')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('source_table', 'usuarios')
            ->get();

        foreach ($rows as $row) {
            if ($row->source_username) {
                $result[(string) $row->source_username] = $row;
            }
        }

        return $result;
    }

    /**
     * @return array<string, object>
     */
    private function identityMapBySourceId(ConnectionInterface $database): array
    {
        $result = [];
        $rows = $database->table('staging.identity_user_map')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('source_table', 'usuarios')
            ->get();

        foreach ($rows as $row) {
            $result[(string) $row->source_user_id] = $row;
        }

        return $result;
    }

    private function normalizeJson(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        json_decode($value);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $value;
        }

        return json_encode(
            ['legacy_value' => $value],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function nullableInt(?string $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * @param  array<string, int>  $sourceCounts
     */
    private function startRun(
        ConnectionInterface $database,
        MySqlDumpReader $reader,
        string $stage,
        int $sourceCount
    ): int {
        return (int) $database->table('staging.import_runs')->insertGetId([
            'run_uuid' => (string) Str::uuid(),
            'source_system' => self::SOURCE_SYSTEM,
            'source_file_name' => basename((string) $this->argument('dump')),
            'source_file_sha256' => strtoupper($reader->sha256()),
            'entity' => 'egresos:'.$stage,
            'status' => 'running',
            'dry_run' => false,
            'source_count' => $sourceCount,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, int>  $sourceCounts
     */
    private function finishRun(
        ConnectionInterface $database,
        int $runId,
        string $status,
        array $sourceCounts,
        ?Throwable $exception = null
    ): void {
        $summary = [
            'source_counts' => $sourceCounts,
            'target_counts' => [
                'cie10' => $database->table('catalogos.cie10')
                    ->where('source_system', self::SOURCE_SYSTEM)->count(),
                'egresos' => $database->table('egresos.egresos')
                    ->where('source_system', self::SOURCE_SYSTEM)->count(),
                'constancias' => $database->table('egresos.constancias')
                    ->where('source_system', self::SOURCE_SYSTEM)->count(),
                'historial' => $database->table('egresos.constancia_historial')
                    ->where('source_system', self::SOURCE_SYSTEM)->count(),
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
