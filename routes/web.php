<?php

use App\Http\Controllers\Appointments\AppointmentApiController;
use App\Http\Controllers\Auth\InstitutionalAuthController;
use App\Http\Controllers\Egresos\AuditoriaController;
use App\Http\Controllers\Egresos\Cie10CatalogController;
use App\Http\Controllers\Egresos\ConfiguracionConstanciaController;
use App\Http\Controllers\Egresos\ConstanciaController;
use App\Http\Controllers\Egresos\EgresoController;
use App\Http\Controllers\Egresos\ImportacionController;
use App\Http\Controllers\Egresos\ReporteController;
use App\Http\Controllers\Indicators\IndicatorController;
use App\Http\Controllers\LegacyApplicationController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Uvi\UviController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('login');
Route::post('/crear-cuenta-ueei', [InstitutionalAuthController::class, 'register']);
Route::post('/validar-dni-ueei', [InstitutionalAuthController::class, 'validateRegistrationDni']);
Route::post('/confirmar-cuenta-ueei', [InstitutionalAuthController::class, 'confirmAccountInstructions']);
Route::post('/login-ueei', [InstitutionalAuthController::class, 'login']);
Route::get('/me-ueei', [InstitutionalAuthController::class, 'me']);
Route::post('/logout-ueei', [InstitutionalAuthController::class, 'logout']);

Route::middleware('central.auth')->group(function (): void {
    Route::get('/principal', [PortalController::class, 'principal'])->name('portal.principal');
    Route::get('/pages/principal', [PortalController::class, 'principal']);
    Route::get('/pages/principal.html', [PortalController::class, 'principal']);
    Route::get('/areas', [PortalController::class, 'areas'])->name('portal.areas');
    Route::get('/pages/Areas.html', [PortalController::class, 'areas']);
    Route::get('/perfil', [PortalController::class, 'profile'])->name('portal.profile');
    Route::get('/pages/perfil', [PortalController::class, 'profile']);
    Route::get('/pages/perfil.html', [PortalController::class, 'profile']);
});

Route::middleware('module.access:informacion')->group(function (): void {
    Route::get('/informacion', [PortalController::class, 'information'])->name('portal.information');
    Route::get('/pages/informacion.html', [PortalController::class, 'information']);
});

/*
|--------------------------------------------------------------------------
| Puente de compatibilidad del Intranet HSJ
|--------------------------------------------------------------------------
| Laravel 13 es ahora el punto de entrada. Las URLs existentes se delegan al
| kernel institucional mientras los controladores se refactorizan por módulo.
| Las vistas y respuestas JSON conservan exactamente sus contratos actuales.
*/
Route::middleware('module.access:citas_admin')->group(function (): void {
    Route::get('/api/citas-admin/citas-diarias', [AppointmentApiController::class, 'daily'])
        ->name('appointments.daily');
    Route::get('/api/citas-admin/citas-diarias/{programacion}/pacientes', [AppointmentApiController::class, 'patients'])
        ->whereNumber('programacion')
        ->name('appointments.patients');
    Route::put('/api/citas-admin/citas-diarias/{programacion}/estado', [AppointmentApiController::class, 'updateProgramState'])
        ->whereNumber('programacion')
        ->name('appointments.program-state');
});

Route::middleware('module.access:produccion')->group(function (): void {
    Route::get('/produccion', [IndicatorController::class, 'productionPage'])
        ->name('indicators.production.page');
    Route::get('/pages/produccion.html', [IndicatorController::class, 'productionPage']);
    Route::get('/indicadores/produccion', [IndicatorController::class, 'production'])
        ->name('indicators.production.index');
});

Route::middleware('module.access:eficiencia')->group(function (): void {
    Route::get('/eficiencia', [IndicatorController::class, 'efficiencyPage'])
        ->name('indicators.efficiency.page');
    Route::get('/pages/eficiencia.html', [IndicatorController::class, 'efficiencyPage']);
    Route::get('/indicadores/eficiencia', [IndicatorController::class, 'efficiency'])
        ->name('indicators.efficiency.index');
    Route::get('/admin/indicadores/eficiencia', [IndicatorController::class, 'efficiencyAdmin'])
        ->name('indicators.efficiency.admin');
    Route::put('/admin/indicadores/eficiencia', [IndicatorController::class, 'updateEfficiency'])
        ->name('indicators.efficiency.update');
});

