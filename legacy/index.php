<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/app.php';

require_once BASE_PATH . '/app/controllers/CirugiasAuthController.php';
require_once BASE_PATH . '/app/controllers/CirugiasController.php';
require_once BASE_PATH . '/app/helpers/modulos.php';

iniciar_sesion_segura();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = app_base();

if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}

$uri = '/' . trim($uri, '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($uri === '/') {
    $uri = '/';
}

/* ==============================
   FUNCIONES DE SEGURIDAD
============================== */

function require_ueei_login(): void
{
    if (empty($_SESSION['ueei_correo'])) {
        header('Location: ' . url_path('/'));
        exit;
    }

    if (!empty($_SESSION['account_confirmation_pending'])) {
        header('Location: ' . url_path('/'));
        exit;
    }
}

function require_ueei_admin(): void
{
    require_ueei_login();

    if (($_SESSION['ueei_rol'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
}

function require_cirugias_login(): void
{
    require_modulo('cirugias');
    CirugiasAuthController::bootstrapCentralSession();
}

function require_cirugias_admin(): void
{
    require_cirugias_login();

    if (! ueei_usuario_es_admin()) {
        header('Location: ' . url_path('/principal-cirugias'));
        exit;
    }
}

function require_cirugias_permission_api(string $permission): void
{
    require_modulo_api('cirugias');
    require_permiso_api($permission);
}

/* ==============================
   IDS DINÁMICOS
============================== */

$cirugiaId = null;

if (preg_match('#^/cirugias/(\d+)$#', $uri, $m)) {
    $cirugiaId = (int) $m[1];
}

$personalId = null;

if (preg_match('#^/personal-medico/(\d+)$#', $uri, $m)) {
    $personalId = (int) $m[1];
}

$personalEstadoId = null;

if (preg_match('#^/personal-medico/(\d+)/estado$#', $uri, $m)) {
    $personalEstadoId = (int) $m[1];
}

/* ==============================
   RUTAS
============================== */

match (true) {

    /* ==============================
       MÓDULO CIRUGÍAS - LOGIN
    ============================== */

    $method === 'GET' && ($uri === '/cirugias-login' || $uri === '/pages/LoginLS.html')
        => (function (): void {
            require_cirugias_login();
            header('Location: ' . url_path(CirugiasAuthController::centralDestination()));
            exit;
        })(),

    $method === 'GET' && ($uri === '/me-ls' || $uri === '/me-cirugias')
        => CirugiasAuthController::me(),

    $method === 'POST' && ($uri === '/logout-ls' || $uri === '/logout-cirugias')
        => CirugiasAuthController::logout(),

    /* ==============================
       MÓDULO CIRUGÍAS - PÁGINAS
    ============================== */

    $method === 'GET' && ($uri === '/principal-cirugias' || $uri === '/pages/principalLS.html')
        => (require_modulo('cirugias')) ?? (require_cirugias_login()) ?? require BASE_PATH . '/views/pages/principal-cirugias.php',

    $method === 'GET' && $uri === '/cirugias-admin'
        => (function (): void {
            require_ueei_admin();
            header('Location: ' . url_path('/admin-ueei'));
            exit;
        })(),

    $method === 'GET' && ($uri === '/manual-cirugias' || $uri === '/pages/manualLS.html')
        => (require_modulo('cirugias')) ?? require BASE_PATH . '/views/pages/manual-cirugias.php',

    /* ==============================
       MÓDULO CIRUGÍAS - API PRINCIPAL
    ============================== */

    $method === 'GET' && $uri === '/cirugias'
        => CirugiasController::listar(),

    $method === 'POST' && $uri === '/cirugias-manual'
        => (function (): void {
            require_cirugias_permission_api('cirugias.records.manage');
            CirugiasController::crearManual();
        })(),

    $method === 'PUT' && $cirugiaId !== null
        => (function () use ($cirugiaId): void {
            require_cirugias_permission_api('cirugias.records.manage');
            CirugiasController::actualizar($cirugiaId);
        })(),

    $method === 'DELETE' && $uri === '/cirugias'
        => (function (): void {
            require_cirugias_permission_api('cirugias.imports.manage');
            CirugiasController::eliminarTodo();
        })(),

    $method === 'GET' && $uri === '/cirugias-resumen'
        => CirugiasController::resumen(),

    $method === 'GET' && $uri === '/cirugias-hojas'
        => CirugiasController::hojas(),

    $method === 'POST' && $uri === '/excel-hojas'
        => (function (): void {
            require_cirugias_permission_api('cirugias.imports.manage');
            CirugiasController::excelHojas();
        })(),

    $method === 'POST' && $uri === '/importar-cirugias'
        => (function (): void {
            require_cirugias_permission_api('cirugias.imports.manage');
            CirugiasController::importarExcel();
        })(),

    ($method === 'GET' || $method === 'POST') && $uri === '/especialidades'
        => (function () use ($method): void {
            if ($method === 'POST') {
                require_cirugias_permission_api('cirugias.records.manage');
            }
            CirugiasController::especialidades();
        })(),

    $method === 'GET' && ($uri === '/cie10' || $uri === '/cie10/buscar')
        => CirugiasController::cie10(),

    $method === 'GET' && $uri === '/cie10/estados'
        => CirugiasController::cie10Estados(),

    $method === 'GET' && $uri === '/cie10/sexos'
        => CirugiasController::cie10Sexos(),

    $method === 'GET' && $uri === '/personal-medico'
        => (function (): void {
            require_cirugias_permission_api('cirugias.staff.manage');
            CirugiasController::personalMedico();
        })(),

    $method === 'POST' && $uri === '/personal-medico'
        => (function (): void {
            require_cirugias_permission_api('cirugias.staff.manage');
            CirugiasController::crearPersonalMedico();
        })(),

    $method === 'PUT' && $personalId !== null
        => (function () use ($personalId): void {
            require_cirugias_permission_api('cirugias.staff.manage');
            CirugiasController::actualizarPersonalMedico($personalId);
        })(),

    $method === 'PUT' && $personalEstadoId !== null
        => (function () use ($personalEstadoId): void {
            require_cirugias_permission_api('cirugias.staff.manage');
            CirugiasController::cambiarEstadoPersonal($personalEstadoId);
        })(),

    $method === 'GET' && $uri === '/personal-medico/profesiones'
        => (function (): void {
            require_cirugias_permission_api('cirugias.staff.manage');
            CirugiasController::personalProfesiones();
        })(),

    $method === 'GET' && ($uri === '/pacientes' || $uri === '/pacientes/buscar')
        => CirugiasController::pacientes(),

    $method === 'GET' && $uri === '/procedimientos'
        => CirugiasController::procedimientos(),

    $method === 'GET' && $uri === '/procedimientos/secciones'
        => CirugiasController::procedimientosSecciones(),

    $method === 'GET' && ($uri === '/procedimientos/sugerencias' || $uri === '/sigh/procedimientos/sugerencias')
        => CirugiasController::procedimientos(),

    $method === 'GET' && $uri === '/sigh/operacion-por-cie10'
        => CirugiasController::operacionPorCie10(),

    $method === 'GET' && $uri === '/api/importaciones'
        => CirugiasController::importaciones(),

    $method === 'GET' && $uri === '/api/analisis/meses-disponibles'
        => (function (): void {
            require_cirugias_permission_api('cirugias.analytics.view');
            CirugiasController::analisisMeses();
        })(),

    $method === 'GET' && $uri === '/api/analisis/cirugias-mensual'
        => (function (): void {
            require_cirugias_permission_api('cirugias.analytics.view');
            CirugiasController::analisisMensual();
        })(),

    $method === 'GET' && $uri === '/api/analisis/tipo-orden'
        => (function (): void {
            require_cirugias_permission_api('cirugias.analytics.view');
            CirugiasController::analisisTipoOrden();
        })(),

    $method === 'GET' && $uri === '/api/analisis/resumen-periodo'
        => (function (): void {
            require_cirugias_permission_api('cirugias.analytics.view');
            CirugiasController::analisisResumenPeriodo();
        })(),

    $method === 'GET' && $uri === '/api/analisis/mayor-menor-electiva'
        => (function (): void {
            require_cirugias_permission_api('cirugias.analytics.view');
            CirugiasController::analisisMayorMenorElectiva();
        })(),

    $method === 'GET' && $uri === '/api/analisis/especialidades'
        => (function (): void {
            require_cirugias_permission_api('cirugias.analytics.view');
            CirugiasController::analisisEspecialidades();
        })(),

    $method === 'GET' && $uri === '/api/analisis/detalle-especialidad'
        => (function (): void {
            require_cirugias_permission_api('cirugias.analytics.view');
            CirugiasController::analisisDetalleEspecialidad();
        })(),

    $method === 'GET' && $uri === '/api/reportes/meses-disponibles'
        => (function (): void {
            require_cirugias_permission_api('cirugias.reports.view');
            CirugiasController::reportesMeses();
        })(),

    $method === 'GET' && $uri === '/api/reportes/cirugias-mensual'
        => (function (): void {
            require_cirugias_permission_api('cirugias.reports.view');
            CirugiasController::reporteMensual();
        })(),

    $method === 'GET' && ($uri === '/tablas-sigh' || $uri === '/api/tablas-sigh')
        => CirugiasController::tablasSigh(),

    $method === 'GET' && preg_match('#^/pacientes/dni/(\d{8})$#', $uri, $m)
        => CirugiasController::pacientes(),

    default => (function (): void {
        http_response_code(404);
        echo 'Página no encontrada';
    })(),
};
