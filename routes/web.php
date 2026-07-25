<?php

use App\Http\Controllers\LegacyApplicationController;
use App\Http\Controllers\Appointments\AppointmentApiController;
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

Route::any('/{path?}', LegacyApplicationController::class)
    ->where('path', '.*')
    ->withoutMiddleware(ValidateCsrfToken::class);
