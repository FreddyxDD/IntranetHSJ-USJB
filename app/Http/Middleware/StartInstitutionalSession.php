<?php

namespace App\Http\Middleware;

use App\Support\InstitutionalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StartInstitutionalSession
{
    public function __construct(private readonly InstitutionalSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->session->start();

        return $next($request);
    }
}
