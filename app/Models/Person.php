<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Person extends Model
{
    protected $connection = 'identity';

    protected $table = 'people';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }
}
