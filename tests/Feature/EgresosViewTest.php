<?php

namespace Tests\Feature;

use Tests\TestCase;

final class EgresosViewTest extends TestCase
{
    public function test_egresos_view_renders_with_central_permissions(): void
    {
        $html = view('egresos.index', [
            'centralUser' => [
                'id' => 1,
                'name' => 'Administrador',
                'email' => 'admin@example.test',
                'roles' => ['administrador'],
            ],
            'permissions' => [],
            'abilities' => [
                'viewRecords' => true,
                'createRecords' => true,
                'updateRecords' => true,
                'manageImports' => true,
                'createCertificates' => true,
                'updateCertificates' => true,
                'cancelCertificates' => true,
                'viewHistory' => true,
                'viewReports' => true,
                'manageConfiguration' => true,
            ],
        ])->render();

        self::assertStringContainsString('Módulo central de Egresos', $html);
        self::assertStringContainsString('window.EGRESOS_CONFIG', $html);
        self::assertStringContainsString('manageConfiguration', $html);
        self::assertStringContainsString('dashboardUrl', $html);
        self::assertStringContainsString('Registrar excepción', $html);
        self::assertStringContainsString('Últimos egresos cargados', $html);
        self::assertStringContainsString('id="timeline-modal"', $html);
        self::assertStringContainsString('id="certificate-preview-modal"', $html);
        self::assertStringContainsString('Confirmar y generar constancia', $html);
        self::assertStringNotContainsString('id="detail-modal"', $html);
        self::assertStringContainsString('Importación masiva controlada', $html);
        self::assertStringContainsString('Analizar archivo', $html);
        self::assertStringContainsString('ningún dato se insertará', $html);
        self::assertTrue(strpos($html, 'id="import-form"') < strpos($html, 'id="import-result"'));
        self::assertTrue(strpos($html, 'id="import-result"') < strpos($html, 'Importaciones recientes'));
        self::assertStringContainsString('Registrar configuración institucional', $html);
        self::assertStringContainsString('hs-tooltip-content', $html);
        self::assertTrue(strpos($html, 'name="nombre_director"') < strpos($html, 'id="certificate-preview"'));
        self::assertStringContainsString('Configuraciones registradas', $html);
        self::assertStringContainsString('Siguiente constancia a generar', $html);
        self::assertStringContainsString('El agrupamiento por paciente no modifica el correlativo legal', $html);
        self::assertStringContainsString('Exportar XLSX', $html);
        self::assertStringNotContainsString('192.168.3.246:8002/egresos/api', $html);
    }
}
