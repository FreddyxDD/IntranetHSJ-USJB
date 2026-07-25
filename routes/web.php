<?php

use App\Http\Controllers\Appointments\AppointmentApiController;
use App\Http\Controllers\Egresos\ConfiguracionConstanciaController;
use App\Http\Controllers\Egresos\ConstanciaController;
use App\Http\Controllers\Egresos\EgresoController;
use App\Http\Controllers\Egresos\ImportacionController;
use App\Http\Controllers\Egresos\ReporteController;
use App\Http\Controllers\LegacyApplicationController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Puente de compatibilidad del Intranet HSJ
|--------------------------------------------------------------------------
| Laravel 13 es ahora el punto de entrada. Las URLs existentes se delegan al
| kernel institucional mientras los controladores se refactorizan por módulo.
| Las vistas y respuestas JSON conservan exactamente sus contratos actuales.
*/
Route::middleware('legacy.module:citas_admin')->group(function (): void {
    Route::get('/api/citas-admin/citas-diarias', [AppointmentApiController::class, 'daily'])
        ->name('appointments.daily');
    Route::get('/api/citas-admin/citas-diarias/{programacion}/pacientes', [AppointmentApiController::class, 'patients'])
        ->whereNumber('programacion')
        ->name('appointments.patients');
    Route::put('/api/citas-admin/citas-diarias/{programacion}/estado', [AppointmentApiController::class, 'updateProgramState'])
        ->whereNumber('programacion')
        ->name('appointments.program-state');
});

Route::prefix('egresos')->middleware('legacy.module:egresos')->group(function (): void {
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
    Route::get('/reportes/egresos.csv', [ReporteController::class, 'csv'])
        ->middleware('central.permission:egresos.reports.view')
        ->name('egresos.reports.csv');
    Route::get('/reportes/egresos.xlsx', [ReporteController::class, 'xlsx'])
        ->middleware('central.permission:egresos.reports.view')
        ->name('egresos.reports.xlsx');
    Route::get('/api/cie10', [EgresoController::class, 'cie10'])
        ->middleware('central.permission:egresos.records.view')
        ->name('egresos.cie10');
    Route::get('/api/constancias', [EgresoController::class, 'certificates'])
        ->middleware('central.permission:egresos.history.view')
        ->name('egresos.certificates.index');
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
    Route::get('/constancias/{constancia}/imprimir', [ConstanciaController::class, 'print'])
        ->whereNumber('constancia')
        ->middleware('central.permission:egresos.view')
        ->name('egresos.certificates.print');
});

Route::any('/{path?}', LegacyApplicationController::class)
    ->where('path', '.*')
    ->withoutMiddleware(ValidateCsrfToken::class);
