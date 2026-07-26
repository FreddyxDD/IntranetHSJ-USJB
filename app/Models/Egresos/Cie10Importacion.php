<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cie10Importacion extends Model
{
    protected $table = 'catalogos.cie10_importaciones';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function filas(): HasMany
    {
        return $this->hasMany(Cie10ImportacionFila::class, 'importacion_id');
    }
}
