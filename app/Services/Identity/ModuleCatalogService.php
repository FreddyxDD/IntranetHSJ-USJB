<?php

namespace App\Services\Identity;

use App\Models\User;

final class ModuleCatalogService
{
    public function all(): array
    {
        return array_values(config('modules.catalog', []));
    }

    public function forUser(?User $user): array
    {
        if (! $user || ! $user->activo) {
            return [];
        }

        if ($user->hasRole('administrador')) {
            return $this->all();
        }

        return array_values(array_filter(
            config('modules.catalog', []),
            fn (array $module): bool => isset($module['permission'])
                && $user->hasPermission((string) $module['permission']),
        ));
    }
}
