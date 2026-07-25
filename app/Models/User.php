<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    protected $connection = 'identity';

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function accessAccount(): HasOne
    {
        return $this->hasOne(AccessAccount::class, 'user_id');
    }

    public function hasRole(string $code, ?string $application = null): bool
    {
        $application ??= (string) config('access.application');
        return $this->accessAccount?->roles->contains(fn (AccessRole $role): bool =>
            $role->code === $code && $role->application?->code === $application && $role->application?->is_active
        ) ?? false;
    }

    public function hasPermission(string $code, ?string $application = null): bool
    {
        $application ??= (string) config('access.application');
        return $this->accessAccount?->roles
            ->contains(fn (AccessRole $role): bool =>
                $role->application?->code === $application
                && $role->application?->is_active
                && $role->permissions->contains(fn (AccessPermission $permission): bool =>
                    $permission->code === $code && $permission->application?->code === $application
                )
            ) ?? false;
    }
}
