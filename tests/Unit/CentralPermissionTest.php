<?php

namespace Tests\Unit;

use Tests\TestCase;

final class CentralPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once base_path('app/helpers/modulos.php');
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_regular_user_only_receives_explicit_central_permissions(): void
    {
        $_SESSION = [
            'ueei_id' => 20,
            'ueei_rol' => 'consulta',
            'identity_roles' => ['consulta_cirugias'],
            'identity_permissions' => [
                'cirugias.view',
                'cirugias.analytics.view',
            ],
        ];

        self::assertTrue(ueei_tiene_permiso('cirugias.analytics.view'));
        self::assertFalse(ueei_tiene_permiso('cirugias.imports.manage'));
    }

    public function test_central_administrator_inherits_all_application_permissions(): void
    {
        $_SESSION = [
            'ueei_id' => 2,
            'ueei_rol' => 'admin',
            'identity_roles' => ['administrador'],
            'identity_permissions' => [],
        ];

        self::assertTrue(ueei_tiene_permiso('cirugias.imports.manage'));
        self::assertTrue(ueei_tiene_permiso('cirugias.staff.manage'));
    }
}
