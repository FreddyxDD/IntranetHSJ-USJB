<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccessRole extends Model
{
    protected $connection = 'identity';

    protected $guarded = [];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AccessApplication::class, 'application_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AccessPermission::class, 'access_role_permissions', 'role_id', 'permission_id');
    }
}
