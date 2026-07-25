<?php
declare(strict_types=1);

use App\Models\AccessAccount;
use App\Models\AccessRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require_once BASE_PATH . '/app/helpers/response.php';
require_once BASE_PATH . '/app/helpers/modulos.php';

final class AdminUeeiController
{
    private const LEGACY_ROLES = ['admin', 'director', 'supervisor', 'trabajador'];

    private const ROLE_MAP = [
        'admin' => 'administrador',
        'director' => 'indicadores',
        'supervisor' => 'consulta_citas',
        'trabajador' => 'consulta',
    ];

    private static function requireAdmin(): void
    {
        if (empty($_SESSION['ueei_id'])) {
            json_response(['ok' => false, 'message' => 'Sesión no iniciada.'], 401);
        }

        if (! ueei_usuario_es_admin()) {
            json_response(['ok' => false, 'message' => 'No tienes permisos de administrador.'], 403);
        }
    }

    public static function resumen(): void
    {
        self::requireAdmin();

        json_response(['ok' => true, 'data' => [
            'totalUsuarios' => User::query()->count(),
            'usuariosActivos' => User::query()->where('activo', true)->count(),
            'usuariosInactivos' => User::query()->where('activo', false)->count(),
            'totalAreas' => self::rolesQuery()->count(),
            'totalModulos' => count(intranet_module_catalog()),
        ]]);
    }

    public static function catalogos(): void
    {
        self::requireAdmin();

        $roles = self::rolesQuery()->orderBy('name')->get(['access_roles.id', 'access_roles.code', 'access_roles.name', 'access_roles.description']);
        $areas = $roles->map(fn (AccessRole $role): array => [
            'id' => (int) $role->id,
            'codigo' => $role->code,
            'nombre' => $role->name,
            'descripcion' => $role->description,
        ])->all();

        json_response([
            'ok' => true,
            'roles' => self::LEGACY_ROLES,
            'areas' => $areas,
            'modulos' => modulos_todos_activos(),
        ]);
    }

    public static function usuarios(): void
    {
        self::requireAdmin();

        $users = User::query()
            ->with(['accessAccount.roles.application', 'accessAccount.roles.permissions.application'])
            ->latest('id')
            ->get()
            ->map(fn (User $user): array => self::serializeUser($user))
            ->all();

        json_response(['ok' => true, 'data' => $users]);
    }

    public static function crearUsuario(): void
    {
        self::requireAdmin();
        $input = get_json_input();
        $email = normalize_email($input['correo'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $legacyRole = (string) ($input['rol'] ?? 'trabajador');

        self::validateInput($email, $password, $legacyRole, true);

        if (User::query()->where('email', $email)->exists()) {
            json_response(['ok' => false, 'message' => 'Ya existe una cuenta con ese correo.'], 409);
        }

        $role = self::resolveRole($legacyRole, $input['area_id'] ?? null);

        $user = DB::connection('identity')->transaction(function () use ($email, $password, $legacyRole, $role): User {
            $name = self::displayNameFromEmail($email);
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'rol' => $role->code,
                'tipo_usuario' => $legacyRole === 'admin' ? 'administrativo' : 'asistencial',
                'activo' => true,
                'registration_source' => 'intranet_hsj',
            ]);

            $account = AccessAccount::query()->create([
                'user_id' => $user->id,
                'username' => self::uniqueUsername($email),
                'email' => $email,
                'password' => null,
                'display_name' => $name,
                'status' => 'active',
                'must_change_password' => true,
                'created_by' => (int) ($_SESSION['ueei_id'] ?? 0) ?: null,
            ]);
            $account->roles()->sync([$role->id]);

            return $user;
        });

        json_response(['ok' => true, 'message' => 'Usuario creado correctamente.', 'id' => (int) $user->id]);
    }

