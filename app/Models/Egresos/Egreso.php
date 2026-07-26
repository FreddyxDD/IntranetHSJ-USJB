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
            'document_verified_at' => 'datetime',
        ];
    }

    public function constancias(): HasMany
    {
        return $this->hasMany(Constancia::class, 'egreso_id');
    }

    public function constanciaEpisodios(): HasMany
    {
        return $this->hasMany(ConstanciaEpisodio::class, 'egreso_id');
    }

    public function getPacienteAttribute(): string
    {
        return trim((string) $this->nomb.' '.(string) $this->apell);
    }

    public function getDocumentoAttribute(): ?string
    {
        $document = trim((string) $this->doc_numero);

        return $document !== '' ? $document : null;
    }
}
