<?php

return [
    'catalog' => [
        'informacion' => [
            'id' => 2, 'codigo' => 'informacion', 'nombre' => 'Información',
            'descripcion' => 'Información institucional del Hospital San José.',
            'ruta' => '/informacion', 'icono' => '/assets/icon/InforHSJ.png',
            'permission' => 'dashboard.view',
        ],
        'citas_admin' => [
            'id' => 1, 'codigo' => 'citas_admin', 'nombre' => 'Citas',
            'descripcion' => 'Administración de citas y registros.',
            'ruta' => '/citas-admin', 'icono' => '/assets/icon/CitasLog.png',
            'permission' => 'citas.view',
        ],
        'cirugias' => [
            'id' => 3, 'codigo' => 'cirugias', 'nombre' => 'Cirugías',
            'descripcion' => 'Registro, control y análisis de cirugías.',
            'ruta' => '/cirugias-login', 'icono' => '/assets/icon/CirugiasLog.png',
            'permission' => 'cirugias.view',
        ],
        'uvi' => [
            'id' => 4, 'codigo' => 'uvi', 'nombre' => 'UVI',
            'descripcion' => 'Acceso institucional al módulo UVI.',
            'ruta' => '/uvi-login', 'icono' => '/assets/icon/UVIlo.png',
            'permission' => 'uvi.view',
        ],
        'produccion' => [
            'id' => 5, 'codigo' => 'produccion', 'nombre' => 'Producción',
            'descripcion' => 'Indicadores de producción y rendimiento.',
            'ruta' => '/produccion', 'icono' => '/assets/icon/Total_cirugias.png',
            'permission' => 'produccion.view',
        ],
        'eficiencia' => [
            'id' => 6, 'codigo' => 'eficiencia', 'nombre' => 'Eficiencia',
            'descripcion' => 'Indicadores de eficiencia hospitalaria.',
            'ruta' => '/eficiencia', 'icono' => '/assets/icon/Tasa_Urgencia.png',
            'permission' => 'eficiencia.view',
        ],
        'calidad' => [
            'id' => 7, 'codigo' => 'calidad', 'nombre' => 'Calidad',
            'descripcion' => 'Indicadores de calidad institucional.',
            'ruta' => '/calidad', 'icono' => '/assets/icon/Segura.png',
            'permission' => 'calidad.view',
        ],
        'egresos' => [
            'id' => 8, 'codigo' => 'egresos', 'nombre' => 'Egresos',
            'descripcion' => 'Consulta y emisión de constancias de egreso hospitalario.',
            'ruta' => '/egresos', 'icono' => '/assets/images/EgresosHSJ.png',
            'permission' => 'egresos.view',
        ],
    ],
];
