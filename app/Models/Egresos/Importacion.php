<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;

final class Importacion extends Model
{
    protected $table = 'egresos.importaciones';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