Route::middleware('module.access:calidad')->group(function (): void {
    Route::get('/calidad', [IndicatorController::class, 'qualityPage'])
        ->name('indicators.quality.page');
    Route::get('/pages/calidad.html', [IndicatorController::class, 'qualityPage']);
    Route::get('/indicadores/calidad', [IndicatorController::class, 'quality'])
        ->name('indicators.quality.index');
    Route::get('/admin/indicadores/calidad', [IndicatorController::class, 'qualityAdmin'])
        ->name('indicators.quality.admin');
    Route::put('/admin/indicadores/calidad', [IndicatorController::class, 'updateQuality'])
        ->name('indicators.quality.update');
});

Route::middleware('module.access:uvi')->group(function (): void {
    Route::get('/uvi-login', [UviController::class, 'index'])->name('uvi.index');
    Route::get('/pages/UVILogin.html', [UviController::class, 'index']);
    Route::get('/admin-uvi', [UviController::class, 'index']);
    Route::get('/pages/AdminUVI.html', [UviController::class, 'index']);

    Route::post('/login-uvi', [UviController::class, 'retiredLocalAccounts']);
    Route::post('/logout-uvi', [UviController::class, 'retiredLocalAccounts']);
    Route::get('/usuarios-uvi', [UviController::class, 'retiredLocalAccounts']);
    Route::post('/crear-cuenta-uvi', [UviController::class, 'retiredLocalAccounts']);
    Route::put('/usuarios-uvi/{usuario}', [UviController::class, 'retiredLocalAccounts'])
        ->whereNumber('usuario');
    Route::patch('/usuarios-uvi/{usuario}/estado', [UviController::class, 'retiredLocalAccounts'])
        ->whereNumber('usuario');
});

