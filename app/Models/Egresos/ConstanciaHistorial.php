<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConstanciaHistorial extends Model
{
    protected $table = 'egresos.constancia_historial';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function constancia(): BelongsTo
    {
        return $this->belongsTo(Constancia::class, 'constancia_id');
    }
}
