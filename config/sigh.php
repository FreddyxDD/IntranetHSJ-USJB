<?php

return [
    'databases' => [
        'main' => env('SIGH_DB_DATABASE', 'SIGH'),
        'external' => env('SIGH_DB_EXTERNAL_DATABASE', 'SIGH_EXTERNA'),
        'sis' => env('SIGH_DB_SIS_DATABASE', 'SIGH_SIS'),
    ],
];
