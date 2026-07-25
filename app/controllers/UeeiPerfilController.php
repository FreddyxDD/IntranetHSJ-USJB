<?php
declare(strict_types=1);

use App\Models\User;

require_once BASE_PATH . '/app/helpers/response.php';

final class UeeiPerfilController
{
    private static function usuarioActual(): ?array
    {
        $userId = (int) ($_SESSION['ueei_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $user = User::query()->with('accessAccount.roles')->find($userId);
        if (! $user || ! $user->activo || ($user->accessAccount && $user->accessAccount->status !== 'active')) {
            self::limpiarSesion();
            return null;
        }

        $role = (string) ($_SESSION['ueei_rol'] ?? $user->rol ?? 'trabajador');

        return [
            'id' => (int) $user->id,
            'nombre' => (string) $user->name,
            'correo' => (string) $user->email,
            'rol' => $role,
            'rol_texto' => self::nombreRol($role),
            'estado' => 1,
            'estado_texto' => 'Activo',
            'fecha_creacion' => optional($user->created_at)->format('Y-m-d H:i:s'),
            'fecha_actualizacion' => optional($user->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    private static function nombreRol(string $role): string
    {
        return match (strtolower($role)) {
            'admin', 'administrador' => 'Administrador',
            'supervisor' => 'Supervisor',
            'impresion' => 'Impresión',
            'consulta', 'trabajador', 'usuario' => 'Personal autorizado',
            default => ucfirst($role),
        };
    }

    private static function limpiarSesion(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
        }
        session_destroy();
    }

    private static function redirect(string $path): never
    {
        header('Location: '.(function_exists('url_path') ? url_path($path) : $path));
        exit;
    }

    public static function index(): void
    {
        $usuario = self::usuarioActual();
        if (! $usuario) {
            self::redirect('/');
        }
        require BASE_PATH . '/views/pages/perfil.php';
    }

    public static function me(): void
    {
        $usuario = self::usuarioActual();
        if (! $usuario) {
            json_response(['ok' => false, 'message' => 'No autenticado'], 401);
        }
        json_response(['ok' => true, 'usuario' => $usuario]);
    }
}
