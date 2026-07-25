<?php

namespace Tests\Unit;

use App\Models\Egresos\Cie10;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\ConstanciaHistorial;
use App\Models\Egresos\Egreso;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class EgresosArchitectureTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_module_is_registered_with_its_central_permission(): void
    {
        require_once base_path('app/config/app.php');
        require_once base_path('app/helpers/modulos.php');

        self::assertSame('/egresos', intranet_module_catalog()['egresos']['ruta']);
        self::assertSame(['egresos.view'], intranet_module_permission_map()['egresos']);
    }

    public function test_domain_models_use_schema_qualified_sql_server_tables(): void
    {
        self::assertSame('egresos.egresos', (new Egreso)->getTable());
        self::assertSame('egresos.constancias', (new Constancia)->getTable());
        self::assertSame('egresos.constancia_historial', (new ConstanciaHistorial)->getTable());
        self::assertSame('catalogos.cie10', (new Cie10)->getTable());
    }

    public function test_routes_are_declared_before_the_legacy_fallback(): void
    {
        $index = Route::getRoutes()->getByName('egresos.index');
        $create = Route::getRoutes()->getByName('egresos.certificates.store');

        self::assertNotNull($index);
        self::assertContains('legacy.module:egresos', $index->gatherMiddleware());
        self::assertContains('central.permission:egresos.view', $index->gatherMiddleware());
        self::assertNotNull($create);
        self::assertContains('central.permission:egresos.certificates.create', $create->gatherMiddleware());
    }

    public function test_central_administrator_can_use_egresos_permissions(): void
    {
        require_once base_path('app/config/app.php');
        require_once base_path('app/helpers/modulos.php');

        $_SESSION = [
            'ueei_id' => 1,
            'ueei_correo' => 'admin@example.test',
            'ueei_rol' => 'admin',
            'identity_roles' => ['administrador'],
            'identity_permissions' => [],
        ];

        self::assertTrue(modulo_autorizado('egresos'));
        self::assertTrue(ueei_tiene_permiso('egresos.certificates.create'));
    }
}