    public static function actualizarUsuario(int $id): void
    {
        self::requireAdmin();
        $input = get_json_input();
        $email = normalize_email($input['correo'] ?? '');
        $legacyRole = (string) ($input['rol'] ?? 'trabajador');
        self::validateInput($email, '', $legacyRole, false);

        if (User::query()->where('email', $email)->where('id', '<>', $id)->exists()) {
            json_response(['ok' => false, 'message' => 'Ya existe otra cuenta con ese correo.'], 409);
        }

        $user = User::query()->with('accessAccount')->find($id);
        if (! $user) {
            json_response(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $role = self::resolveRole($legacyRole, $input['area_id'] ?? null);

        DB::connection('identity')->transaction(function () use ($user, $email, $role): void {
            $user->forceFill(['email' => $email, 'rol' => $role->code])->save();
            $account = $user->accessAccount;

            if (! $account) {
                $account = AccessAccount::query()->create([
                    'user_id' => $user->id,
                    'username' => self::uniqueUsername($email),
                    'email' => $email,
                    'display_name' => $user->name,
                    'status' => $user->activo ? 'active' : 'inactive',
                    'must_change_password' => false,
                    'created_by' => (int) ($_SESSION['ueei_id'] ?? 0) ?: null,
                ]);
            } else {
                $account->forceFill(['email' => $email])->save();
            }

            $account->roles()->sync([$role->id]);
        });

        json_response(['ok' => true, 'message' => 'Usuario actualizado correctamente.']);
    }

    public static function cambiarEstado(int $id): void
    {
        self::requireAdmin();
        $state = (int) (get_json_input()['estado'] ?? -1);

        if (! in_array($state, [0, 1], true)) {
            json_response(['ok' => false, 'message' => 'Estado inválido.'], 400);
        }
        if ($id === (int) ($_SESSION['ueei_id'] ?? 0) && $state === 0) {
            json_response(['ok' => false, 'message' => 'No puedes desactivar tu propia cuenta.'], 400);
        }

        $user = User::query()->with('accessAccount')->find($id);
        if (! $user) {
            json_response(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        DB::connection('identity')->transaction(function () use ($user, $state): void {
            $user->forceFill(['activo' => (bool) $state])->save();
            $user->accessAccount?->forceFill(['status' => $state ? 'active' : 'inactive'])->save();
        });

        json_response(['ok' => true, 'message' => $state ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.']);
    }

    public static function cambiarPassword(int $id): void
    {
        self::requireAdmin();
        $password = (string) (get_json_input()['password'] ?? '');

        if (strlen($password) < 8 || strlen($password) > 72) {
            json_response(['ok' => false, 'message' => 'La contraseña debe tener entre 8 y 72 caracteres.'], 400);
        }

        $user = User::query()->with('accessAccount')->find($id);
        if (! $user) {
            json_response(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        DB::connection('identity')->transaction(function () use ($user, $password): void {
            $hash = Hash::make($password);
            $user->forceFill(['password' => $hash])->save();
            if ($user->accessAccount) {
                $user->accessAccount->forceFill(['password' => $hash, 'must_change_password' => false])->save();
            }
        });

        json_response(['ok' => true, 'message' => 'Contraseña actualizada correctamente.']);
    }

    private static function serializeUser(User $user): array
    {
        $application = (string) config('access.application');
        $roles = $user->accessAccount?->roles
            ->filter(fn ($role): bool => $role->application?->code === $application && $role->application?->is_active)
            ->values() ?? collect();
        $permissions = $roles->flatMap->permissions->pluck('code')->unique()->all();
        $modules = $user->hasRole('administrador')
            ? modulos_todos_activos()
            : array_values(array_filter(modulos_todos_activos(), function (array $module) use ($permissions): bool {
                $required = intranet_module_permission_map()[$module['codigo']] ?? [];
                return array_intersect($required, $permissions) !== [];
            }));
        $primaryRole = $roles->first();

        return [
            'id' => (int) $user->id,
            'correo' => $user->email,
            'rol' => self::legacyRole($primaryRole?->code ?? $user->rol),
            'area_id' => $primaryRole?->id ? (int) $primaryRole->id : null,
            'area_nombre' => $primaryRole?->name,
            'estado' => $user->activo ? 1 : 0,
            'session_version' => 1,
            'fecha_creacion' => optional($user->created_at)->format('Y-m-d H:i:s'),
            'fecha_actualizacion' => optional($user->updated_at)->format('Y-m-d H:i:s'),
            'modulo_ids' => array_column($modules, 'id'),
            'modulo_codigos' => array_column($modules, 'codigo'),
            'modulo_nombres' => array_column($modules, 'nombre'),
        ];
    }

    private static function resolveRole(string $legacyRole, mixed $roleId): AccessRole
    {
        if ((int) $roleId > 0) {
            $selected = self::rolesQuery()->find((int) $roleId);
            if ($selected) {
                return $selected;
            }
        }

        $code = self::ROLE_MAP[$legacyRole] ?? 'consulta';
        return self::rolesQuery()->where('access_roles.code', $code)->firstOrFail();
    }

    private static function rolesQuery()
    {
        return AccessRole::query()->whereHas('application', fn ($query) => $query
            ->where('code', config('access.application'))
            ->where('is_active', true));
    }

    private static function validateInput(string $email, string $password, string $role, bool $requiresPassword): void
    {
        if (! valid_email($email)) {
            json_response(['ok' => false, 'message' => 'El correo no es válido.'], 400);
        }
        if ($requiresPassword && (strlen($password) < 8 || strlen($password) > 72)) {
            json_response(['ok' => false, 'message' => 'La contraseña debe tener entre 8 y 72 caracteres.'], 400);
        }
        if (! in_array($role, self::LEGACY_ROLES, true)) {
            json_response(['ok' => false, 'message' => 'Rol inválido.'], 400);
        }
    }

    private static function legacyRole(string $code): string
    {
        return match ($code) {
            'administrador' => 'admin',
            'supervisor' => 'supervisor',
            default => 'trabajador',
        };
    }

    private static function displayNameFromEmail(string $email): string
    {
        return ucwords(str_replace(['.', '_', '-'], ' ', strstr($email, '@', true) ?: $email));
    }

    private static function uniqueUsername(string $email): string
    {
        $base = substr((string) (strstr($email, '@', true) ?: 'usuario'), 0, 50);
        $candidate = $base;
        $suffix = 1;
        while (AccessAccount::query()->where('username', $candidate)->exists()) {
            $candidate = substr($base, 0, 50).'-'.$suffix++;
        }
        return $candidate;
    }
}
