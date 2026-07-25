<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccessPermission extends Model
{
    protected $connection = 'identity';

    protected $guarded = [];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AccessApplication::class, 'application_id');
    }
}
