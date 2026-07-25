<?php

namespace App\Services\Egresos;

use App\Models\Egresos\Constancia;

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
        $service = $this->service($certificate);
        $serviceCode = $this->clean($certificate->sigla_servicio)
            ?: $service['code'];

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
            'admission_date' => $certificate->fecing?->format('d/m/Y') ?: '',
            'discharge_date' => $certificate->fecegr?->format('d/m/Y') ?: '',
            'history' => $this->clean($certificate->numhc) ?: '-',
            'service' => $service['name'],
            'service_code' => $serviceCode,
            'diagnoses' => $this->diagnoses($certificate),
            'issue_date' => now()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'),
            'director_initials' => mb_strtoupper(
                $this->clean($certificate->iniciales_jefe)
                ?: $this->clean($certificate->iniciales_director)
                ?: 'MASG'
            ),
            'ccp_initials' => mb_strtoupper(
                $this->clean($certificate->iniciales_ccp)
                ?: $service['lower']
            ),
            'lower_code' => 'J-'.$serviceCode,
            'director_name' => $this->clean($certificate->nombre_director),
            'director_title' => mb_strtoupper(
                $this->clean($certificate->cargo_director) ?: 'DIRECCIÓN EJECUTIVA'
            ),
        ];
    }

    private function service(Constancia $certificate): array
    {
        $ups = $this->clean($certificate->ups);
        $stored = mb_strtoupper($this->clean($certificate->servicio));
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

    private function diagnoses(Constancia $certificate): array
    {
        return collect(range(1, 4))
            ->map(function (int $position) use ($certificate): ?array {
                $code = $this->clean($certificate->getAttribute("coddiag{$position}"));
                if ($code === '') {
                    return null;
                }

                return [
                    'code' => $this->formatCie10($code),
                    'description' => $this->clean(
                        $certificate->getAttribute("descdiag{$position}")
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
