<?php

namespace App\Http\Middleware;

use App\Services\Identity\CentralAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCentralPermission
{
    public function __construct(private readonly CentralAccessService $access) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $this->access->isAuthenticated()) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Sesión no iniciada.'], 401)
                : redirect('/');
        }

        if ($this->access->confirmationPending()) {
            return $request->expectsJson()
                ? response()->json([
                    'ok' => false,
                    'message' => 'Debes confirmar las instrucciones de activación antes de continuar.',
                ], 428)
                : redirect('/');
        }

        if (! $this->access->hasPermission($permission)) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'No tienes permiso para realizar esta acción.'], 403)
                : response()->view('errors.403', status: 403);
        }

        return $next($request);
    }
}
