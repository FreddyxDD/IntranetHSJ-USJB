<?php

namespace Tests\Unit;

use App\Models\Egresos\Cie10;
use App\Models\Egresos\ConfiguracionConstancia;
use App\Models\Egresos\ConfiguracionConstanciaHistorial;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\ConstanciaHistorial;
use App\Models\Egresos\Egreso;
use App\Models\Egresos\Importacion;
use App\Models\Egresos\ImportacionFila;
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
        self::assertSame('egresos.configuracion_constancias', (new ConfiguracionConstancia)->getTable());
        self::assertSame('egresos.configuracion_constancia_historial', (new ConfiguracionConstanciaHistorial)->getTable());
        self::assertSame('egresos.importaciones', (new Importacion)->getTable());
        self::assertSame('egresos.importacion_filas', (new ImportacionFila)->getTable());
    }

    public function test_legacy_documents_are_exposed_without_the_type_prefix(): void
    {
        $egreso = new Egreso(['doc_numero' => '77882264']);
        $constancia = new Constancia([
            'source_system' => 'egresos_legacy',
            'doc_iden_original' => '177882264',
            'doc_iden' => '77882264',
        ]);
        $newCertificate = new Constancia([
            'source_system' => 'intranet_hsj',
            'doc_iden' => '12345678',
        ]);

        self::assertSame('77882264', $egreso->documento);
        self::assertSame('77882264', $constancia->documento);
        self::assertSame('12345678', $newCertificate->documento);
    }

    public function test_routes_are_declared_before_the_legacy_fallback(): void
    {
        $index = Route::getRoutes()->getByName('egresos.index');
        $create = Route::getRoutes()->getByName('egresos.certificates.store');
        $update = Route::getRoutes()->getByName('egresos.certificates.update');
        $cancel = Route::getRoutes()->getByName('egresos.certificates.cancel');
        $view = Route::getRoutes()->getByName('egresos.certificates.view');
        $print = Route::getRoutes()->getByName('egresos.certificates.print');
        $authorizePrint = Route::getRoutes()->getByName('egresos.certificates.authorize-print');
        $configuration = Route::getRoutes()->getByName('egresos.configuration.update');
        $audit = Route::getRoutes()->getByName('egresos.audit.index');
        $recordCreate = Route::getRoutes()->getByName('egresos.records.store');
        $recordUpdate = Route::getRoutes()->getByName('egresos.records.update');
        $recordTimeline = Route::getRoutes()->getByName('egresos.records.timeline');
        $import = Route::getRoutes()->getByName('egresos.imports.store');
        $importShow = Route::getRoutes()->getByName('egresos.imports.show');
        $importCommit = Route::getRoutes()->getByName('egresos.imports.commit');
        $export = Route::getRoutes()->getByName('egresos.reports.xlsx');
        $patientSearch = Route::getRoutes()->getByName('egresos.patients.search');

        self::assertNotNull($index);
        self::assertContains('legacy.module:egresos', $index->gatherMiddleware());
        self::assertContains('central.permission:egresos.view', $index->gatherMiddleware());
        self::assertNotNull($create);
        self::assertContains('central.permission:egresos.certificates.create', $create->gatherMiddleware());
        self::assertContains('central.permission:egresos.certificates.update', $update->gatherMiddleware());
        self::assertContains('central.permission:egresos.certificates.cancel', $cancel->gatherMiddleware());
        self::assertContains('central.permission:egresos.view', $view->gatherMiddleware());
        self::assertContains('central.permission:egresos.view', $print->gatherMiddleware());
        self::assertContains('central.permission:egresos.view', $authorizePrint->gatherMiddleware());
        self::assertContains('central.permission:egresos.configuration.manage', $configuration->gatherMiddleware());
        self::assertContains('central.permission:egresos.history.view', $audit->gatherMiddleware());
        self::assertContains('central.permission:egresos.records.create', $recordCreate->gatherMiddleware());
        self::assertContains('central.permission:egresos.records.update', $recordUpdate->gatherMiddleware());
        self::assertNotNull($recordTimeline);
        self::assertContains('central.permission:egresos.records.view', $recordTimeline->gatherMiddleware());
        self::assertContains('central.permission:egresos.imports.manage', $import->gatherMiddleware());
        self::assertContains('central.permission:egresos.imports.manage', $importShow->gatherMiddleware());
        self::assertContains('central.permission:egresos.imports.manage', $importCommit->gatherMiddleware());
        self::assertContains('central.permission:egresos.reports.view', $export->gatherMiddleware());
        self::assertContains('central.permission:egresos.records.view', $patientSearch->gatherMiddleware());
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
