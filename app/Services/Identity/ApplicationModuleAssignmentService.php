<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\AccessAccount;
use App\Models\AccessPermission;
use App\Models\AccessRole;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ApplicationModuleAssignmentService
{
    public function sync(
        AccessAccount $account,
        AccessRole $role,
        array $selectedModuleIds,
        ?int $assignedBy = null,
    ): void {
        $modules = collect(config('modules.catalog', []))->keyBy(fn (array $module): int => (int) $module['id']);
        $selectedIds = collect($selectedModuleIds)->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $invalidIds = $selectedIds->diff($modules->keys());

        if ($invalidIds->isNotEmpty()) {
            throw new DomainException('La selección contiene módulos que no pertenecen a la aplicación activa.');
        }

        $application = (string) config('access.application');
        $role->loadMissing(['application', 'permissions.application']);

        if ($role->application?->code !== $application || ! $role->application->is_active) {
            throw new DomainException('El perfil seleccionado no pertenece a la aplicación activa.');
        }

        if ($role->code === 'administrador') {
            $selectedIds = $modules->keys()->map(fn (mixed $id): int => (int) $id)->values();
        }

        $modulePermissionById = $modules
            ->mapWithKeys(fn (array $module): array => [(int) $module['id'] => (string) $module['permission']]);
        $permissions = AccessPermission::query()
            ->whereHas('application', fn ($query) => $query
                ->where('code', $application)
                ->where('is_active', true))
            ->whereIn('code', $modulePermissionById->values()->all())
            ->get()
            ->keyBy('code');

        if ($permissions->count() !== $modulePermissionById->unique()->count()) {
            throw new DomainException('El catálogo de módulos contiene permisos que no están registrados en HSJ_Identity.');
        }

        $inheritedCodes = $role->permissions
            ->filter(fn (AccessPermission $permission): bool => $permission->application?->code === $application)
            ->pluck('code');
        $selectedCodes = $selectedIds->map(fn (int $id): string => $modulePermissionById->get($id));
        $permissionIds = $permissions->pluck('id')->all();

        DB::connection('identity')->table('access_account_permission_overrides')
            ->where('account_id', $account->id)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        foreach ($modulePermissionById->values() as $code) {
            $isGranted = $selectedCodes->contains($code);
            $isInherited = $inheritedCodes->contains($code);

            if ($isGranted === $isInherited) {
                continue;
            }

            DB::connection('identity')->table('access_account_permission_overrides')->insert([
                'account_id' => $account->id,
                'permission_id' => $permissions->get($code)->id,
                'is_granted' => $isGranted,
                'assigned_at' => now(),
                'assigned_by' => $assignedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
