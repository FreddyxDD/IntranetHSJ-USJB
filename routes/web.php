<?php

use App\Http\Controllers\Admin\IdentityAdminController;
use App\Http\Controllers\Appointments\AppointmentAdminController;
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
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Surgery\SurgeryController;
use App\Http\Controllers\Surgery\SurgeryPortalController;
use App\Http\Controllers\Uvi\UviController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('institutional.login');
Route::redirect('/login', '/')->name('institutional.login.compatibility');
Route::post('/crear-cuenta-ueei', [InstitutionalAuthController::class, 'register']);
Route::post('/validar-dni-ueei', [InstitutionalAuthController::class, 'validateRegistrationDni']);
Route::post('/confirmar-cuenta-ueei', [InstitutionalAuthController::class, 'confirmAccountInstructions']);
Route::post('/login-ueei', [InstitutionalAuthController::class, 'login']);
Route::get('/me-ueei', [InstitutionalAuthController::class, 'me']);
Route::post('/logout-ueei', [InstitutionalAuthController::class, 'logout'])
    ->name('institutional.logout');
Route::post('/logout', [InstitutionalAuthController::class, 'logout'])
    ->name('institutional.logout.compatibility');

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

Route::middleware('central.auth')->group(function (): void {
    Route::get('/admin-ueei', [IdentityAdminController::class, 'page'])->name('identity.admin');
    Route::get('/api/admin-ueei/resumen', [IdentityAdminController::class, 'resumen']);
    Route::get('/api/admin-ueei/catalogos', [IdentityAdminController::class, 'catalogos']);
    Route::get('/api/admin-ueei/usuarios', [IdentityAdminController::class, 'usuarios']);
    Route::post('/api/admin-ueei/usuarios', [IdentityAdminController::class, 'crearUsuario']);
    Route::put('/api/admin-ueei/usuarios/{usuario}', [IdentityAdminController::class, 'actualizarUsuario'])
        ->whereNumber('usuario');
    Route::patch('/api/admin-ueei/usuarios/{usuario}/estado', [IdentityAdminController::class, 'cambiarEstado'])
        ->whereNumber('usuario');
    Route::patch('/api/admin-ueei/usuarios/{usuario}/password', [IdentityAdminController::class, 'cambiarPassword'])
        ->whereNumber('usuario');
});

