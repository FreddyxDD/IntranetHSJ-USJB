<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\InstitutionalAuthController;
use App\Http\Controllers\Controller;
use App\Models\AccessAccount;
use App\Models\AccessRole;
use App\Models\User;
use App\Services\Identity\ApplicationModuleAssignmentService;
use App\Services\Identity\ApplicationRoleAssignmentService;
use App\Services\Identity\CentralAccessService;
use App\Services\Identity\ModuleCatalogService;
use App\Services\Identity\SelfRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class IdentityAdminController extends Controller
{
    private const LEGACY_ROLES = ['admin', 'director', 'supervisor', 'trabajador'];

    public function page(): View
    {
        self::requireAdmin();
        $user = app(CentralAccessService::class)->user();

        return view('admin.identity', [
            'adminCorreo' => $user?->email ?? 'Administrador',
            'adminRol' => 'admin',
        ]);
    }

    private static function requireAdmin(): void
    {
        if (! app(CentralAccessService::class)->isAdministrator()) {
            self::json(['ok' => false, 'message' => 'No tienes permisos de administrador.'], 403);
        }
    }

    public static function resumen(): void
    {
        self::requireAdmin();

        self::json(['ok' => true, 'data' => [
            'totalUsuarios' => User::query()->count(),
            'usuariosActivos' => User::query()->where('activo', true)->count(),
            'usuariosInactivos' => User::query()->where('activo', false)->count(),
            'solicitudesPendientes' => AccessAccount::query()->where('status', 'pending')->count(),
            'totalAreas' => self::rolesQuery()->count(),
            'totalModulos' => count(app(ModuleCatalogService::class)->all()),
        ]]);
    }

    public static function catalogos(): void
    {
        self::requireAdmin();

        $roles = self::rolesQuery()
            ->with(['permissions.application'])
            ->orderBy('name')
            ->get(['access_roles.id', 'access_roles.code', 'access_roles.name', 'access_roles.description']);
        $areas = $roles->map(fn (AccessRole $role): array => [
            'id' => (int) $role->id,
            'codigo' => $role->code,
            'nombre' => $role->name,
            'descripcion' => $role->description,
            'rol' => self::legacyRole($role->code),
            'modulo_ids' => self::moduleIdsForRole($role),
        ])->all();

        self::json([
            'ok' => true,
            'roles' => self::LEGACY_ROLES,
            'areas' => $areas,
            'modulos' => app(ModuleCatalogService::class)->all(),
        ]);
    }

    public static function usuarios(): void
    {
        self::requireAdmin();

        $users = User::query()
            ->with([
                'person',
                'accessAccount.roles.application',
                'accessAccount.roles.permissions.application',
                'accessAccount.permissionOverrides.application',
            ])
            ->latest('id')
            ->get()
            ->map(fn (User $user): array => self::serializeUser($user))
            ->all();

        self::json(['ok' => true, 'data' => $users]);
    }

    public static function crearUsuario(): void
    {
        self::requireAdmin();

        self::json([
            'ok' => false,
            'message' => 'Las cuentas se crean validando el DNI y los datos personales de Legajos. Este panel solo administra accesos.',
        ], 422);
    }

    public static function actualizarUsuario(int $id): void
    {
        self::requireAdmin();
        $input = request()->json()->all();
        $protectedFields = ['correo', 'email', 'nombre', 'name', 'dni', 'telefono', 'fecha_nacimiento'];
        if (array_intersect($protectedFields, array_keys($input)) !== []) {
            self::json([
                'ok' => false,
                'message' => 'Los datos personales son de solo lectura y deben actualizarse desde Legajos.',
            ], 422);
        }

        $user = User::query()->with(['person', 'accessAccount'])->find($id);
        if (! $user) {
            self::json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $role = self::resolveRole($input['area_id'] ?? null);
        $moduleIds = $input['modulos'] ?? null;
        if (! is_array($moduleIds)) {
            self::json(['ok' => false, 'message' => 'Debes indicar los módulos asignados al usuario.'], 422);
        }
        $actorAccountId = app(CentralAccessService::class)->user()?->accessAccount?->id;

        DB::connection('identity')->transaction(function () use ($user, $role, $moduleIds, $actorAccountId): void {
            $user->forceFill(['rol' => $role->code])->save();
            $account = $user->accessAccount;

            if (! $account) {
                $account = AccessAccount::query()->create([
                    'user_id' => $user->id,
                    'person_id' => $user->person_id,
                    'username' => $user->registration_document_number ?: (string) $user->id,
                    'email' => $user->email,
                    'display_name' => $user->name,
                    'status' => $user->activo ? 'active' : 'inactive',
                    'must_change_password' => false,
                    'created_by' => $actorAccountId,
                ]);
            }

            app(ApplicationRoleAssignmentService::class)->assign(
                $account,
                $role,
                $actorAccountId,
            );
            app(ApplicationModuleAssignmentService::class)->sync(
                $account,
                $role,
                $moduleIds,
                $actorAccountId,
            );
        });

        $updatedUser = User::query()
            ->with([
                'person',
                'accessAccount.roles.application',
                'accessAccount.roles.permissions.application',
                'accessAccount.permissionOverrides.application',
            ])
            ->findOrFail($id);

        if ($id === (int) ($_SESSION['ueei_id'] ?? 0)) {
            InstitutionalAuthController::refrescarSesion($updatedUser);
        }

        self::json([
            'ok' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => self::serializeUser($updatedUser),
        ]);
    }

    public static function cambiarEstado(int $id): void
    {
        self::requireAdmin();
        $state = (int) request()->json('estado', -1);

        if (! in_array($state, [0, 1], true)) {
            self::json(['ok' => false, 'message' => 'Estado inválido.'], 400);
        }
        if ($id === (int) ($_SESSION['ueei_id'] ?? 0) && $state === 0) {
            self::json(['ok' => false, 'message' => 'No puedes desactivar tu propia cuenta.'], 400);
        }

        $user = User::query()->with('accessAccount')->find($id);
        if (! $user) {
            self::json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $approvingRequest = $state === 1 && $user->accessAccount?->status === 'pending';

        if ($approvingRequest) {
            app(SelfRegistrationService::class)->approvePendingAccount(
                $user,
                (int) ($_SESSION['ueei_id'] ?? 0)
            );
        } else {
            DB::connection('identity')->transaction(function () use ($user, $state): void {
                $user->forceFill(['activo' => (bool) $state])->save();
                $user->accessAccount?->forceFill(['status' => $state ? 'active' : 'inactive'])->save();
            });
        }

        logger()->info($approvingRequest ? 'Solicitud de cuenta aprobada.' : 'Estado de cuenta actualizado.', [
            'user_id' => $user->id,
            'person_id' => $user->person_id,
            'approved_by' => (int) ($_SESSION['ueei_id'] ?? 0) ?: null,
            'status' => $state ? 'active' : 'inactive',
        ]);

        self::json([
            'ok' => true,
            'message' => $approvingRequest
                ? 'Solicitud aprobada. La persona ya puede iniciar sesión con acceso de consulta.'
                : ($state ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.'),
        ]);
    }

    public static function cambiarPassword(int $id): void
    {
        self::requireAdmin();
        $password = (string) request()->json('password', '');

        if (strlen($password) < 8 || strlen($password) > 72) {
            self::json(['ok' => false, 'message' => 'La contraseña debe tener entre 8 y 72 caracteres.'], 400);
        }

        $user = User::query()->with('accessAccount')->find($id);
        if (! $user) {
            self::json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        DB::connection('identity')->transaction(function () use ($user, $password): void {
            $hash = Hash::make($password);
            $user->forceFill(['password' => $hash])->save();
            if ($user->accessAccount) {
                $user->accessAccount->forceFill(['password' => $hash, 'must_change_password' => false])->save();
            }
        });

        self::json(['ok' => true, 'message' => 'Contraseña actualizada correctamente.']);
    }

    private static function serializeUser(User $user): array
    {
        $personName = collect([
            $user->person?->names,
            $user->person?->paternal_last_name,
            $user->person?->maternal_last_name,
        ])->filter()->implode(' ');
        $application = (string) config('access.application');
        $roles = $user->accessAccount?->roles
            ->filter(fn ($role): bool => $role->application?->code === $application && $role->application?->is_active)
            ->values() ?? collect();
        $permissions = $roles->flatMap->permissions->pluck('code')->unique()->all();
        $modules = app(ModuleCatalogService::class)->forUser($user);
        $primaryRole = $roles->first();

        return [
            'id' => (int) $user->id,
            'correo' => $user->person?->email ?: $user->email,
            'nombre' => $personName ?: $user->name,
            'dni' => $user->person?->document_number ?: $user->registration_document_number,
            'telefono' => $user->person?->phone,
            'fecha_nacimiento' => $user->person?->birth_date?->format('Y-m-d'),
            'fuente_datos' => 'Legajos / HSJ_Identity',
            'rol' => self::legacyRole($primaryRole?->code ?? $user->rol),
            'area_id' => $primaryRole?->id ? (int) $primaryRole->id : null,
            'area_nombre' => $primaryRole?->name,
            'estado' => $user->activo ? 1 : 0,
            'estado_cuenta' => $user->accessAccount?->status ?? ($user->activo ? 'active' : 'inactive'),
            'solicitud_pendiente' => $user->accessAccount?->status === 'pending',
            'fecha_aprobacion' => $user->accessAccount?->approved_at?->format('Y-m-d H:i:s'),
            'aprobado_por' => $user->accessAccount?->approved_by,
            'session_version' => 1,
            'fecha_creacion' => optional($user->created_at)->format('Y-m-d H:i:s'),
            'fecha_actualizacion' => optional($user->updated_at)->format('Y-m-d H:i:s'),
            'modulo_ids' => array_column($modules, 'id'),
            'modulo_codigos' => array_column($modules, 'codigo'),
            'modulo_nombres' => array_column($modules, 'nombre'),
        ];
    }

    private static function resolveRole(mixed $roleId): AccessRole
    {
        if ((int) $roleId > 0) {
            $selected = self::rolesQuery()->find((int) $roleId);
            if ($selected) {
                return $selected;
            }
        }

        self::json(['ok' => false, 'message' => 'Selecciona un rol válido para Intranet HSJ.'], 422);
    }

    private static function rolesQuery()
    {
        return AccessRole::query()->whereHas('application', fn ($query) => $query
            ->where('code', config('access.application'))
            ->where('is_active', true));
    }

    private static function legacyRole(string $code): string
    {
        return match ($code) {
            'administrador' => 'admin',
            'indicadores', 'director' => 'director',
            'consulta_citas', 'supervisor' => 'supervisor',
            default => 'trabajador',
        };
    }

    private static function moduleIdsForRole(AccessRole $role): array
    {
        if ($role->code === 'administrador') {
            return array_column(app(ModuleCatalogService::class)->all(), 'id');
        }

        $application = (string) config('access.application');
        $permissionCodes = $role->permissions
            ->filter(fn ($permission): bool => $permission->application?->code === $application)
            ->pluck('code')
            ->unique()
            ->all();
        $modules = app(ModuleCatalogService::class)->all();

        return array_values(array_map(
            static fn (array $module): int => (int) $module['id'],
            array_filter(
                $modules,
                static fn (array $module): bool => in_array(
                    $module['permission'] ?? null,
                    $permissionCodes,
                    true,
                )
            )
        ));
    }

    private static function json(array $payload, int $status = 200): never
    {
        throw new HttpResponseException(response()->json($payload, $status));
    }
}
