<?php

namespace App\Services\Egresos;

use App\Models\Egresos\Cie10;
use App\Models\Egresos\Egreso;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ConstanciaEpisodeSelection
{
    public const MAX_EPISODES = 10;

    public function resolve(array $episodeIds, bool $lock = false): EloquentCollection
    {
        $ids = collect($episodeIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty() || $ids->count() > self::MAX_EPISODES) {
            throw ValidationException::withMessages([
                'egreso_ids' => 'Seleccione entre 1 y '.self::MAX_EPISODES.' episodios.',
            ]);
        }

        $query = Egreso::query()->whereIn('id', $ids);
        if ($lock) {
            $query->lockForUpdate();
        }

        $episodes = $query->get()
            ->sortBy([
                ['fecing', 'asc'],
                ['fecegr', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        if ($episodes->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'egreso_ids' => 'Uno o más episodios seleccionados ya no se encuentran disponibles.',
            ]);
        }

        if ($episodes->map(fn (Egreso $episode): string => $this->patientKey($episode))->unique()->count() !== 1) {
            throw ValidationException::withMessages([
                'egreso_ids' => 'Todos los episodios deben pertenecer al mismo paciente.',
            ]);
        }

        return $episodes;
    }

    public function snapshots(Collection $episodes): array
    {
        $diagnoses = $this->diagnosisDescriptions($episodes);

        return $episodes->values()->map(function (Egreso $episode, int $index) use ($diagnoses): array {
            $snapshot = [
                'egreso_id' => $episode->id,
                'posicion' => $index + 1,
                'source_system' => $episode->source_system ?: 'intranet_hsj',
                'numhc' => $episode->numhc,
                'doc_tipo_id' => $episode->doc_tipo_id,
                'doc_iden' => $episode->documento,
                'paciente' => $episode->paciente,
                'nombres' => $episode->nomb,
                'apellidos' => $episode->apell,
                'fecing' => $episode->fecing,
                'fecegr' => $episode->fecegr,
                'ups' => $episode->ups,
                'servicio' => $episode->ups,
                'condicion' => $episode->condicion,
                'financia' => $episode->financia,
            ];

            foreach (range(1, 4) as $position) {
                $code = trim((string) $episode->getAttribute("coddiag{$position}"));
                $normalized = strtoupper(str_replace('.', '', $code));
                $snapshot["coddiag{$position}"] = $code !== '' ? $code : null;
                $snapshot["descdiag{$position}"] = $normalized !== ''
                    ? ($diagnoses[$normalized] ?? null)
                    : null;
            }

            return $snapshot;
        })->all();
    }

    public function preview(Collection $episodes, int $number, int $year): array
    {
        $snapshots = collect($this->snapshots($episodes));
        $first = $snapshots->first();
        $serviceCodes = $snapshots
            ->pluck('servicio')
            ->map(fn (?string $service): string => $this->serviceCode((string) $service))
            ->unique();

        return [
            'number' => $number,
            'year' => $year,
            'correlative' => sprintf(
                'N° %04d-%d-HSJ-%s',
                $number,
                $year,
                $serviceCodes->count() === 1 ? $serviceCodes->first() : 'GEN'
            ),
            'patient' => $first['paciente'],
            'history' => $first['numhc'],
            'document' => $first['doc_iden'],
            'episode_count' => $snapshots->count(),
            'episodes' => $snapshots->map(fn (array $snapshot): array => [
                ...$snapshot,
                'fecing' => $snapshot['fecing']?->format('Y-m-d'),
                'fecegr' => $snapshot['fecegr']?->format('Y-m-d'),
                'diagnosticos' => collect(range(1, 4))
                    ->map(fn (int $position): ?array => $snapshot["coddiag{$position}"]
                        ? [
                            'codigo' => $snapshot["coddiag{$position}"],
                            'descripcion' => $snapshot["descdiag{$position}"],
                        ]
                        : null)
                    ->filter()
                    ->values()
                    ->all(),
            ])->values(),
        ];
    }

    private function diagnosisDescriptions(Collection $episodes): array
    {
        $codes = $episodes
            ->flatMap(fn (Egreso $episode): array => collect(range(1, 4))
                ->map(fn (int $position): string => trim((string) $episode->getAttribute("coddiag{$position}")))
                ->all())
            ->filter()
            ->map(fn (string $code): string => strtoupper(str_replace('.', '', $code)))
            ->unique();

        return Cie10::query()
            ->whereIn('codigo_normalizado', $codes)
            ->pluck('descripcion', 'codigo_normalizado')
            ->all();
    }

    private function patientKey(Egreso $episode): string
    {
        $history = trim((string) $episode->numhc);
        if ($history !== '') {
            return 'hc:'.mb_strtolower($history);
        }

        $document = trim((string) $episode->documento);

        return $document !== ''
            ? 'doc:'.mb_strtolower($document)
            : 'episode:'.$episode->id;
    }

    private function serviceCode(string $service): string
    {
        $normalized = mb_strtolower($service);

        return match (true) {
            str_contains($normalized, 'medicina') => 'MED',
            str_contains($normalized, 'ginecolog') => 'GIN',
            str_contains($normalized, 'pediatr') => 'PED',
            str_contains($normalized, 'cirug') => 'CIR',
            default => 'GEN',
        };
    }
}
