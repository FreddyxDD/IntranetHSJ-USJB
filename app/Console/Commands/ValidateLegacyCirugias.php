<?php

namespace App\Console\Commands;

use App\Services\Migration\LegacyRowFingerprint;
use App\Services\Migration\MySqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ValidateLegacyCirugias extends Command
{
    private const SOURCE_SYSTEM = 'hospital_ueei';

    private const SOURCE_SHA256 = '2D663F528F21E236DC7EDBB9FB7521761081FF8F63A59191681864E02E66C8C6';

    private const PARTICIPANT_COLUMNS = [
        'cirujano_1',
        'cirujano_2',
        'anestesiologo',
        'enfermera_instrumentista',
        'anestesiologo_recuperacion',
        'enfermera_recuperacion',
        'tecnico_enfermeria_1',
        'tecnico_enfermeria_2',
    ];

    protected $signature = 'hsj:validate-cirugias
        {dump : Ruta absoluta de HSJ_DATA.sql}';

    protected $description = 'Compara conteos, huellas y relaciones de la migración de Cirugías.';

    public function handle(): int
    {
        $reader = new MySqlDumpReader((string) $this->argument('dump'));

        if (! hash_equals(self::SOURCE_SHA256, strtoupper($reader->sha256()))) {
            throw new RuntimeException('El respaldo no coincide con HSJ_DATA.sql aprobado.');
        }

        $database = DB::connection();
        $surgeries = $this->compare(
            $database,
            $reader,
            'cirugias',
            'cirugias.cirugias'
        );
        $imports = $this->compare(
            $database,
            $reader,
            'historial_importaciones_cirugias',
            'cirugias.importaciones'
        );
        $expectedParticipants = $this->expectedParticipants($reader);
        $actualParticipants = $database->table('cirugias.participantes')->count();
        $orphanParticipants = $database->table('cirugias.participantes as p')
            ->leftJoin('cirugias.cirugias as c', 'c.id', '=', 'p.cirugia_id')
            ->whereNull('c.id')
            ->count();
        $participantStatuses = $database->table('cirugias.participantes')
            ->selectRaw('match_status, COUNT(*) total')
            ->groupBy('match_status')
            ->orderBy('match_status')
            ->get();

        $this->table(
            ['Entidad', 'Fuente', 'Destino', 'Faltan', 'Sobran', 'Huella distinta'],
            [
                ['cirugias', ...array_values($surgeries)],
                ['historial_importaciones_cirugias', ...array_values($imports)],
            ]
        );
        $this->table(
            ['Control', 'Resultado'],
            [
                ['Participantes esperados', $expectedParticipants],
                ['Participantes importados', $actualParticipants],
                ['Participantes huérfanos', $orphanParticipants],
            ]
        );
        $this->table(
            ['Estado de conciliación', 'Participantes'],
            $participantStatuses->map(
                fn (object $row): array => [$row->match_status, $row->total]
            )->all()
        );

        $failed = $surgeries !== [
            'source' => 798,
            'target' => 798,
            'missing' => 0,
            'extra' => 0,
            'mismatched' => 0,
        ] || $imports !== [
            'source' => 11,
            'target' => 11,
            'missing' => 0,
            'extra' => 0,
            'mismatched' => 0,
        ] || $expectedParticipants !== $actualParticipants
            || $orphanParticipants !== 0;

        if ($failed) {
            $this->error('La validación de Cirugías detectó diferencias.');

            return self::FAILURE;
        }

        $this->info('Validación completada: Cirugías coincide con la fuente aprobada.');

        return self::SUCCESS;
    }

    /**
     * @return array{source: int, target: int, missing: int, extra: int, mismatched: int}
     */
    private function compare(
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
        $mismatched = 0;

        foreach (array_intersect_key($source, $target) as $id => $fingerprint) {
            if (! hash_equals($fingerprint, $target[$id])) {
                $mismatched++;
            }
        }

        return [
            'source' => count($source),
            'target' => count($target),
            'missing' => count(array_diff_key($source, $target)),
            'extra' => count(array_diff_key($target, $source)),
            'mismatched' => $mismatched,
        ];
    }

    private function expectedParticipants(MySqlDumpReader $reader): int
    {
        $count = 0;

        foreach ($reader->rows('cirugias') as $row) {
            foreach (self::PARTICIPANT_COLUMNS as $column) {
                if (trim((string) ($row[$column] ?? '')) !== '') {
                    $count++;
                }
            }
        }

        return $count;
    }
}
