<?php

namespace App\Services\Egresos;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SighPatientSource
{
    public function connection(): ConnectionInterface
    {
        return DB::connection((string) config('egresos.patient_connection', 'sigh_local'));
    }

    public function sourceCode(): string
    {
        return (string) config('egresos.patient_source_code', 'sigh_202607_local');
    }

    /**
     * @param  list<string|int>  $histories
     * @return Collection<string, object>
     */
    public function byHistories(array $histories): Collection
    {
        $values = collect($histories)
            ->filter(fn ($value) => filter_var($value, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return collect();
        }

        return $this->connection()
            ->table('dbo.Pacientes')
            ->whereIn('NroHistoriaClinica', $values->all())
            ->get([
                'IdPaciente',
                'NroHistoriaClinica',
                'NroDocumento',
                'IdDocIdentidad',
                'PrimerNombre',
                'SegundoNombre',
                'TercerNombre',
                'ApellidoPaterno',
                'ApellidoMaterno',
            ])
            ->keyBy(fn ($patient): string => (string) $patient->NroHistoriaClinica);
    }

    public function search(string $text, int $limit = 20): Collection
    {
        $text = trim($text);
        if ($text === '') {
            return collect();
        }

        return $this->connection()
            ->table('dbo.Pacientes as p')
            ->leftJoin('dbo.TiposDocIdentidad as t', 't.IdDocIdentidad', '=', 'p.IdDocIdentidad')
            ->where(function ($query) use ($text): void {
                $query->where('p.NroDocumento', $text);
                if (ctype_digit($text)) {
                    $query->orWhere('p.NroHistoriaClinica', (int) $text);
                }
            })
            ->orderByDesc('p.IdPaciente')
            ->limit($limit)
            ->get([
                'p.IdPaciente',
                'p.NroHistoriaClinica',
                'p.NroDocumento',
                'p.IdDocIdentidad',
                't.Descripcion as TipoDocumento',
                'p.PrimerNombre',
                'p.SegundoNombre',
                'p.TercerNombre',
                'p.ApellidoPaterno',
                'p.ApellidoMaterno',
            ]);
    }
}
