<?php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

define('APP_NAME', 'UEeI | Hospital San José');
define('BASE_PATH', dirname(__DIR__, 2));

/*
|--------------------------------------------------------------------------
| CONEXIÓN AL MÓDULO PÚBLICO DE CITAS
|--------------------------------------------------------------------------
| Esta base "citas" es la que usa el proyecto Citas_Hospital_PHP.
| Ahí se guardan los registros enviados desde la página pública.
*/
define('CITAS_MYSQL_HOST', '127.0.0.1');
define('CITAS_MYSQL_PORT', '3306');
define('CITAS_MYSQL_DB', 'citas');
define('CITAS_MYSQL_USER', 'root');
define('CITAS_MYSQL_PASSWORD', '');

function app_base(): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    if ($base === '' || $base === '/') {
        return '';
    }

    return $base;
}

function url_path(string $path = ''): string
{
    return app_base() . '/' . ltrim($path, '/');
}

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('hospital_sid');

    session_set_cookie_params([
        'lifetime' => 60 * 60 * 4,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    }
}
