<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/app.php';

require_once BASE_PATH . '/app/controllers/UeeiAuthController.php';
require_once BASE_PATH . '/app/controllers/AdminUeeiController.php';
require_once BASE_PATH . '/app/controllers/CirugiasAuthController.php';
require_once BASE_PATH . '/app/controllers/UviAuthController.php';
require_once BASE_PATH . '/app/controllers/CirugiasController.php';
require_once BASE_PATH . '/app/controllers/ProduccionController.php';
require_once BASE_PATH . '/app/controllers/EficienciaController.php';
require_once BASE_PATH . '/app/controllers/CalidadController.php';
require_once BASE_PATH . '/app/controllers/UeeiPerfilController.php';
require_once BASE_PATH . '/app/helpers/modulos.php';
require_once BASE_PATH . '/app/controllers/CitasAdminController.php';

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
    if (empty($_SESSION['cirugias_usuario'])) {
        header('Location: ' . url_path('/cirugias-login'));
        exit;
    }
}

function require_cirugias_admin(): void
{
    require_cirugias_login();

    if ((int) ($_SESSION['cirugias_rol'] ?? 1) !== 0) {
        header('Location: ' . url_path('/principal-cirugias'));
        exit;
    }
}

/*
   NUEVA LÓGICA DE CITAS:
   Ya no se usa la sesión antigua $_SESSION['citas_admin_usuario'].
   Ahora Citas depende del login general del intranet y del permiso del módulo citas_admin.
*/
function require_citas_admin(): void
{
    require_modulo('citas_admin');
}

