<?php

namespace App\Console\Commands;

use App\Services\Migration\MySqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReconcileLegacyIdentities extends Command
{
    private const EGRESOS_SHA256 = '8C23AE92C4E706C07514D23C87AB258043F1363D071CAE1B8CF0D10501E5934D';

    private const HSJ_DATA_SHA256 = '2D663F528F21E236DC7EDBB9FB7521761081FF8F63A59191681864E02E66C8C6';

    protected $signature = 'hsj:reconcile-legacy-identities
        {egresos_dump : Ruta absoluta de egresos_BD.sql}
        {hsj_dump : Ruta absoluta de HSJ_DATA.sql}
        {--apply : Guardar los resultados en el esquema staging}';

    protected $description = 'Concilia usuarios y personal legado con HSJ_Identity sin crear cuentas automáticamente.';

    public function handle(): int
    {
        $egresos = new MySqlDumpReader((string) $this->argument('egresos_dump'));
        $hsj = new MySqlDumpReader((string) $this->argument('hsj_dump'));

        $this->assertSource($egresos, self::EGRESOS_SHA256, 'egresos_BD.sql');
        $this->assertSource($hsj, self::HSJ_DATA_SHA256, 'HSJ_DATA.sql');

        $identity = DB::connection('identity');
        $operational = DB::connection();
        $accountKeys = $this->centralAccountKeys($identity);
        $userRows = $this->buildUserMappings($egresos, $hsj, $accountKeys);
        $personnelRows = $this->buildPersonnelMappings($hsj, $identity);

        $this->showSummary('Usuarios', $userRows);
        $this->showSummary('Personal', $personnelRows);

        if (! $this->option('apply')) {
            $this->warn('Simulación completada. No se modificó ninguna base de datos.');

            return self::SUCCESS;
        }

        $operational->transaction(function () use (
            $operational,
            $userRows,
            $personnelRows
        ): void {
            foreach ($userRows as $row) {
                $operational->table('staging.identity_user_map')->updateOrInsert(
                    [
                        'source_system' => $row['source_system'],
                        'source_table' => $row['source_table'],
                        'source_user_id' => $row['source_user_id'],
                    ],
                    $row
                );
            }

            foreach ($personnelRows as $row) {
                $operational->table('staging.personnel_map')->updateOrInsert(
                    [
                        'source_system' => $row['source_system'],
                        'source_table' => $row['source_table'],
                        'source_personnel_id' => $row['source_personnel_id'],
                    ],
                    $row
                );
            }
        });

        $this->info('Conciliación guardada en staging. No se crearon ni modificaron cuentas centrales.');

        return self::SUCCESS;
    }

    private function assertSource(
        MySqlDumpReader $reader,
        string $expectedHash,
        string $label
    ): void {
        $actualHash = strtoupper($reader->sha256());

        if (! hash_equals($expectedHash, $actualHash)) {
            throw new RuntimeException(
                "La huella SHA-256 de {$label} no coincide con la fuente aprobada."
            );
        }
    }

    /**
     * @return array<string, list<object>>
     */
    private function centralAccountKeys(ConnectionInterface $identity): array
    {
        $keys = [];
        $accounts = $identity->table('access_accounts')
            ->get(['id', 'person_id', 'username', 'email']);

        foreach ($accounts as $account) {
            foreach ([$account->username, $account->email] as $identifier) {
                $key = $this->normalizeIdentifier($identifier);

                if ($key !== '') {
                    $keys[$key][$account->id] = $account;
                }
            }
        }

        return array_map(
            static fn (array $accounts): array => array_values($accounts),
            $keys
        );
    }

    /**
     * @param  array<string, list<object>>  $accountKeys
     * @return list<array<string, mixed>>
     */
    private function buildUserMappings(
        MySqlDumpReader $egresos,
        MySqlDumpReader $hsj,
        array $accountKeys
    ): array {
        $sources = [
            [$egresos, 'egresos_legacy', 'usuarios', 'id', 'usuario'],
            [$hsj, 'hospital_ueei', 'cuentas_ueei', 'id', 'correo'],
            [$hsj, 'hospital_ueei', 'cuentas_cirugias', 'id', 'usuario'],
            [$hsj, 'hospital_ueei', 'cuentas_citas_admin', 'id', 'usuario'],
            [$hsj, 'hospital_ueei', 'usuarios_uvi', 'id', 'usuario'],
        ];
        $result = [];

        foreach ($sources as [$reader, $system, $table, $idColumn, $userColumn]) {
            foreach ($reader->rows($table) as $source) {
                $username = trim((string) ($source[$userColumn] ?? ''));
                $matches = $accountKeys[$this->normalizeIdentifier($username)] ?? [];
                $match = count($matches) === 1 ? $matches[0] : null;

                $result[] = [
                    'source_system' => $system,
                    'source_table' => $table,
                    'source_user_id' => (string) $source[$idColumn],
                    'source_username' => $username !== '' ? $username : null,
                    'identity_account_id' => $match?->id,
                    'identity_person_id' => $match?->person_id,
                    'match_method' => $match ? 'identifier_exact' : (
                        count($matches) > 1 ? 'identifier_ambiguous' : 'no_match'
                    ),
                    'review_status' => $match ? 'matched' : (
                        count($matches) > 1 ? 'ambiguous' : 'pending'
                    ),
                    'notes' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildPersonnelMappings(
        MySqlDumpReader $hsj,
        ConnectionInterface $identity
    ): array {
        $sourceRows = iterator_to_array($hsj->rows('personal_medico'));
        $documents = collect($sourceRows)
            ->pluck('dni')
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $people = $identity->table('people')
            ->whereIn('document_number', $documents)
            ->get(['id', 'document_number'])
            ->groupBy('document_number');
        $records = $identity->table('personnel_records')
            ->whereIn('document_number', $documents)
            ->get(['id', 'document_number', 'is_active'])
            ->groupBy('document_number');
        $personIds = $people->flatten()->pluck('id');
        $assignments = $identity->table('personnel_assignments')
            ->whereIn('person_id', $personIds)
            ->get(['id', 'person_id', 'source_record_id', 'is_active'])
            ->groupBy('person_id');
        $result = [];

        foreach ($sourceRows as $source) {
            $document = trim((string) ($source['dni'] ?? ''));
            $personMatches = $document !== ''
                ? ($people->get($document) ?? collect())
                : collect();
            $mapping = $this->resolvePersonnelMatch(
                $document,
                $personMatches,
                $records->get($document) ?? collect(),
                $assignments
            );

            $result[] = [
                'source_system' => 'hospital_ueei',
                'source_table' => 'personal_medico',
                'source_personnel_id' => (string) $source['id'],
                'source_document_number' => $document !== '' ? $document : null,
                'source_display_name' => $source['apellidos_nombres'] ?: null,
                'identity_person_id' => $mapping['person_id'],
                'identity_personnel_record_id' => $mapping['record_id'],
                'identity_assignment_id' => $mapping['assignment_id'],
                'match_method' => $mapping['method'],
                'review_status' => $mapping['status'],
                'notes' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, object>  $people
     * @param  Collection<int, object>  $records
     * @param  Collection<int|string, Collection<int, object>>  $assignments
     * @return array{person_id: mixed, record_id: mixed, assignment_id: mixed, method: string, status: string}
     */
    private function resolvePersonnelMatch(
        string $document,
        Collection $people,
        Collection $records,
        Collection $assignments
    ): array {
        $empty = [
            'person_id' => null,
            'record_id' => null,
            'assignment_id' => null,
        ];

        if ($document === '') {
            return $empty + ['method' => 'missing_document', 'status' => 'pending'];
        }

        if ($people->isEmpty()) {
            return $empty + ['method' => 'no_match', 'status' => 'pending'];
        }

        if ($people->count() !== 1) {
            return $empty + ['method' => 'document_ambiguous', 'status' => 'ambiguous'];
        }

        $person = $people->first();
        $activeAssignments = ($assignments->get($person->id) ?? collect())
            ->filter(fn (object $assignment): bool => (bool) $assignment->is_active)
            ->values();

        if ($activeAssignments->count() > 1) {
            return [
                'person_id' => $person->id,
                'record_id' => null,
                'assignment_id' => null,
                'method' => 'active_assignment_ambiguous',
                'status' => 'ambiguous',
            ];
        }

        if ($activeAssignments->count() === 1) {
            $assignment = $activeAssignments->first();

            return [
                'person_id' => $person->id,
                'record_id' => $assignment->source_record_id,
                'assignment_id' => $assignment->id,
                'method' => 'document_active_assignment',
                'status' => 'matched',
            ];
        }

        if ($records->count() === 1) {
            return [
                'person_id' => $person->id,
                'record_id' => $records->first()->id,
                'assignment_id' => null,
                'method' => 'document_single_record',
                'status' => 'matched',
            ];
        }

        return [
            'person_id' => $person->id,
            'record_id' => null,
            'assignment_id' => null,
            'method' => 'record_ambiguous',
            'status' => 'ambiguous',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function showSummary(string $label, array $rows): void
    {
        $statuses = collect($rows)->countBy('review_status');

        $this->table(
            ['Entidad', 'Total', 'Coincidencias', 'Pendientes', 'Ambiguos'],
            [[
                $label,
                count($rows),
                $statuses->get('matched', 0),
                $statuses->get('pending', 0),
                $statuses->get('ambiguous', 0),
            ]]
        );
    }

    private function normalizeIdentifier(mixed $identifier): string
    {
        $identifier = mb_strtolower(trim((string) $identifier));

        if (str_contains($identifier, '@')) {
            $identifier = explode('@', $identifier, 2)[0];
        }

        return $identifier;
    }
}
