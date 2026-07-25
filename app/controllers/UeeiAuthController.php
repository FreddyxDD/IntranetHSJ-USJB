<?php
declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

require_once BASE_PATH . '/app/helpers/response.php';
require_once BASE_PATH . '/app/helpers/modulos.php';

final class UeeiAuthController
{
    public static function me(): void
    {
        $userId = (int) ($_SESSION['ueei_id'] ?? 0);

        if ($userId <= 0) {
            json_response(['ok' => false, 'message' => 'No autenticado UEeI'], 401);
        }

        $user = self::userQuery()->find($userId);

        if (! $user || ! $user->activo) {
            self::destruirSesion();
            json_response(['ok' => false, 'message' => 'Sesión UEeI inválida o expirada.'], 401);
        }

        self::establecerSesion($user);
        self::respondAuthenticated($user);
    }

    public static function register(): void
    {
        json_response([
            'success' => false,
            'ok' => false,
            'message' => 'El registro público está deshabilitado. Solicita tu cuenta al administrador del intranet.',
        ], 403);
    }

    public static function login(): void
    {
        $input = get_json_input();
        $email = normalize_email($input['correo'] ?? '');
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '') {
            json_response(['success' => false, 'ok' => false, 'message' => 'Completa todos los campos.'], 400);
        }

        if (! valid_email($email) || strlen($password) > 200) {
            self::genericAuthError();
        }

        $user = self::userQuery()->where('email', $email)->first();

        if (! $user || ! $user->activo || ! Hash::check($password, (string) $user->password)) {
            self::genericAuthError();
        }

        if ($user->accessAccount && $user->accessAccount->status !== 'active') {
            self::genericAuthError();
        }

        session_regenerate_id(true);
        self::establecerSesion($user);

        if ($user->accessAccount) {
            $user->accessAccount->forceFill(['last_login_at' => now()])->save();
        }

        self::respondAuthenticated($user, 'Inicio de sesión correcto.');
    }

    public static function logout(): void
    {
        self::destruirSesion();
        json_response(['ok' => true, 'success' => true, 'message' => 'Sesión cerrada correctamente']);
    }

    private static function userQuery()
    {
        return User::query()->with([
            'accessAccount.roles.application',
            'accessAccount.roles.permissions.application',
        ]);
    }

    private static function establecerSesion(User $user): void
    {
        $application = (string) config('access.application');
        $applicationRoles = $user->accessAccount?->roles
            ->filter(fn ($role): bool => $role->application?->code === $application && $role->application?->is_active)
            ->values() ?? collect();
        $roleCodes = $applicationRoles->pluck('code')->values()->all();
        $role = $user->hasRole('administrador') ? 'admin' : ($roleCodes[0] ?? $user->rol ?? 'consulta');

        $_SESSION['ueei_id'] = (int) $user->id;
        $_SESSION['ueei_correo'] = (string) $user->email;
        $_SESSION['ueei_nombre'] = (string) $user->name;
        $_SESSION['ueei_rol'] = $role;
        $_SESSION['ueei_area_id'] = null;
        $_SESSION['identity_roles'] = $roleCodes;
        $_SESSION['identity_permissions'] = $applicationRoles
            ->flatMap->permissions
            ->filter(fn ($permission): bool => $permission->application?->code === $application)
            ->pluck('code')
            ->unique()
            ->values()
            ->all() ?? [];
    }

    public static function refrescarSesion(User $user): void
    {
        self::establecerSesion($user);
    }

    private static function respondAuthenticated(User $user, ?string $message = null): never
    {
        $payload = [
            'success' => true,
            'ok' => true,
            'id' => (int) $user->id,
            'nombre' => (string) $user->name,
            'correo' => (string) $user->email,
            'rol' => (string) $_SESSION['ueei_rol'],
            'area_id' => null,
            'roles' => $_SESSION['identity_roles'],
            'permisos' => $_SESSION['identity_permissions'],
            'modulos' => modulos_autorizados(),
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        json_response($payload);
    }

    private static function destruirSesion(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
        }

        session_destroy();
    }

    private static function genericAuthError(): never
    {
        json_response(['success' => false, 'ok' => false, 'message' => 'Credenciales inválidas.'], 401);
    }
}
