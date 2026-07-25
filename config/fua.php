<?php

return [
    'template_path' => env('FUA_TEMPLATE_PATH', storage_path('app/templates/fua_template.xlsx')),
    'output_path' => env('FUA_OUTPUT_PATH', rtrim(env('TEMP') ?: env('TMP') ?: sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'citashsj-fuas'),
    'print' => [
        'command' => env('FUA_PRINT_COMMAND'),
        'settings' => env('FUA_PRINT_SETTINGS', 'fit,monochrome'),
        'default_printer' => env('FUA_DEFAULT_PRINTER_NAME'),
    ],
    'pdf_merge' => [
        'python' => env('FUA_PDF_MERGE_PYTHON', 'python'),
    ],
    'real_generation' => [
        'enabled' => env('FUA_REAL_GENERATION_ENABLED', false),
        'cab_dni_usuario' => env('FUA_REAL_CAB_DNI_USUARIO'),
        'id_usuario_auditoria' => (int) env('FUA_REAL_ID_USUARIO_AUDITORIA', 0),
        'cab_codigo_punto_digitacion' => (int) env('FUA_REAL_CAB_CODIGO_PUNTO_DIGITACION', 1071),
        'cab_codigo_udr' => env('FUA_REAL_CAB_CODIGO_UDR', '035'),
        'cab_origen_registro' => env('FUA_REAL_CAB_ORIGEN_REGISTRO', '1000'),
        'cab_version_aplicativo' => env('FUA_REAL_CAB_VERSION_APLICATIVO', 'v.3'),
        'establecimiento_distrito' => env('FUA_REAL_ESTABLECIMIENTO_DISTRITO', '110201'),
        'establecimiento_categoria' => env('FUA_REAL_ESTABLECIMIENTO_CATEGORIA', '05'),
    ],

    'ipress' => [
        'codigo_renaes' => env('FUA_IPRESS_RENAES', '03414'),
        'nombre' => env('FUA_IPRESS_NOMBRE', 'HOSP. SAN JOSE DE CHINCHA'),
        'formato_prefijo' => env('FUA_FORMATO_PREFIJO', '3414'),
    ],

    'correlativo' => [
        'disa' => env('FUA_CORRELATIVO_DISA', '414'),
        'lote' => env('FUA_CORRELATIVO_LOTE', now()->format('y')),
    ],

    'prestacion' => [
        'ups_default' => env('FUA_UPS_DEFAULT', '300301'),
        'codigo_default' => env('FUA_CODIGO_PRESTA_DEFAULT', '000'),
    ],

    'sis' => [
        'diresa_default' => env('FUA_SIS_DIRESA_DEFAULT', '150'),
        'tipo_formato_default' => env('FUA_SIS_TIPO_FORMATO_DEFAULT', '2'),
    ],
];
