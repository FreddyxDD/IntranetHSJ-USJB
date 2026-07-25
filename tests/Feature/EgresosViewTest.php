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
        self::assertStringNotContainsString('192.168.3.246:8002/egresos/api', $html);
    }
}
