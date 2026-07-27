<?php

namespace App\Services\Identity;

use App\Models\User;
use App\Support\InstitutionalSession;

final class CentralAccessService
{
    private ?User $resolvedUser = null;

    private bool $resolved = false;

    public function __construct(
        private readonly InstitutionalSession $session,
        private readonly ModuleCatalogService $modules,
    ) {}

    public function user(): ?User
    {
        if ($this->resolved) {
            return $this->resolvedUser;
        }

        $this->resolved = true;
        $id = (int) $this->session->get('ueei_id', 0);

        if ($id <= 0) {
            return null;
        }

        $user = User::query()
            ->with([
                'accessAccount.roles.application',
                'accessAccount.roles.permissions.application',
            ])
            ->find($id);

        if (! $user || ! $user->activo || $user->accessAccount?->status !== 'active') {
            return null;
        }

        return $this->resolvedUser = $user;
    }

    public function isAuthenticated(): bool
    {
        return $this->session->hasAuthenticatedIdentity() && $this->user() !== null;
    }

    public function isAdministrator(): bool
    {
        return $this->user()?->hasRole('administrador') ?? false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        return $this->user()?->hasPermission($permission) ?? false;
    }

    public function hasModule(string $module): bool
    {
        $permission = config("modules.catalog.{$module}.permission");

        return is_string($permission) && $this->hasPermission($permission);
    }

    public function modules(): array
    {
        return $this->modules->forUser($this->user());
    }

    public function confirmationPending(): bool
    {
        return $this->session->confirmationPending();
    }
}
