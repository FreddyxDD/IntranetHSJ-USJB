<?php

namespace App\Http\Middleware;

use App\Services\Identity\CentralAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureInstitutionalAuthentication
{
    public function __construct(private readonly CentralAccessService $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->access->isAuthenticated() || $this->access->confirmationPending()) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Sesión no iniciada.'], 401)
                : redirect('/');
        }

        return $next($request);
    }
}
