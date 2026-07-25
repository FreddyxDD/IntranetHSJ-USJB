<?php

namespace App\Models\Egresos;

use Illuminate\Database\Eloquent\Model;

final class ConfiguracionConstancia extends Model
{
    protected $table = 'egresos.configuracion_constancias';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $guarded = [];
}
