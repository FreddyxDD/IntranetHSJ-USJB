<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Egreso extends Model
{
    protected $table = 'egresos.egresos';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecing' => 'date',
            'fecegr' => 'date',
            'fecparto' => 'date',
            'fechareg' => 'date',
        ];
    }

    public function constancias(): HasMany
    {
        return $this->hasMany(Constancia::class, 'egreso_id');
    }

    public function getPacienteAttribute(): string
    {
        return trim((string) $this->nomb.' '.(string) $this->apell);
    }
}
