<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConstanciaEpisodio extends Model
{
    protected $table = 'egresos.constancia_episodios';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecing' => 'date',
            'fecegr' => 'date',
            'posicion' => 'integer',
        ];
    }

    public function constancia(): BelongsTo
    {
        return $this->belongsTo(Constancia::class, 'constancia_id');
    }

    public function egreso(): BelongsTo
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }
}
