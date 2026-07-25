<?php

use App\Http\Middleware\EnsureLegacyModuleAccess;
use App\Support\UserFacingError;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'legacy.module' => EnsureLegacyModuleAccess::class,
        ]);

        // Compatibilidad temporal: los formularios existentes conservan sus
        // contratos mientras cada módulo adopta tokens CSRF de Laravel.
        $middleware->validateCsrfTokens(except: ['*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request) {
            if ($exception->getConnectionName() !== 'sigh') {
                return null;
            }

            $reference = UserFacingError::report($exception, 'INTRA-SIGH', [
                'path' => $request->path(),
                'user_id' => $request->user()?->getAuthIdentifier(),
            ]);
            $message = 'El servicio de citas no está disponible temporalmente. Intenta nuevamente en unos minutos.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'success' => false,
                    'error' => $message,
                    'reference' => $reference,
                ], 503);
            }

            return response()->view('errors.service-unavailable', [
                'message' => $message,
                'reference' => $reference,
            ], 503);
        });
    })->create();
