<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Throwable;

final class LegacyApplicationController extends Controller
{
    /**
     * Ejecuta temporalmente un módulo aún no refactorizado sin permitir que
     * su salida directa rompa el ciclo de respuesta de Laravel.
     */
    public function __invoke(): Response
    {
        ob_start();

        try {
            require base_path('legacy/index.php');
            $content = (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        return response($content, http_response_code() ?: 200);
    }
}
