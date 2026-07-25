<?php

namespace Tests\Feature;

use App\Models\Egresos\Constancia;
use App\Services\Egresos\ConstanciaDocumentPresenter;
use Tests\TestCase;

final class EgresosCertificateViewTest extends TestCase
{
    public function test_certificate_keeps_the_original_hospitalization_format(): void
    {
        $certificate = new Constancia([
            'numero' => 29,
            'anio' => 2026,
            'paciente' => 'CECILIA DAVALOS HUAMAN',
            'doc_tipo_id' => 1,
            'doc_iden' => '47259227',
            'numhc' => '370500',
            'fecing' => '2025-06-29',
            'fecegr' => '2025-07-01',
            'ups' => '241400',
            'servicio' => 'GINECOLOGIA',
            'sigla_servicio' => 'GIN',
            'coddiag1' => 'O821',
            'descdiag1' => 'Parto por cesárea de emergencia',
            'iniciales_jefe' => 'MASG',
            'iniciales_ccp' => 'CGSB',
            'estado' => 'generada',
        ]);
        $document = app(ConstanciaDocumentPresenter::class)->present($certificate);
        $html = view('egresos.certificate', [
            'constancia' => $certificate,
            'document' => $document,
        ])->render();
        $normalizedHtml = preg_replace('/\s+/u', ' ', $html);

        self::assertStringContainsString('CONSTANCIA DE HOSPITALIZACION', $html);
        self::assertStringContainsString('N° 029-2026-HSJ-GIN', $html);
        self::assertStringContainsString('DIRECCION REGIONAL DE SALUD', $html);
        self::assertStringContainsString('HACE CONSTAR:', $html);
        self::assertStringContainsString('hoja automatizada de epicrisis', $normalizedHtml);
        self::assertStringContainsString('/assets/images/logo.jpeg', $html);
        self::assertStringContainsString('/assets/images/fondo.png', $html);
        self::assertStringNotContainsString('CONSTANCIA DE EGRESO HOSPITALARIO', $html);
    }
}