/*
|--------------------------------------------------------------------------
| URLs compatibles del Intranet HSJ
|--------------------------------------------------------------------------
| Laravel 13 es el único punto de entrada. Las URL históricas se conservan
| como alias explícitos de controladores Laravel, sin enrutador PHP heredado.
*/
Route::middleware('module.access:citas_admin')->group(function (): void {
    Route::get('/citas-admin', [AppointmentAdminController::class, 'page'])->name('appointments.admin');
    Route::get('/pages/CitasAdmi.html', [AppointmentAdminController::class, 'page']);
    Route::get('/api/citas-admin/registros', [AppointmentAdminController::class, 'registros']);
    Route::put('/api/citas-admin/registros/{registro}/estado', [AppointmentAdminController::class, 'actualizarEstado'])
        ->whereNumber('registro');
    Route::get('/api/citas-admin/reportes', [AppointmentAdminController::class, 'reportes']);
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

Route::middleware('module.access:cirugias')->group(function (): void {
    Route::get('/cirugias-login', [SurgeryPortalController::class, 'entry']);
    Route::get('/pages/LoginLS.html', [SurgeryPortalController::class, 'entry']);
    Route::get('/principal-cirugias', [SurgeryPortalController::class, 'page'])->name('surgery.page');
    Route::get('/pages/principalLS.html', [SurgeryPortalController::class, 'page']);
    Route::get('/manual-cirugias', [SurgeryPortalController::class, 'manual']);
    Route::get('/pages/manualLS.html', [SurgeryPortalController::class, 'manual']);
    Route::get('/cirugias-admin', [SurgeryPortalController::class, 'administration']);
    Route::get('/me-ls', [SurgeryPortalController::class, 'me']);
    Route::get('/me-cirugias', [SurgeryPortalController::class, 'me']);
    Route::post('/logout-ls', [SurgeryPortalController::class, 'leave']);
    Route::post('/logout-cirugias', [SurgeryPortalController::class, 'leave']);

    Route::get('/cirugias', [SurgeryController::class, 'listar']);
    Route::get('/cirugias-resumen', [SurgeryController::class, 'resumen']);
    Route::get('/cirugias-hojas', [SurgeryController::class, 'hojas']);
    Route::post('/cirugias-manual', [SurgeryController::class, 'crearManual'])
        ->middleware('central.permission:cirugias.records.manage');
    Route::put('/cirugias/{cirugia}', [SurgeryController::class, 'actualizar'])
        ->whereNumber('cirugia')
        ->middleware('central.permission:cirugias.records.manage');
    Route::delete('/cirugias', [SurgeryController::class, 'eliminarTodo'])
        ->middleware('central.permission:cirugias.imports.manage');
    Route::post('/excel-hojas', [SurgeryController::class, 'excelHojas'])
        ->middleware('central.permission:cirugias.imports.manage');
    Route::post('/importar-cirugias', [SurgeryController::class, 'importarExcel'])
        ->middleware('central.permission:cirugias.imports.manage');

    Route::get('/especialidades', [SurgeryController::class, 'especialidades']);
    Route::post('/especialidades', [SurgeryController::class, 'especialidades'])
        ->middleware('central.permission:cirugias.records.manage');
    Route::get('/cie10', [SurgeryController::class, 'cie10']);
    Route::get('/cie10/buscar', [SurgeryController::class, 'cie10']);
    Route::get('/cie10/estados', [SurgeryController::class, 'cie10Estados']);
    Route::get('/cie10/sexos', [SurgeryController::class, 'cie10Sexos']);
    Route::get('/personal-medico', [SurgeryController::class, 'personalMedico'])
        ->middleware('central.permission:cirugias.staff.manage');
    Route::get('/personal-medico/{personal}', [SurgeryController::class, 'mostrarPersonalMedico'])
        ->whereNumber('personal')
        ->middleware('central.permission:cirugias.staff.manage');
    Route::post('/personal-medico', [SurgeryController::class, 'crearPersonalMedico'])
        ->middleware('central.permission:cirugias.staff.manage');
    Route::put('/personal-medico/{personal}', [SurgeryController::class, 'actualizarPersonalMedico'])
        ->whereNumber('personal')
        ->middleware('central.permission:cirugias.staff.manage');
    Route::put('/personal-medico/{personal}/estado', [SurgeryController::class, 'cambiarEstadoPersonal'])
        ->whereNumber('personal')
        ->middleware('central.permission:cirugias.staff.manage');
    Route::get('/personal-medico/profesiones', [SurgeryController::class, 'personalProfesiones'])
        ->middleware('central.permission:cirugias.staff.manage');
    Route::get('/pacientes', [SurgeryController::class, 'pacientes']);
    Route::get('/pacientes/buscar', [SurgeryController::class, 'pacientes']);
    Route::get('/pacientes/dni/{dni}', [SurgeryController::class, 'pacientes'])
        ->where('dni', '\d{8}');
    Route::get('/procedimientos', [SurgeryController::class, 'procedimientos']);
    Route::get('/procedimientos/sugerencias', [SurgeryController::class, 'procedimientos']);
    Route::get('/sigh/procedimientos/sugerencias', [SurgeryController::class, 'procedimientos']);
    Route::get('/procedimientos/secciones', [SurgeryController::class, 'procedimientosSecciones']);
    Route::get('/sigh/operacion-por-cie10', [SurgeryController::class, 'operacionPorCie10']);
    Route::get('/tablas-sigh', [SurgeryController::class, 'tablasSigh']);
    Route::get('/api/tablas-sigh', [SurgeryController::class, 'tablasSigh']);
    Route::get('/api/importaciones', [SurgeryController::class, 'importaciones']);

    Route::middleware('central.permission:cirugias.analytics.view')->group(function (): void {
        Route::get('/api/analisis/meses-disponibles', [SurgeryController::class, 'analisisMeses']);
        Route::get('/api/analisis/cirugias-mensual', [SurgeryController::class, 'analisisMensual']);
        Route::get('/api/analisis/tipo-orden', [SurgeryController::class, 'analisisTipoOrden']);
        Route::get('/api/analisis/resumen-periodo', [SurgeryController::class, 'analisisResumenPeriodo']);
        Route::get('/api/analisis/mayor-menor-electiva', [SurgeryController::class, 'analisisMayorMenorElectiva']);
        Route::get('/api/analisis/especialidades', [SurgeryController::class, 'analisisEspecialidades']);
        Route::get('/api/analisis/detalle-especialidad', [SurgeryController::class, 'analisisDetalleEspecialidad']);
    });

    Route::middleware('central.permission:cirugias.reports.view')->group(function (): void {
        Route::get('/api/reportes/meses-disponibles', [SurgeryController::class, 'reportesMeses']);
        Route::get('/api/reportes/cirugias-mensual', [SurgeryController::class, 'reporteMensual']);
    });
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