function require_citas_admin_api(): void
{
    require_modulo_api('citas_admin');
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

$citasRegistroId = null;

if (preg_match('#^/api/citas-admin/registros/(\d+)/estado$#', $uri, $m)) {
    $citasRegistroId = (int) $m[1];
}

$citaDiariaEstadoId = null;

if (preg_match('#^/api/citas-admin/citas-diarias/(\d+)/estado$#', $uri, $m)) {
    $citaDiariaEstadoId = (int) $m[1];
}

$citaDiariaPacientesId = null;

if (preg_match('#^/api/citas-admin/citas-diarias/(\d+)/pacientes$#', $uri, $m)) {
    $citaDiariaPacientesId = (int) $m[1];
}

/* ==============================
   IDS DINÁMICOS ADMIN UEeI
============================== */

$adminUeeiUsuarioId = null;

if (preg_match('#^/api/admin-ueei/usuarios/(\d+)$#', $uri, $m)) {
    $adminUeeiUsuarioId = (int) $m[1];
}

$adminUeeiUsuarioEstadoId = null;

if (preg_match('#^/api/admin-ueei/usuarios/(\d+)/estado$#', $uri, $m)) {
    $adminUeeiUsuarioEstadoId = (int) $m[1];
}

$adminUeeiUsuarioPasswordId = null;

if (preg_match('#^/api/admin-ueei/usuarios/(\d+)/password$#', $uri, $m)) {
    $adminUeeiUsuarioPasswordId = (int) $m[1];
}

/* ==============================
   RUTAS
============================== */

match (true) {

    /* ==============================
       LOGIN GENERAL UEEI
    ============================== */

    $method === 'GET' && $uri === '/'
        => require BASE_PATH . '/views/ueei/index.php',

    $method === 'POST' && $uri === '/crear-cuenta-ueei'
        => UeeiAuthController::register(),

    $method === 'POST' && $uri === '/login-ueei'
        => UeeiAuthController::login(),

    $method === 'GET' && $uri === '/me-ueei'
        => UeeiAuthController::me(),

    $method === 'POST' && $uri === '/logout-ueei'
        => UeeiAuthController::logout(),

    $method === 'GET' && $uri === '/admin-ueei'
        => (require_ueei_admin()) ?? require BASE_PATH . '/views/admin/admin-ueei.php',

    $method === 'GET' && ($uri === '/pages/principal.html' || $uri === '/pages/principal' || $uri === '/principal')
        => (require_ueei_login()) ?? require BASE_PATH . '/views/pages/principal.php',

    $method === 'GET' && ($uri === '/areas' || $uri === '/pages/Areas.html')
        => (require_ueei_login()) ?? require BASE_PATH . '/views/pages/areas.php',

    $method === 'GET' && ($uri === '/perfil' || $uri === '/pages/perfil.html' || $uri === '/pages/perfil')
        => UeeiPerfilController::index(),

    /* ==============================
       API ADMIN UEeI
    ============================== */

    $method === 'GET' && $uri === '/api/admin-ueei/resumen'
        => AdminUeeiController::resumen(),

    $method === 'GET' && $uri === '/api/admin-ueei/catalogos'
        => AdminUeeiController::catalogos(),

    $method === 'GET' && $uri === '/api/admin-ueei/usuarios'
        => AdminUeeiController::usuarios(),

    $method === 'POST' && $uri === '/api/admin-ueei/usuarios'
        => AdminUeeiController::crearUsuario(),

    $method === 'PUT' && $adminUeeiUsuarioId !== null
        => AdminUeeiController::actualizarUsuario($adminUeeiUsuarioId),

    $method === 'PATCH' && $adminUeeiUsuarioEstadoId !== null
        => AdminUeeiController::cambiarEstado($adminUeeiUsuarioEstadoId),

    $method === 'PATCH' && $adminUeeiUsuarioPasswordId !== null
        => AdminUeeiController::cambiarPassword($adminUeeiUsuarioPasswordId),

    /* ==============================
       MÓDULO INFORMACIÓN
    ============================== */

    $method === 'GET' && ($uri === '/informacion' || $uri === '/pages/informacion.html')
        => (require_modulo('informacion')) ?? require BASE_PATH . '/views/pages/informacion.php',

    /* ==============================
       MÓDULO UVI
    ============================== */

    $method === 'GET' && ($uri === '/uvi-login' || $uri === '/pages/UVILogin.html')
        => (require_modulo('uvi')) ?? require BASE_PATH . '/views/pages/uvi-login.php',

    $method === 'GET' && ($uri === '/admin-uvi' || $uri === '/pages/AdminUVI.html')
        => (require_modulo('uvi')) ?? require BASE_PATH . '/views/pages/admin-uvi.php',

    $method === 'POST' && $uri === '/login-uvi'
        => UviAuthController::login(),

    $method === 'POST' && $uri === '/logout-uvi'
        => UviAuthController::logout(),

    $method === 'GET' && $uri === '/usuarios-uvi'
        => (function (): void {
            require_modulo_api('uvi');
            UviAuthController::listarUsuarios();
        })(),

    $method === 'POST' && $uri === '/crear-cuenta-uvi'
        => (function (): void {
            require_modulo_api('uvi');
            UviAuthController::crearCuenta();
        })(),

    $method === 'PUT' && preg_match('#^/usuarios-uvi/(\d+)$#', $uri, $m)
        => (function () use ($m): void {
            require_modulo_api('uvi');
            UviAuthController::actualizarUsuario((int) $m[1]);
        })(),

    $method === 'PATCH' && preg_match('#^/usuarios-uvi/(\d+)/estado$#', $uri, $m)
        => (function () use ($m): void {
            require_modulo_api('uvi');
            UviAuthController::cambiarEstadoUsuario((int) $m[1]);
        })(),

    /* ==============================
       INDICADORES PRODUCCIÓN
    ============================== */

    $method === 'GET' && ($uri === '/produccion' || $uri === '/pages/produccion.html')
        => (require_modulo('produccion')) ?? require BASE_PATH . '/views/pages/produccion.php',

    $method === 'GET' && $uri === '/indicadores/produccion'
        => (function (): void {
            require_modulo_api('produccion');
            ProduccionController::produccion();
        })(),

    /* ==============================
       INDICADORES EFICIENCIA
    ============================== */

    $method === 'GET' && ($uri === '/eficiencia' || $uri === '/pages/eficiencia.html')
        => (require_modulo('eficiencia')) ?? require BASE_PATH . '/views/pages/eficiencia.php',

    $method === 'GET' && $uri === '/indicadores/eficiencia'
        => (function (): void {
            require_modulo_api('eficiencia');
            EficienciaController::listar();
        })(),

    $method === 'GET' && $uri === '/admin/indicadores/eficiencia'
        => (function (): void {
            require_modulo_api('eficiencia');
            EficienciaController::listarAdmin();
        })(),

    $method === 'PUT' && $uri === '/admin/indicadores/eficiencia'
        => (function (): void {
            require_modulo_api('eficiencia');
            EficienciaController::actualizar();
        })(),

    /* ==============================
       INDICADORES CALIDAD
    ============================== */

    $method === 'GET' && ($uri === '/calidad' || $uri === '/pages/calidad.html')
        => (require_modulo('calidad')) ?? require BASE_PATH . '/views/pages/calidad.php',

    $method === 'GET' && $uri === '/indicadores/calidad'
        => (function (): void {
            require_modulo_api('calidad');
            CalidadController::listar();
        })(),

    $method === 'GET' && $uri === '/admin/indicadores/calidad'
        => (function (): void {
            require_modulo_api('calidad');
            CalidadController::listarAdmin();
        })(),

    $method === 'PUT' && $uri === '/admin/indicadores/calidad'
        => (function (): void {
            require_modulo_api('calidad');
            CalidadController::actualizar();
        })(),

    /* ==============================
       MÓDULO CIRUGÍAS - LOGIN
    ============================== */

    $method === 'GET' && ($uri === '/cirugias-login' || $uri === '/pages/LoginLS.html')
        => (require_modulo('cirugias')) ?? require BASE_PATH . '/views/pages/cirugias-login.php',

    $method === 'POST' && $uri === '/login-ls'
        => CirugiasAuthController::login(),

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
        => (require_modulo('cirugias')) ?? (require_cirugias_admin()) ?? require BASE_PATH . '/views/pages/cirugias-admin.php',

    $method === 'GET' && ($uri === '/manual-cirugias' || $uri === '/pages/manualLS.html')
        => require BASE_PATH . '/views/pages/manual-cirugias.php',

    /* ==============================
       MÓDULO CIRUGÍAS - API ADMIN USUARIOS
    ============================== */

    $method === 'GET' && $uri === '/api/cirugias/usuarios'
        => CirugiasAuthController::listarUsuarios(),

    $method === 'POST' && ($uri === '/api/cirugias/usuarios' || $uri === '/cirugias/usuarios/crear')
        => CirugiasAuthController::crearUsuario(),

    $method === 'POST' && $uri === '/api/cirugias/usuarios/estado'
        => CirugiasAuthController::cambiarEstadoUsuario(),

    $method === 'POST' && $uri === '/api/cirugias/usuarios/eliminar'
        => CirugiasAuthController::eliminarUsuario(),

    /* ==============================
       MÓDULO CIRUGÍAS - API PRINCIPAL
    ============================== */

    $method === 'GET' && $uri === '/cirugias'
        => CirugiasController::listar(),

    $method === 'POST' && $uri === '/cirugias-manual'
        => CirugiasController::crearManual(),

    $method === 'PUT' && $cirugiaId !== null
        => CirugiasController::actualizar($cirugiaId),

    $method === 'DELETE' && $uri === '/cirugias'
        => CirugiasController::eliminarTodo(),

    $method === 'GET' && $uri === '/cirugias-resumen'
        => CirugiasController::resumen(),

    $method === 'GET' && $uri === '/cirugias-hojas'
        => CirugiasController::hojas(),

    $method === 'POST' && $uri === '/excel-hojas'
        => CirugiasController::excelHojas(),

    $method === 'POST' && $uri === '/importar-cirugias'
        => CirugiasController::importarExcel(),

    ($method === 'GET' || $method === 'POST') && $uri === '/especialidades'
        => CirugiasController::especialidades(),

    $method === 'GET' && ($uri === '/cie10' || $uri === '/cie10/buscar')
        => CirugiasController::cie10(),

    $method === 'GET' && $uri === '/cie10/estados'
        => CirugiasController::cie10Estados(),

    $method === 'GET' && $uri === '/cie10/sexos'
        => CirugiasController::cie10Sexos(),

    $method === 'GET' && $uri === '/personal-medico'
        => CirugiasController::personalMedico(),

    $method === 'POST' && $uri === '/personal-medico'
        => CirugiasController::crearPersonalMedico(),

    $method === 'PUT' && $personalId !== null
        => CirugiasController::actualizarPersonalMedico($personalId),

    $method === 'PUT' && $personalEstadoId !== null
        => CirugiasController::cambiarEstadoPersonal($personalEstadoId),

    $method === 'GET' && $uri === '/personal-medico/profesiones'
        => CirugiasController::personalProfesiones(),

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
        => CirugiasController::analisisMeses(),

    $method === 'GET' && $uri === '/api/analisis/cirugias-mensual'
        => CirugiasController::analisisMensual(),

    $method === 'GET' && $uri === '/api/analisis/tipo-orden'
        => CirugiasController::analisisTipoOrden(),

    $method === 'GET' && $uri === '/api/analisis/resumen-periodo'
        => CirugiasController::analisisResumenPeriodo(),

    $method === 'GET' && $uri === '/api/analisis/mayor-menor-electiva'
        => CirugiasController::analisisMayorMenorElectiva(),

    $method === 'GET' && $uri === '/api/analisis/especialidades'
        => CirugiasController::analisisEspecialidades(),

    $method === 'GET' && $uri === '/api/analisis/detalle-especialidad'
        => CirugiasController::analisisDetalleEspecialidad(),

    $method === 'GET' && $uri === '/api/reportes/meses-disponibles'
        => CirugiasController::reportesMeses(),

    $method === 'GET' && $uri === '/api/reportes/cirugias-mensual'
        => CirugiasController::reporteMensual(),

    $method === 'GET' && ($uri === '/tablas-sigh' || $uri === '/api/tablas-sigh')
        => CirugiasController::tablasSigh(),

    $method === 'GET' && preg_match('#^/pacientes/dni/(\d{8})$#', $uri, $m)
        => CirugiasController::pacientes(),

    /* ==============================
       MÓDULO CITAS ADMIN
       Acceso único desde login general del intranet.
       Se elimina /citas-admin-login y el login propio de Citas.
    ============================== */

    $method === 'GET' && ($uri === '/citas-admin' || $uri === '/pages/CitasAdmi.html')
        => (require_citas_admin()) ?? require BASE_PATH . '/views/pages/citas-admin.php',

    $method === 'GET' && $uri === '/api/citas-admin/registros'
        => (function (): void {
            require_citas_admin_api();
            CitasAdminController::registros();
        })(),

    $method === 'PUT' && $citasRegistroId !== null
        => (function () use ($citasRegistroId): void {
            require_citas_admin_api();
            CitasAdminController::actualizarEstado($citasRegistroId);
        })(),

    $method === 'GET' && $uri === '/api/citas-admin/citas-diarias'
        => (function (): void {
            require_citas_admin_api();
            CitasAdminController::citasDiarias();
        })(),

    $method === 'GET' && $uri === '/api/citas-admin/reportes'
        => (function (): void {
            require_citas_admin_api();
            CitasAdminController::reportes();
        })(),

    $method === 'PUT' && $citaDiariaEstadoId !== null
        => (function () use ($citaDiariaEstadoId): void {
            require_citas_admin_api();
            CitasAdminController::actualizarEstadoCitaDiaria($citaDiariaEstadoId);
        })(),

    $method === 'GET' && $citaDiariaPacientesId !== null
        => (function () use ($citaDiariaPacientesId): void {
            require_citas_admin_api();
            CitasAdminController::pacientesCitaDiaria($citaDiariaPacientesId);
        })(),

    default => (function (): void {
        http_response_code(404);
        echo 'Página no encontrada';
    })(),
};
