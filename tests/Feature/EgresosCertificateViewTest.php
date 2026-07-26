<?php

namespace Tests\Feature;

use App\Models\Egresos\Constancia;
use App\Models\Egresos\ConstanciaEpisodio;
use App\Services\Egresos\ConstanciaDocumentPresenter;
use Illuminate\Database\Eloquent\Collection;
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
        self::assertStringContainsString('N° 0029-2026-HSJ-GIN', $html);
        self::assertStringContainsString('DIRECCION REGIONAL DE SALUD', $html);
        self::assertStringContainsString('HACE CONSTAR:', $html);
        self::assertStringContainsString('hoja automatizada de epicrisis', $normalizedHtml);
        self::assertStringContainsString('/assets/images/logo.jpeg', $html);
        self::assertStringContainsString('/assets/images/fondo.png', $html);
        self::assertStringNotContainsString('CONSTANCIA DE EGRESO HOSPITALARIO', $html);
    }

    public function test_cancelled_certificate_is_viewable_but_not_printable(): void
    {
        $certificate = new Constancia([
            'numero' => 30,
            'anio' => 2026,
            'paciente' => 'PACIENTE DE PRUEBA',
            'estado' => 'anulada',
            'motivo_anulacion' => 'Documento reemplazado por corrección.',
        ]);
        $document = app(ConstanciaDocumentPresenter::class)->present($certificate);
        $html = view('egresos.certificate', [
            'constancia' => $certificate,
            'document' => $document,
            'allowPrint' => false,
        ])->render();

        self::assertStringContainsString('CONSTANCIA ANULADA', $html);
        self::assertStringContainsString('disponible únicamente para consulta histórica', $html);
        self::assertStringContainsString('IMPRESIÓN NO AUTORIZADA', $html);
        self::assertStringNotContainsString('onclick="window.print()"', $html);
        self::assertFalse($certificate->canBePrinted());
        $certificate->estado = 'generada';
        self::assertTrue($certificate->canBePrinted());
    }

    public function test_certificate_can_render_multiple_selected_episodes(): void
    {
        $certificate = new Constancia([
            'numero' => 32,
            'anio' => 2026,
            'paciente' => 'PACIENTE CON REINGRESOS',
            'doc_tipo_id' => 1,
            'doc_iden' => '70887766',
            'numhc' => '200757',
            'estado' => 'generada',
        ]);
        $certificate->setRelation('episodios', new Collection([
            new ConstanciaEpisodio([
                'egreso_id' => 10,
                'posicion' => 1,
                'fecing' => '2025-01-10',
                'fecegr' => '2025-01-15',
                'ups' => 'MEDICINA',
                'servicio' => 'MEDICINA',
                'condicion' => 'MEJORADO',
                'coddiag1' => 'J189',
                'descdiag1' => 'Neumonía no especificada',
            ]),
            new ConstanciaEpisodio([
                'egreso_id' => 20,
                'posicion' => 2,
                'fecing' => '2026-03-01',
                'fecegr' => '2026-03-04',
                'ups' => 'CIRUGIA',
                'servicio' => 'CIRUGIA',
                'condicion' => 'MEJORADO',
                'coddiag1' => 'K358',
                'descdiag1' => 'Otras apendicitis agudas',
            ]),
        ]));

        $document = app(ConstanciaDocumentPresenter::class)->present($certificate);
        $html = view('egresos.certificate', [
            'constancia' => $certificate,
            'document' => $document,
        ])->render();

        self::assertSame(2, $document['episode_count']);
        self::assertStringContainsString('sheet-multiple', $html);
        self::assertStringContainsString('N° 0032-2026-HSJ-GEN', $html);
        self::assertStringContainsString('2 episodios de', $html);
        self::assertStringContainsString('Episodio 1: MEDICINA', $html);
        self::assertStringContainsString('Episodio 2: CIRUGIA', $html);
        self::assertStringContainsString('J18.9:', $html);
        self::assertStringContainsString('K35.8:', $html);
    }

    public function test_active_certificate_requires_server_authorization_before_printing(): void
    {
        $certificate = new Constancia([
            'numero' => 31,
            'anio' => 2026,
            'paciente' => 'PACIENTE AUTORIZADO',
            'estado' => 'generada',
        ]);
        $html = view('egresos.certificate', [
            'constancia' => $certificate,
            'document' => app(ConstanciaDocumentPresenter::class)->present($certificate),
            'allowPrint' => true,
            'printAuthorizationUrl' => '/egresos/api/constancias/31/autorizar-impresion',
        ])->render();

        self::assertStringContainsString('Autorizar e imprimir', $html);
        self::assertStringContainsString('/egresos/api/constancias/31/autorizar-impresion', $html);
        self::assertStringContainsString("document.body.classList.add('print-authorized')", $html);
        self::assertStringNotContainsString('onclick="window.print()"', $html);
    }
}
