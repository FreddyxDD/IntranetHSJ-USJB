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
            'first_printed_at' => 'datetime',
            'last_printed_at' => 'datetime',
            'print_count' => 'integer',
        ];
    }

    public function canBePrinted(): bool
    {
        return $this->estado !== 'anulada';
    }

    public function getDocumentoAttribute(): ?string
    {
        $document = trim((string) $this->doc_iden);

        return $document !== '' && ! in_array($document, ['0', '9'], true)
            ? $document
            : null;
    }

    public function egreso(): BelongsTo
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(ConstanciaHistorial::class, 'constancia_id');
    }

    public function episodios(): HasMany
    {
        return $this->hasMany(ConstanciaEpisodio::class, 'constancia_id')
            ->orderBy('posicion');
    }
}
