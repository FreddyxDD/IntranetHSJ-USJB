<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;

final class Cie10ImportacionFila extends Model
{
    protected $table = 'catalogos.cie10_importacion_filas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'datos_anteriores' => 'array',
            'mensajes' => 'array',
        ];
    }
}
