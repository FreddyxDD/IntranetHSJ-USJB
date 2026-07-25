<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class AccessAccount extends Model
{
    protected $connection = 'identity';

    protected $guarded = [];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['must_change_password' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AccessRole::class, 'access_account_roles', 'account_id', 'role_id')
            ->withPivot(['assigned_at', 'assigned_by']);
    }
}
