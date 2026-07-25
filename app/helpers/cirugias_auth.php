<?php
declare(strict_types=1);

if (!function_exists('cirugias_session_start')) {
    function cirugias_session_start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('iniciar_sesion_segura')) {
                iniciar_sesion_segura();
            } else {
                session_start();
            }
        }
    }
}

if (!function_exists('cirugias_path')) {
    function cirugias_path(string $path): string
    {
        return function_exists('url_path') ? url_path($path) : $path;
    }
}

if (!function_exists('cirugias_json')) {
    function cirugias_json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('cirugias_input')) {
    function cirugias_input(): array
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            return $json;
        }

        return $_POST;
    }
}

if (!function_exists('cirugias_logueado')) {
    function cirugias_logueado(): bool
    {
        cirugias_session_start();
        return isset($_SESSION['cirugias_usuario_id']);
    }
}

if (!function_exists('cirugias_es_admin')) {
    function cirugias_es_admin(): bool
    {
        cirugias_session_start();
        return isset($_SESSION['cirugias_rol']) && (int) $_SESSION['cirugias_rol'] === 0;
    }
}

if (!function_exists('cirugias_require_login_page')) {
    function cirugias_require_login_page(): void
    {
        cirugias_session_start();

        if (!cirugias_logueado()) {
            header('Location: ' . cirugias_path('/login-ls'));
            exit;
        }
    }
}

if (!function_exists('cirugias_require_admin_page')) {
    function cirugias_require_admin_page(): void
    {
        cirugias_session_start();

        if (!cirugias_logueado()) {
            header('Location: ' . cirugias_path('/login-ls'));
            exit;
        }

        if (!cirugias_es_admin()) {
            header('Location: ' . cirugias_path('/principal-cirugias'));
            exit;
        }
    }
}

if (!function_exists('cirugias_require_admin_api')) {
    function cirugias_require_admin_api(): void
    {
        cirugias_session_start();

        if (!cirugias_logueado()) {
            cirugias_json([
                'ok' => false,
                'message' => 'Sesión no iniciada.'
            ], 401);
        }

        if (!cirugias_es_admin()) {
            cirugias_json([
                'ok' => false,
                'message' => 'No tienes permiso para realizar esta acción.'
            ], 403);
        }
    }
}