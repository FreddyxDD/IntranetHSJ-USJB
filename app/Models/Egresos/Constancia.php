<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Constancia extends Model
{
    protected $table = 'egresos.constancias';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecing' => 'date',
            'fecegr' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function egreso(): BelongsTo
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(ConstanciaHistorial::class, 'constancia_id');
    }
}
