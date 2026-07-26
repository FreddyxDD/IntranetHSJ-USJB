<?php

namespace App\Services\Egresos;

use App\Models\Egresos\Constancia;
use App\Models\Egresos\ConstanciaEpisodio;
use Illuminate\Database\Eloquent\Model;

final class ConstanciaDocumentPresenter
{
    private const SERVICES = [
        '241400' => ['name' => 'GINECOLOGIA', 'code' => 'GIN', 'lower' => 'KRJ'],
        '240100' => ['name' => 'CIRUGIA', 'code' => 'CIR', 'lower' => 'BDP'],
        '242500' => ['name' => 'PEDIATRIA', 'code' => 'PED', 'lower' => 'VVU'],
        '241800' => ['name' => 'MEDICINA', 'code' => 'MED', 'lower' => 'CCP'],
    ];

    private const DOCUMENT_TYPES = [
        1 => 'DNI',
        2 => 'CARNET DE EXTRANJERIA',
        3 => 'PASAPORTE',
        4 => 'DOCUMENTO DE IDENTIDAD EXTRANJERO',
        5 => 'CODIGO DE RECIEN NACIDO',
        6 => 'CODIGO TEMPORAL',
        7 => 'C.I.U.',
        8 => 'DOCUMENTO DE MADRE E HIJO',
    ];

    public function present(Constancia $certificate): array
    {
        if ($certificate->exists && ! $certificate->relationLoaded('episodios')) {
            $certificate->load('episodios');
        }
        $episodeModels = $certificate->relationLoaded('episodios')
            ? $certificate->episodios
            : collect();
        if ($episodeModels->isEmpty()) {
            $episodeModels = collect([$certificate]);
        }
        $episodes = $episodeModels
            ->map(fn (Model $episode): array => $this->episode($episode))
            ->values();
        $serviceCodes = $episodes->pluck('service_code')->unique();
        $serviceCode = $this->clean($certificate->sigla_servicio)
            ?: ($serviceCodes->count() === 1 ? $serviceCodes->first() : 'GEN');
        $service = $episodes->count() === 1
            ? $episodes->first()['service']
            : 'VARIOS SERVICIOS';

        return [
            'correlative' => sprintf(
                'N° %04d-%d-HSJ-%s',
                $certificate->numero,
                $certificate->anio,
                $serviceCode
            ),
            'patient' => mb_strtoupper($this->clean($certificate->paciente) ?: 'PACIENTE NO REGISTRADO'),
            'document_type' => self::DOCUMENT_TYPES[(int) $certificate->doc_tipo_id] ?? 'DNI',
            'document' => $certificate->documento ?: 'NO CONSIGNADO',
            'admission_date' => $episodes->first()['admission_date'],
            'discharge_date' => $episodes->first()['discharge_date'],
            'history' => $this->clean($certificate->numhc) ?: '-',
            'service' => $service,
            'service_code' => $serviceCode,
            'diagnoses' => $episodes->first()['diagnoses'],
            'episodes' => $episodes->all(),
            'episode_count' => $episodes->count(),
            'issue_date' => now()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'),
            'director_initials' => mb_strtoupper(
                $this->clean($certificate->iniciales_jefe)
                ?: $this->clean($certificate->iniciales_director)
                ?: 'MASG'
            ),
            'ccp_initials' => mb_strtoupper(
                $this->clean($certificate->iniciales_ccp)
                ?: ($episodes->count() === 1 ? $episodes->first()['lower_code'] : 'GEN')
            ),
            'lower_code' => 'J-'.$serviceCode,
            'director_name' => $this->clean($certificate->nombre_director),
            'director_title' => mb_strtoupper(
                $this->clean($certificate->cargo_director) ?: 'DIRECCIÓN EJECUTIVA'
            ),
        ];
    }

    private function episode(Model $episode): array
    {
        $service = $this->service($episode);

        return [
            'id' => $episode->egreso_id,
            'position' => $episode instanceof ConstanciaEpisodio ? $episode->posicion : 1,
            'admission_date' => $episode->fecing?->format('d/m/Y') ?: '',
            'discharge_date' => $episode->fecegr?->format('d/m/Y') ?: '',
            'service' => $service['name'],
            'service_code' => $service['code'],
            'lower_code' => $service['lower'],
            'condition' => $this->clean($episode->condicion) ?: 'NO CONSIGNADA',
            'financing' => $this->clean($episode->financia),
            'diagnoses' => $this->diagnoses($episode),
        ];
    }

    private function service(Model $episode): array
    {
        $ups = $this->clean($episode->ups);
        $stored = mb_strtoupper($this->clean($episode->servicio));
        $mapped = self::SERVICES[$ups] ?? null;

        if ($mapped) {
            $mapped['name'] = $stored ?: $mapped['name'];

            return $mapped;
        }

        return [
            'name' => $stored ?: $ups ?: 'SERVICIO NO REGISTRADO',
            'code' => $this->serviceCode($stored ?: $ups),
            'lower' => 'GEN',
        ];
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

    private function diagnoses(Model $episode): array
    {
        return collect(range(1, 4))
            ->map(function (int $position) use ($episode): ?array {
                $code = $this->clean($episode->getAttribute("coddiag{$position}"));
                if ($code === '') {
                    return null;
                }

                return [
                    'code' => $this->formatCie10($code),
                    'description' => $this->clean(
                        $episode->getAttribute("descdiag{$position}")
                    ) ?: 'SIN DESCRIPCION',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function formatCie10(string $code): string
    {
        $normalized = mb_strtoupper(str_replace('.', '', $code));

        return strlen($normalized) > 3
            ? substr($normalized, 0, 3).'.'.substr($normalized, 3)
            : $normalized;
    }

    private function clean(mixed $value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?: '';
    }
}
