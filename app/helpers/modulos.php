<?php
declare(strict_types=1);

use App\Models\User;

function intranet_module_catalog(): array
{
    return [
        'informacion' => ['id' => 2, 'codigo' => 'informacion', 'nombre' => 'Información', 'descripcion' => 'Información institucional del Hospital San José.', 'ruta' => '/informacion', 'icono' => '/assets/icon/InforHSJ.png'],
        'citas_admin' => ['id' => 1, 'codigo' => 'citas_admin', 'nombre' => 'Citas', 'descripcion' => 'Administración de citas y registros.', 'ruta' => '/citas-admin', 'icono' => '/assets/icon/CitasLog.png'],
        'cirugias' => ['id' => 3, 'codigo' => 'cirugias', 'nombre' => 'Cirugías', 'descripcion' => 'Registro, control y análisis de cirugías.', 'ruta' => '/cirugias-login', 'icono' => '/assets/icon/CirugiasLog.png'],
        'uvi' => ['id' => 4, 'codigo' => 'uvi', 'nombre' => 'UVI', 'descripcion' => 'Administración del módulo UVI.', 'ruta' => '/uvi-login', 'icono' => '/assets/icon/UVIlo.png'],
        'produccion' => ['id' => 5, 'codigo' => 'produccion', 'nombre' => 'Producción', 'descripcion' => 'Indicadores de producción y rendimiento.', 'ruta' => '/produccion', 'icono' => '/assets/icon/Total_cirugias.png'],
        'eficiencia' => ['id' => 6, 'codigo' => 'eficiencia', 'nombre' => 'Eficiencia', 'descripcion' => 'Indicadores de eficiencia hospitalaria.', 'ruta' => '/eficiencia', 'icono' => '/assets/icon/Tasa_Urgencia.png'],
        'calidad' => ['id' => 7, 'codigo' => 'calidad', 'nombre' => 'Calidad', 'descripcion' => 'Indicadores de calidad institucional.', 'ruta' => '/calidad', 'icono' => '/assets/icon/Segura.png'],
    ];
}

function intranet_module_permission_map(): array
{
    return [
        'informacion' => ['dashboard.view'],
        'citas_admin' => ['citas.view'],
        'cirugias' => ['cirugias.view'],
        'uvi' => ['uvi.view'],
        'produccion' => ['produccion.view'],
        'eficiencia' => ['eficiencia.view'],
        'calidad' => ['calidad.view'],
    ];
}

function ueei_usuario_es_admin(?string $rol = null): bool
{
    $rol = $rol ?? (string) ($_SESSION['ueei_rol'] ?? '');
    $roles = $_SESSION['identity_roles'] ?? [];

    return $rol === 'admin' || in_array('administrador', is_array($roles) ? $roles : [], true);
}

function ueei_cuenta_id_sesion(): int
{
    return (int) ($_SESSION['ueei_id'] ?? 0);
}

function modulos_todos_activos(): array
{
    return array_values(intranet_module_catalog());
}

function modulos_por_cuenta(int $cuentaId): array
{
    if ($cuentaId <= 0) {
        return [];
    }

    $user = User::query()->with([
        'accessAccount.roles.application',
        'accessAccount.roles.permissions.application',
    ])->find($cuentaId);

    if (! $user || ! $user->activo) {
        return [];
    }

    if ($user->hasRole('administrador')) {
        return modulos_todos_activos();
    }

    $application = (string) config('access.application');
    $permissions = $user->accessAccount?->roles
        ->filter(fn ($role): bool => $role->application?->code === $application && $role->application?->is_active)
        ->flatMap->permissions
        ->filter(fn ($permission): bool => $permission->application?->code === $application)
        ->pluck('code')
        ->unique()
        ->all() ?? [];

    $catalog = intranet_module_catalog();
    $mapping = intranet_module_permission_map();
    $modules = [];

    foreach ($mapping as $module => $requiredPermissions) {
        if (array_intersect($requiredPermissions, $permissions) !== []) {
            $modules[] = $catalog[$module];
        }
    }

    return $modules;
}

function modulos_por_area(int $areaId): array
{
    return [];
}

function modulos_autorizados(?int $areaId = null, ?string $rol = null, ?int $cuentaId = null): array
{
    if (ueei_usuario_es_admin($rol)) {
        return modulos_todos_activos();
    }

    return modulos_por_cuenta($cuentaId ?: ueei_cuenta_id_sesion());
}

function modulo_autorizado(string $codigo): bool
{
    if (empty($_SESSION['ueei_id'])) {
        return false;
    }

    if (ueei_usuario_es_admin()) {
        return array_key_exists($codigo, intranet_module_catalog());
    }

    foreach (modulos_autorizados() as $module) {
        if (($module['codigo'] ?? '') === $codigo) {
            return true;
        }
    }

    return false;
}

function require_modulo(string $codigo): void
{
    require_ueei_login();

    if (modulo_autorizado($codigo)) {
        return;
    }

    http_response_code(403);
    require file_exists(BASE_PATH.'/views/errors/403.php')
        ? BASE_PATH.'/views/errors/403.php'
        : BASE_PATH.'/resources/views/errors/403.blade.php';
    exit;
}

function require_modulo_api(string $codigo): void
{
    if (empty($_SESSION['ueei_id'])) {
        json_response(['ok' => false, 'message' => 'Sesión no iniciada.'], 401);
    }

    if (! modulo_autorizado($codigo)) {
        json_response(['ok' => false, 'message' => 'No tienes permiso para usar este módulo.'], 403);
    }
}

function modulo_habilitado(array $modulos, string $codigo): bool
{
    foreach ($modulos as $modulo) {
        if (($modulo['codigo'] ?? '') === $codigo) {
            return true;
        }
    }

    return false;
}
