<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLegacyModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        require_once base_path('app/config/app.php');
        require_once base_path('app/helpers/modulos.php');

        iniciar_sesion_segura();

        if (empty($_SESSION['ueei_correo'])) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Sesión no iniciada.'], 401)
                : redirect('/');
        }

        if (! modulo_autorizado($module)) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'No tienes permiso para usar este módulo.'], 403)
                : response()->view('errors.403', status: 403);
        }

        return $next($request);
    }
}