Route::prefix('egresos')->middleware('module.access:egresos')->group(function (): void {
    Route::get('/', [EgresoController::class, 'index'])
        ->middleware('central.permission:egresos.view')
        ->name('egresos.index');
    Route::get('/api/dashboard', [EgresoController::class, 'dashboard'])
        ->middleware('central.permission:egresos.records.view')
        ->name('egresos.dashboard');
    Route::get('/api/registros', [EgresoController::class, 'search'])
        ->middleware('central.permission:egresos.records.view')
        ->name('egresos.records.index');
    Route::get('/api/registros/{egreso}', [EgresoController::class, 'show'])
        ->whereNumber('egreso')
        ->middleware('central.permission:egresos.records.view')
        ->name('egresos.records.show');
    Route::get('/api/registros/{egreso}/timeline', [EgresoController::class, 'timeline'])
        ->whereNumber('egreso')
        ->middleware('central.permission:egresos.records.view')
        ->name('egresos.records.timeline');
    Route::get('/api/pacientes-sigh', [EgresoController::class, 'patients'])
        ->middleware('central.permission:egresos.records.view')
        ->name('egresos.patients.search');
    Route::post('/api/registros', [EgresoController::class, 'store'])
        ->middleware('central.permission:egresos.records.create')
        ->name('egresos.records.store');
    Route::put('/api/registros/{egreso}', [EgresoController::class, 'update'])
        ->whereNumber('egreso')
        ->middleware('central.permission:egresos.records.update')
        ->name('egresos.records.update');
    Route::get('/api/estadisticas/mensuales', [EgresoController::class, 'monthly'])
        ->middleware('central.permission:egresos.reports.view')
        ->name('egresos.statistics.monthly');
    Route::get('/api/estadisticas/servicios', [EgresoController::class, 'services'])
        ->middleware('central.permission:egresos.reports.view')
        ->name('egresos.statistics.services');
    Route::get('/api/importaciones', [ImportacionController::class, 'index'])
        ->middleware('central.permission:egresos.imports.manage')
        ->name('egresos.imports.index');
    Route::post('/api/importaciones', [ImportacionController::class, 'store'])
        ->middleware('central.permission:egresos.imports.manage')
        ->name('egresos.imports.store');
    Route::get('/api/importaciones/{importacion}', [ImportacionController::class, 'show'])
        ->whereNumber('importacion')
        ->middleware('central.permission:egresos.imports.manage')
        ->name('egresos.imports.show');
    Route::post('/api/importaciones/{importacion}/confirmar', [ImportacionController::class, 'commit'])
        ->whereNumber('importacion')
        ->middleware('central.permission:egresos.imports.manage')
        ->name('egresos.imports.commit');
    Route::get('/reportes/egresos.csv', [ReporteController::class, 'csv'])
        ->middleware('central.permission:egresos.reports.view')
        ->name('egresos.reports.csv');
    Route::get('/reportes/egresos.xlsx', [ReporteController::class, 'xlsx'])
        ->middleware('central.permission:egresos.reports.view')
        ->name('egresos.reports.xlsx');
    Route::get('/api/cie10', [EgresoController::class, 'cie10'])
        ->middleware('central.permission:egresos.records.view')
        ->name('egresos.cie10');
    Route::get('/catalogos/cie10', [Cie10CatalogController::class, 'page'])
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.catalog');
    Route::get('/api/catalogos/cie10', [Cie10CatalogController::class, 'index'])
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.catalog.index');
    Route::post('/api/catalogos/cie10', [Cie10CatalogController::class, 'store'])
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.catalog.store');
    Route::put('/api/catalogos/cie10/{cie10}', [Cie10CatalogController::class, 'update'])
        ->whereNumber('cie10')
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.catalog.update');
    Route::delete('/api/catalogos/cie10/{cie10}', [Cie10CatalogController::class, 'destroy'])
        ->whereNumber('cie10')
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.catalog.destroy');
    Route::get('/api/catalogos/cie10-importaciones', [Cie10CatalogController::class, 'imports'])
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.imports.index');
    Route::post('/api/catalogos/cie10-importaciones', [Cie10CatalogController::class, 'previewImport'])
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.imports.store');
    Route::get('/api/catalogos/cie10-importaciones/{importacion}', [Cie10CatalogController::class, 'showImport'])
        ->whereNumber('importacion')
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.imports.show');
    Route::post('/api/catalogos/cie10-importaciones/{importacion}/confirmar', [Cie10CatalogController::class, 'confirmImport'])
        ->whereNumber('importacion')
        ->middleware('central.permission:egresos.catalogs.manage')
        ->name('egresos.cie10.imports.confirm');
    Route::get('/api/constancias', [EgresoController::class, 'certificates'])
        ->middleware('central.permission:egresos.history.view')
        ->name('egresos.certificates.index');
    Route::post('/api/constancias/previsualizar', [ConstanciaController::class, 'preview'])
        ->middleware('central.permission:egresos.certificates.create')
        ->name('egresos.certificates.preview');
    Route::post('/api/constancias', [ConstanciaController::class, 'store'])
        ->middleware('central.permission:egresos.certificates.create')
        ->name('egresos.certificates.store');
    Route::put('/api/constancias/{constancia}', [ConstanciaController::class, 'update'])
        ->whereNumber('constancia')
        ->middleware('central.permission:egresos.certificates.update')
        ->name('egresos.certificates.update');
    Route::delete('/api/constancias/{constancia}', [ConstanciaController::class, 'cancel'])
        ->whereNumber('constancia')
        ->middleware('central.permission:egresos.certificates.cancel')
        ->name('egresos.certificates.cancel');
    Route::get('/api/configuracion-constancias', [ConfiguracionConstanciaController::class, 'show'])
        ->middleware('central.permission:egresos.configuration.manage')
        ->name('egresos.configuration.show');
    Route::put('/api/configuracion-constancias', [ConfiguracionConstanciaController::class, 'update'])
        ->middleware('central.permission:egresos.configuration.manage')
        ->name('egresos.configuration.update');
    Route::get('/api/auditoria', [AuditoriaController::class, 'index'])
        ->middleware('central.permission:egresos.history.view')
        ->name('egresos.audit.index');
    Route::get('/constancias/{constancia}', [ConstanciaController::class, 'viewDocument'])
        ->whereNumber('constancia')
        ->middleware('central.permission:egresos.view')
        ->name('egresos.certificates.view');
    Route::post('/api/constancias/{constancia}/autorizar-impresion', [ConstanciaController::class, 'authorizePrint'])
        ->whereNumber('constancia')
        ->middleware('central.permission:egresos.view')
        ->name('egresos.certificates.authorize-print');
    Route::get('/constancias/{constancia}/imprimir', [ConstanciaController::class, 'print'])
        ->whereNumber('constancia')
        ->middleware('central.permission:egresos.view')
        ->name('egresos.certificates.print');
});

Route::any('/{path?}', LegacyApplicationController::class)
    ->where('path', '.*')
    ->withoutMiddleware(ValidateCsrfToken::class);
