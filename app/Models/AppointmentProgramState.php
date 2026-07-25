<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AppointmentProgramState extends Model
{
    protected $fillable = [
        'programacion_id',
        'fecha',
        'estado',
        'observacion',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }
}
