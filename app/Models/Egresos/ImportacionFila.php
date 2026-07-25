<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportacionFila extends Model
{
    protected $table = 'egresos.importacion_filas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'mensajes' => 'array',
        ];
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class, 'importacion_id');
    }
}
