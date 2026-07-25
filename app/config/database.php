<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONEXIÓN A BASE DE DATOS
|--------------------------------------------------------------------------
| db()      => MySQL local del sistema intranetHSJ
| db_sigh() => SQL Server remoto SIGH
|--------------------------------------------------------------------------
*/

/* =========================================================
   MYSQL LOCAL - SISTEMA INTRANET
========================================================= */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) env('LEGACY_DB_HOST', '127.0.0.1');
    $port = (string) env('LEGACY_DB_PORT', '3306');
    $dbname = (string) env('LEGACY_DB_DATABASE', 'hospital_ueei');
    $user = (string) env('LEGACY_DB_USERNAME', '');
    $pass = (string) env('LEGACY_DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;

    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'ok' => false,
            'message' => 'Error de conexión a MySQL local.',
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/* =========================================================
   SQL SERVER REMOTO - SIGH
========================================================= */
function db_sigh(): PDO
{
    static $pdoSigh = null;

    if ($pdoSigh instanceof PDO) {
        return $pdoSigh;
    }

    $server = (string) env('SIGH_DB_HOST', '127.0.0.1');
    $database = (string) env('SIGH_DB_DATABASE', 'SIGH');
    $user = (string) env('SIGH_DB_USERNAME', '');
    $pass = (string) env('SIGH_DB_PASSWORD', '');

    /*
    Para SQL Server 2012 normalmente ayuda usar Encrypt=no.
    */
    $dsn = "sqlsrv:Server={$server};Database={$database};Encrypt=no;TrustServerCertificate=yes";

    try {
        $pdoSigh = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdoSigh;

    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'ok' => false,
            'message' => 'Error de conexión a SQL Server SIGH.',
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

/* =========================================================
   MYSQL REMOTO - BASE CITAS RESERVAS
========================================================= */
function db_citas(): PDO
{
    static $pdoCitas = null;

    if ($pdoCitas instanceof PDO) {
        return $pdoCitas;
    }

    $host = (string) env('CITAS_LEGACY_DB_HOST', '127.0.0.1');
    $port = (string) env('CITAS_LEGACY_DB_PORT', '3306');
    $dbname = (string) env('CITAS_LEGACY_DB_DATABASE', 'citas');
    $user = (string) env('CITAS_LEGACY_DB_USERNAME', '');
    $pass = (string) env('CITAS_LEGACY_DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    try {
        $pdoCitas = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
        ]);

        return $pdoCitas;

    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'ok' => false,
            'message' => 'Error de conexión a MySQL citas remoto.',
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
