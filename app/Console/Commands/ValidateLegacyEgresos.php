<?php

namespace App\Console\Commands;

use App\Services\Migration\LegacyRowFingerprint;
use App\Services\Migration\MySqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ValidateLegacyEgresos extends Command
{
    private const SOURCE_SYSTEM = 'egresos_legacy';

    private const SOURCE_SHA256 = '8C23AE92C4E706C07514D23C87AB258043F1363D071CAE1B8CF0D10501E5934D';

    private const TABLES = [
        'cie10' => ['catalogos.cie10', 13023],
        'egresos' => ['egresos.egresos', 5872],
        'constancias_egresos' => ['egresos.constancias', 37],
        'constancias_egresos_historial' => ['egresos.constancia_historial', 41],
    ];

    protected $signature = 'hsj:validate-egresos
        {dump : Ruta absoluta de egresos_BD.sql}';

    protected $description = 'Compara conteos, huellas y relaciones de la migración de Egresos.';

    public function handle(): int
    {
        $reader = new MySqlDumpReader((string) $this->argument('dump'));

        if (! hash_equals(self::SOURCE_SHA256, strtoupper($reader->sha256()))) {
            throw new RuntimeException('El respaldo no coincide con la fuente aprobada.');
        }

        $database = DB::connection();
        $rows = [];
        $failed = false;

        foreach (self::TABLES as $sourceTable => [$targetTable, $expected]) {
            $result = $this->compareFingerprints(
                $database,
                $reader,
                $sourceTable,
                $targetTable
            );
            $valid = $result['source_count'] === $expected
                && $result['target_count'] === $expected
                && $result['missing'] === 0
                && $result['extra'] === 0
                && $result['mismatched'] === 0;
            $failed = $failed || ! $valid;
            $rows[] = [
                $sourceTable,
                $result['source_count'],
                $result['target_count'],
                $result['missing'],
                $result['extra'],
                $result['mismatched'],
                $valid ? 'OK' : 'ERROR',
            ];
        }

        $this->table(
            ['Entidad', 'Fuente', 'Destino', 'Faltan', 'Sobran', 'Huella distinta', 'Estado'],
            $rows
        );

        $relations = $this->relationshipMetrics($database);
        $this->table(
            ['Control relacional', 'Incidencias'],
            collect($relations)->map(
                fn (int $count, string $name): array => [$name, $count]
            )->values()->all()
        );

        if (array_sum($relations) !== 0) {
            $failed = true;
        }

        if ($failed) {
            $this->error('La validación de Egresos detectó diferencias.');

            return self::FAILURE;
        }

        $this->info('Validación completada: conteos, huellas y relaciones coinciden.');

        return self::SUCCESS;
    }

    /**
     * @return array{source_count: int, target_count: int, missing: int, extra: int, mismatched: int}
     */
    private function compareFingerprints(
        ConnectionInterface $database,
        MySqlDumpReader $reader,
        string $sourceTable,
        string $targetTable
    ): array {
        $source = [];

        foreach ($reader->rows($sourceTable) as $row) {
            $source[(int) $row['id']] = LegacyRowFingerprint::make($row);
        }

        $target = $database->table($targetTable)
            ->where('source_system', self::SOURCE_SYSTEM)
            ->pluck('source_fingerprint', 'source_id')
            ->mapWithKeys(
                fn ($fingerprint, $sourceId): array => [
                    (int) $sourceId => trim((string) $fingerprint),
                ]
            )
            ->all();
        $missing = count(array_diff_key($source, $target));
        $extra = count(array_diff_key($target, $source));
        $mismatched = 0;

        foreach (array_intersect_key($source, $target) as $sourceId => $fingerprint) {
            if (! hash_equals($fingerprint, $target[$sourceId])) {
                $mismatched++;
            }
        }

        return [
            'source_count' => count($source),
            'target_count' => count($target),
            'missing' => $missing,
            'extra' => $extra,
            'mismatched' => $mismatched,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function relationshipMetrics(ConnectionInterface $database): array
    {
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
        $duplicateSequences = DB::query()
            ->fromSub(
                $database->table('egresos.constancias')
                    ->select([
                        'sequence_owner_key',
                        'anio',
                        'numero',
                    ])
                    ->selectRaw('COUNT(*) AS total')
                    ->groupBy('sequence_owner_key', 'anio', 'numero')
                    ->havingRaw('COUNT(*) > 1'),
                'duplicates'
            )
            ->count();

        return [
            'Constancias sin egreso' => $orphanConstancias,
            'Historial sin constancia' => $orphanHistory,
            'Constancias sin historial' => $withoutHistory,
            'Correlativos duplicados' => $duplicateSequences,
        ];
    }
}
