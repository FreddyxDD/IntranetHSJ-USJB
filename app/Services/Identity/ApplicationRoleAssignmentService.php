<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\AccessAccount;
use App\Models\AccessRole;
use DomainException;

final class ApplicationRoleAssignmentService
{
    public function assign(AccessAccount $account, AccessRole $role, ?int $assignedBy = null): void
    {
        $application = (string) config('access.application');
        $role->loadMissing('application');

        if ($role->application?->code !== $application || ! $role->application->is_active) {
            throw new DomainException('El perfil seleccionado no pertenece a la aplicación activa.');
        }

        $applicationRoleIds = AccessRole::query()
            ->whereHas('application', fn ($query) => $query->where('code', $application))
            ->pluck('id')
            ->all();

        // Solo reemplaza el perfil de esta aplicación. Los roles de Citas,
        // Legajos u otros sistemas centralizados deben conservarse.
        $account->roles()->detach($applicationRoleIds);
        $account->roles()->attach($role->id, [
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
        ]);
    }
}
