<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fuente maestra de pacientes
    |--------------------------------------------------------------------------
    |
    | En desarrollo se usa la copia local de solo lectura. Dentro de la red
    | hospitalaria puede cambiarse a "sigh" sin modificar controladores.
    |
    */
    'patient_connection' => env('EGRESOS_PATIENT_CONNECTION', 'sigh_local'),
    'patient_source_code' => env('EGRESOS_PATIENT_SOURCE_CODE', 'sigh_202607_local'),
];
