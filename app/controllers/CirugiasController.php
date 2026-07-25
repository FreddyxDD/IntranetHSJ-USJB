<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/response.php';

final class CirugiasController
{
    private const CAMPOS = [
        'fecha',
        'hora',
        'historia_clinica',
        'dni',
        'nombres_apellidos',
        'tipo_orden',
        'especialidad',
        'edad',
        'sexo',
        'tipo_seguro',
        'prueba_covid',
        'suspension',
        'motivo_suspension',
        'diagnostico_preoperatorio',
        'codigo_cie10',
        'operacion_realizada',
        'comorbilidad',
        'reintervencion',
        'ram_medicamentos',
        'discrepancia_diagnostica',
        'tiempo_total',
        'tiempo_anestesia',
        'tiempo_operacion',
        'complicaciones_intraoperatorias',
        'cirujano_1',
        'cirujano_2',
        'anestesiologo',
        'enfermera_instrumentista',
        'anestesiologo_recuperacion',
        'enfermera_recuperacion',
        'tecnico_enfermeria_1',
        'tecnico_enfermeria_2',
        'tipo_anestesia',
        'cirugia_mayor',
        'cirugia_menor',
        'sop',
        'destino',
        'tiempo_urpa',
        'observaciones',
        'hoja_origen',
        'origen_registro'
    ];

    private const MAPA_EXCEL = [
        'FECHA' => 'fecha',
        'HORA' => 'hora',
        'HISTORIA CLINICA' => 'historia_clinica',
        'HISTORIA CLÍNICA' => 'historia_clinica',
        'HC' => 'historia_clinica',
        'DNI' => 'dni',
        'DOCUMENTO' => 'dni',
        'NOMBRES Y APELLIDOS' => 'nombres_apellidos',
        'PACIENTE' => 'nombres_apellidos',
        'TIPO DE ORDEN' => 'tipo_orden',
        'ORDEN' => 'tipo_orden',
        'ESPECIALIDAD' => 'especialidad',
        'EDAD' => 'edad',
        'SEXO' => 'sexo',
        'TIPO DE SEGURO' => 'tipo_seguro',
        'SEGURO' => 'tipo_seguro',
        'PRUEBA COVID' => 'prueba_covid',
        'SUSPENSION' => 'suspension',
        'SUSPENSIÓN' => 'suspension',
        'MOTIVO DE SUSPENSION' => 'motivo_suspension',
        'MOTIVO DE SUSPENSIÓN' => 'motivo_suspension',
        'DIAGNOSTICO PREOPERATORIO' => 'diagnostico_preoperatorio',
        'DIAGNÓSTICO PREOPERATORIO' => 'diagnostico_preoperatorio',
        'CODIGO CIE 10' => 'codigo_cie10',
        'CÓDIGO CIE 10' => 'codigo_cie10',
        'CIE10' => 'codigo_cie10',
        'OPERACION REALIZADA' => 'operacion_realizada',
        'OPERACIÓN REALIZADA' => 'operacion_realizada',
        'COMORBILIDAD' => 'comorbilidad',
        'REINTERVENCION' => 'reintervencion',
        'REINTERVENCIÓN' => 'reintervencion',
        'RAM MEDICAMENTOS' => 'ram_medicamentos',
        'DISCREPANCIA DIAGNOSTICA' => 'discrepancia_diagnostica',
        'DISCREPANCIA DIAGNÓSTICA' => 'discrepancia_diagnostica',
        'TIEMPO TOTAL' => 'tiempo_total',
        'TIEMPO ANESTESIA' => 'tiempo_anestesia',
        'TIEMPO OPERACION' => 'tiempo_operacion',
        'TIEMPO OPERACIÓN' => 'tiempo_operacion',
        'COMPLICACIONES INTRAOPERATORIAS' => 'complicaciones_intraoperatorias',
        'CIRUJANO 1' => 'cirujano_1',
        'CIRUJANO 2' => 'cirujano_2',
        'ANESTESIOLOGO' => 'anestesiologo',
        'ANESTESIÓLOGO' => 'anestesiologo',
        'ENFERMERA INSTRUMENTISTA' => 'enfermera_instrumentista',
        'ANESTESIOLOGO RECUPERACION' => 'anestesiologo_recuperacion',
        'ANESTESIÓLOGO RECUPERACIÓN' => 'anestesiologo_recuperacion',
        'ENFERMERA RECUPERACION' => 'enfermera_recuperacion',
        'ENFERMERA RECUPERACIÓN' => 'enfermera_recuperacion',
        'TECNICO DE ENFERMERIA 1' => 'tecnico_enfermeria_1',
        'TÉCNICO DE ENFERMERÍA 1' => 'tecnico_enfermeria_1',
        'TECNICO DE ENFERMERIA 2' => 'tecnico_enfermeria_2',
        'TÉCNICO DE ENFERMERÍA 2' => 'tecnico_enfermeria_2',
        'TIPO DE ANESTESIA' => 'tipo_anestesia',
        'CIRUGIA MAYOR' => 'cirugia_mayor',
        'CIRUGÍA MAYOR' => 'cirugia_mayor',
        'CIRUGIA MENOR' => 'cirugia_menor',
        'CIRUGÍA MENOR' => 'cirugia_menor',
        'SOP' => 'sop',
        'DESTINO' => 'destino',
        'TIEMPO URPA' => 'tiempo_urpa',
        'OBSERVACIONES' => 'observaciones'
    ];

    private static function requireLoginJson(): void
    {
        if (empty($_SESSION['cirugias_usuario'])) {
            json_response([
                'success' => false,
                'message' => 'No autenticado en Cirugías'
            ], 401);
        }
    }

    private static function requireAdminJson(): void
    {
        self::requireLoginJson();

        if ((int) ($_SESSION['cirugias_rol'] ?? 1) !== 0) {
            json_response([
                'success' => false,
                'message' => 'Acceso denegado. Solo administrador.'
            ], 403);
        }
    }

public static function listar(): void
{
    self::requireLoginJson();

    $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
    $especialidad = trim((string) ($_GET['especialidad'] ?? ''));
    $tipoOrden = trim((string) ($_GET['tipo_orden'] ?? ''));
    $hoja = trim((string) ($_GET['hoja'] ?? ''));

    $where = ['1=1'];
    $params = [];

    if ($busqueda !== '') {
        $where[] = "(
            historia_clinica LIKE :busqueda OR
            dni LIKE :busqueda OR
            nombres_apellidos LIKE :busqueda OR
            diagnostico_preoperatorio LIKE :busqueda OR
            operacion_realizada LIKE :busqueda OR
            codigo_cie10 LIKE :busqueda
        )";
        $params[':busqueda'] = '%' . $busqueda . '%';
    }

    if ($especialidad !== '') {
        $where[] = 'especialidad = :especialidad';
        $params[':especialidad'] = $especialidad;
    }

    if ($tipoOrden !== '') {
        $where[] = 'tipo_orden = :tipo_orden';
        $params[':tipo_orden'] = $tipoOrden;
    }

    if ($hoja !== '') {
        $mapaMeses = [
            'ENE' => 1,
            'FEB' => 2,
            'MAR' => 3,
            'ABR' => 4,
            'MAY' => 5,
            'JUN' => 6,
            'JUL' => 7,
            'AGO' => 8,
            'SEP' => 9,
            'OCT' => 10,
            'NOV' => 11,
            'DIC' => 12,
        ];

        $hojaNormalizada = mb_strtoupper(trim($hoja));

        if (preg_match('/^([A-Z]{3})(\d{2})$/', $hojaNormalizada, $m)) {
            $prefijoMes = $m[1];
            $anioCorto = (int) $m[2];

            if (isset($mapaMeses[$prefijoMes])) {
                $where[] = 'fecha IS NOT NULL';
                $where[] = 'MONTH(fecha) = :mes_fecha';
                $where[] = 'YEAR(fecha) = :anio_fecha';

                $params[':mes_fecha'] = $mapaMeses[$prefijoMes];
                $params[':anio_fecha'] = 2000 + $anioCorto;
            }
        } else {
            /*
             * Respaldo por si alguna pestaña no tiene formato ENE26.
             */
            $where[] = 'hoja_origen = :hoja';
            $params[':hoja'] = $hoja;
        }
    }

    $sql = "
        SELECT *
        FROM cirugias
        WHERE " . implode(' AND ', $where) . "
        ORDER BY fecha DESC, hora DESC, id DESC
        LIMIT 1000
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    json_response([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);
}

public static function resumen(): void
{
    self::requireLoginJson();

    try {
        $pdo = db();

        $stmt = $pdo->query("
            SELECT
                COUNT(*) AS totalRegistros,

                COALESCE(SUM(
                    CASE
                        WHEN fecha IS NOT NULL
                        AND hora IS NOT NULL
                        AND (
                            NULLIF(TRIM(COALESCE(dni, '')), '') IS NOT NULL
                            OR NULLIF(TRIM(COALESCE(historia_clinica, '')), '') IS NOT NULL
                        )
                        THEN 1 ELSE 0
                    END
                ), 0) AS registrosValidos,

                COALESCE(SUM(
                    CASE
                        WHEN NULLIF(TRIM(COALESCE(observaciones, '')), '') IS NOT NULL
                        THEN 1 ELSE 0
                    END
                ), 0) AS conObservaciones
            FROM cirugias
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $ultimaImportacion = null;

        /*
        |--------------------------------------------------------------------------
        | 1. Intentar obtener última importación desde historial_importaciones_cirugias
        |--------------------------------------------------------------------------
        */
        try {
            $existeHistorial = $pdo
                ->query("SHOW TABLES LIKE 'historial_importaciones_cirugias'")
                ->fetchColumn();

            if ($existeHistorial) {
                $columnasHistorial = $pdo
                    ->query("SHOW COLUMNS FROM historial_importaciones_cirugias")
                    ->fetchAll(PDO::FETCH_COLUMN);

                $columnaFechaHistorial = null;

                foreach ([
                    'fecha_carga',
                    'fecha_importacion',
                    'created_at',
                    'creado_en',
                    'importado_en'
                ] as $columna) {
                    if (in_array($columna, $columnasHistorial, true)) {
                        $columnaFechaHistorial = $columna;
                        break;
                    }
                }

                if ($columnaFechaHistorial !== null) {
                    $columnaSegura = '`' . str_replace('`', '', $columnaFechaHistorial) . '`';

                    $ultimaImportacion = $pdo
                        ->query("
                            SELECT DATE_FORMAT(MAX($columnaSegura), '%Y-%m-%dT%H:%i:%s')
                            FROM historial_importaciones_cirugias
                        ")
                        ->fetchColumn();
                }
            }
        } catch (Throwable) {
            $ultimaImportacion = null;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Si no hay historial, intentar obtener desde la tabla cirugias
        |--------------------------------------------------------------------------
        */
        if (!$ultimaImportacion) {
            try {
                $columnasCirugias = $pdo
                    ->query("SHOW COLUMNS FROM cirugias")
                    ->fetchAll(PDO::FETCH_COLUMN);

                $columnaFechaCirugia = null;

                foreach ([
                    'fecha_registro',
                    'fecha_carga',
                    'created_at',
                    'creado_en',
                    'importado_en',
                    'fecha_importacion'
                ] as $columna) {
                    if (in_array($columna, $columnasCirugias, true)) {
                        $columnaFechaCirugia = $columna;
                        break;
                    }
                }

                if ($columnaFechaCirugia !== null) {
                    $columnaSegura = '`' . str_replace('`', '', $columnaFechaCirugia) . '`';

                    $ultimaImportacion = $pdo
                        ->query("
                            SELECT DATE_FORMAT(MAX($columnaSegura), '%Y-%m-%dT%H:%i:%s')
                            FROM cirugias
                        ")
                        ->fetchColumn();
                } else {
                    $ultimaImportacion = $pdo
                        ->query("
                            SELECT DATE_FORMAT(MAX(fecha), '%Y-%m-%dT00:00:00')
                            FROM cirugias
                        ")
                        ->fetchColumn();
                }
            } catch (Throwable) {
                $ultimaImportacion = null;
            }
        }

        json_response([
            'success' => true,
            'ok' => true,
            'resumen' => [
                'totalRegistros' => (int) ($row['totalRegistros'] ?? 0),
                'registrosValidos' => (int) ($row['registrosValidos'] ?? 0),
                'conObservaciones' => (int) ($row['conObservaciones'] ?? 0),
                'ultimaImportacion' => $ultimaImportacion ?: null,
            ],
        ]);
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'ok' => false,
            'message' => 'Error al obtener resumen de cirugías',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

 public static function hojas(): void
{
    self::requireLoginJson();

    try {
        $stmt = db()->query("
            SELECT hoja_origen
            FROM (
                SELECT DISTINCT hoja_origen
                FROM cirugias
                WHERE hoja_origen IS NOT NULL
                AND hoja_origen <> ''
            ) AS hojas
            ORDER BY
                CASE LEFT(hoja_origen, 3)
                    WHEN 'ENE' THEN 1
                    WHEN 'FEB' THEN 2
                    WHEN 'MAR' THEN 3
                    WHEN 'ABR' THEN 4
                    WHEN 'MAY' THEN 5
                    WHEN 'JUN' THEN 6
                    WHEN 'JUL' THEN 7
                    WHEN 'AGO' THEN 8
                    WHEN 'SEP' THEN 9
                    WHEN 'OCT' THEN 10
                    WHEN 'NOV' THEN 11
                    WHEN 'DIC' THEN 12
                    ELSE 99
                END,
                RIGHT(hoja_origen, 2)
        ");

        $hojas = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $hoja = trim((string) ($row['hoja_origen'] ?? ''));

            if ($hoja !== '') {
                $hojas[] = $hoja;
            }
        }

        json_response([
            'success' => true,
            'hojas' => $hojas,
        ]);
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'message' => 'Error al obtener meses/hojas',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

    public static function crearManual(): void
    {
        self::requireLoginJson();

        $data = get_json_input();
        $data['origen_registro'] = 'MANUAL';
        $data['hoja_origen'] = self::hojaManual($data['fecha'] ?? '');

        self::insertarCirugia($data);

        json_response([
            'success' => true,
            'message' => 'Registro guardado correctamente'
        ]);
    }

    public static function actualizar(int $id): void
    {
        self::requireLoginJson();

        $data = get_json_input();

        $sets = [];
        $params = [
            ':id' => $id
        ];

        foreach (self::CAMPOS as $campo) {
            if (!array_key_exists($campo, $data)) {
                continue;
            }

            $valor = self::limpiar($data[$campo]);

            if ($campo === 'edad') {
                $valor = $valor === null ? null : (int) $valor;
            }

            if ($campo === 'fecha') {
                $valor = self::convertirFecha($valor);
            }

            if ($campo === 'hora') {
                $valor = self::convertirHora($valor);
            }

            $sets[] = "$campo = :$campo";
            $params[":$campo"] = $valor;
        }

        if (!$sets) {
            json_response([
                'success' => false,
                'message' => 'No hay datos para actualizar'
            ], 400);
        }

        $sql = "
            UPDATE cirugias
            SET " . implode(', ', $sets) . "
            WHERE id = :id
        ";

        db()->prepare($sql)->execute($params);

        json_response([
            'success' => true,
            'message' => 'Registro actualizado correctamente'
        ]);
    }

    public static function eliminarTodo(): void
    {
        self::requireAdminJson();

        db()->exec("DELETE FROM cirugias");
        db()->exec("ALTER TABLE cirugias AUTO_INCREMENT = 1");

        json_response([
            'success' => true,
            'message' => 'Todos los registros fueron eliminados'
        ]);
    }

    public static function especialidades(): void
    {
        self::requireLoginJson();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::requireAdminJson();

            $input = get_json_input();
            $nombre = mb_strtoupper(trim((string) ($input['nombre'] ?? '')));

            if ($nombre === '') {
                json_response([
                    'success' => false,
                    'message' => 'Ingrese el nombre de la especialidad'
                ], 400);
            }

            $stmt = db()->prepare("
                INSERT IGNORE INTO cirugias_especialidades (nombre)
                VALUES (:nombre)
            ");

            $stmt->execute([
                ':nombre' => $nombre
            ]);

            json_response([
                'success' => true,
                'message' => 'Especialidad registrada'
            ]);
        }

        $stmt = db()->query("
            SELECT nombre
            FROM cirugias_especialidades
            WHERE estado = 1

            UNION

            SELECT DISTINCT especialidad AS nombre
            FROM cirugias
            WHERE COALESCE(especialidad, '') <> ''

            ORDER BY nombre
        ");

        json_response([
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }

    public static function excelHojas(): void
    {
        self::requireLoginJson();
        self::cargarPhpSpreadsheet();

        if (empty($_FILES['archivo']['tmp_name'])) {
            json_response([
                'success' => false,
                'message' => 'No se recibió ningún archivo'
            ], 400);
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['archivo']['tmp_name']);

        json_response([
            'success' => true,
            'hojas' => $spreadsheet->getSheetNames()
        ]);
    }

    public static function importarExcel(): void
    {
        self::requireLoginJson();
        self::cargarPhpSpreadsheet();

        if (empty($_FILES['archivo']['tmp_name'])) {
            json_response([
                'success' => false,
                'message' => 'No se recibió ningún archivo'
            ], 400);
        }

        $archivo = $_FILES['archivo'];
        $hojaSeleccionada = trim((string) ($_POST['hoja'] ?? ''));

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo['tmp_name']);

        $sheet = $hojaSeleccionada !== ''
            ? $spreadsheet->getSheetByName($hojaSeleccionada)
            : $spreadsheet->getSheet(0);

        if (!$sheet) {
            json_response([
                'success' => false,
                'message' => 'La hoja seleccionada no existe'
            ], 400);
        }

        $hojaNombre = $sheet->getTitle();
        $filas = $sheet->toArray(null, true, false, true);

        if (count($filas) < 2) {
            json_response([
                'success' => false,
                'message' => 'El Excel no tiene registros'
            ], 400);
        }

        $cabecera = array_shift($filas);
        $columnas = [];

        foreach ($cabecera as $letra => $titulo) {
            $key = self::normalizarCabecera((string) $titulo);

            if (isset(self::MAPA_EXCEL[$key])) {
                $columnas[$letra] = self::MAPA_EXCEL[$key];
            }
        }

        if (!$columnas) {
            json_response([
                'success' => false,
                'message' => 'No se reconocieron las columnas del Excel'
            ], 400);
        }

        $insertados = 0;
        $observados = 0;

        foreach ($filas as $fila) {
            $data = [];

            foreach ($columnas as $letra => $campo) {
                $data[$campo] = $fila[$letra] ?? null;
            }

            $data['fecha'] = self::convertirFecha($data['fecha'] ?? null);
            $data['hora'] = self::convertirHora($data['hora'] ?? null);
            $data['edad'] = self::limpiar($data['edad'] ?? null);
            $data['origen_registro'] = 'EXCEL';
            $data['hoja_origen'] = $hojaNombre;

            if (empty($data['fecha']) && empty($data['dni']) && empty($data['historia_clinica']) && empty($data['nombres_apellidos'])) {
                $observados++;
                continue;
            }

            self::insertarCirugia($data);
            $insertados++;
        }

        $stmt = db()->prepare("
            INSERT INTO historial_importaciones_cirugias
            (nombre_archivo, hoja, total_registros, registros_validos, registros_observados, usuario)
            VALUES
            (:nombre_archivo, :hoja, :total, :validos, :observados, :usuario)
        ");

        $stmt->execute([
            ':nombre_archivo' => $archivo['name'] ?? '',
            ':hoja' => $hojaNombre,
            ':total' => count($filas),
            ':validos' => $insertados,
            ':observados' => $observados,
            ':usuario' => $_SESSION['cirugias_usuario'] ?? ''
        ]);

        json_response([
            'success' => true,
            'message' => 'Importación finalizada correctamente',
            'insertados' => $insertados,
            'observados' => $observados
        ]);
    }

    public static function importaciones(): void
    {
        self::requireLoginJson();

        $stmt = db()->query("
            SELECT *
            FROM historial_importaciones_cirugias
            ORDER BY fecha_carga DESC
            LIMIT 100
        ");

        json_response([
            'success' => true,
            'data' => $stmt->fetchAll()
        ]);
    }

public static function reporteMensual(): void
{
    self::requireLoginJson();

    try {
        $anio = (int) ($_GET['anio'] ?? 0);
        $mes = (int) ($_GET['mes'] ?? 0);

        if ($anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) {
            json_response([
                'ok' => false,
                'success' => false,
                'message' => 'Debes enviar anio y mes válidos. Ejemplo: ?anio=2026&mes=1',
            ], 422);
        }

        $stmt = db()->prepare("
            SELECT
                fecha,
                especialidad,
                tipo_orden,
                cirugia_mayor,
                cirugia_menor,
                tiempo_operacion,
                tiempo_anestesia,
                tiempo_total
            FROM cirugias
            WHERE fecha IS NOT NULL
            AND YEAR(fecha) = :anio
            AND MONTH(fecha) = :mes
        ");

        $stmt->execute([
            ':anio' => $anio,
            ':mes' => $mes,
        ]);

        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resumen = [
            'total_cirugias' => 0,

            'electiva_mayor' => 0,
            'electiva_menor' => 0,
            'electiva_total' => 0,

            'emergencia_mayor' => 0,
            'emergencia_menor' => 0,
            'emergencia_total' => 0,

            'total_horas' => 0,

            'electiva_mayor_detalle' => self::crearGrupoReporteMensual(),
            'electiva_menor_detalle' => self::crearGrupoReporteMensual(),
            'emergencia_mayor_detalle' => self::crearGrupoReporteMensual(),
            'emergencia_menor_detalle' => self::crearGrupoReporteMensual(),
        ];

        if (!$registros) {
            json_response([
                'ok' => true,
                'success' => true,
                'existe_reporte' => false,
                'periodo' => self::nombreMesReporteMensual($mes) . ' DEL ' . $anio,
                'anio' => $anio,
                'mes' => $mes,
                'resumen' => $resumen,
                'data' => [],
            ]);
        }

        $mapa = [];

        foreach ($registros as $item) {
            $servicio = self::normalizarServicioReporteMensual($item['especialidad'] ?? '');
            $tipoOrden = self::normalizarTextoReporteMensual($item['tipo_orden'] ?? '');

            $esElectiva = str_contains($tipoOrden, 'ELECTIVA');
            $esEmergencia = str_contains($tipoOrden, 'EMERGENCIA');

            if (!$esElectiva && !$esEmergencia) {
                continue;
            }

            $esMayor = self::tieneValorReporteMensual($item['cirugia_mayor'] ?? '');
            $esMenor = self::tieneValorReporteMensual($item['cirugia_menor'] ?? '');

            /*
            |--------------------------------------------------------------------------
            | IMPORTANTE:
            | Si tus registros importados no tienen marcado MAYOR/MENOR,
            | los contamos como cirugía menor para que el reporte no quede vacío.
            |--------------------------------------------------------------------------
            */
            if (!$esMayor && !$esMenor) {
                $esMenor = true;
            }

            /*
            | Si ambos están marcados, damos prioridad a MAYOR.
            */
            if ($esMayor && $esMenor) {
                $esMenor = false;
            }

            if (!isset($mapa[$servicio])) {
                $mapa[$servicio] = self::crearFilaReporteMensual($servicio);
            }

            $clave = '';

            if ($esElectiva && $esMayor) {
                $clave = 'electiva_mayor';
            } elseif ($esElectiva && $esMenor) {
                $clave = 'electiva_menor';
            } elseif ($esEmergencia && $esMayor) {
                $clave = 'emergencia_mayor';
            } elseif ($esEmergencia && $esMenor) {
                $clave = 'emergencia_menor';
            }

            if ($clave === '') {
                continue;
            }

            self::sumarGrupoReporteMensual($mapa[$servicio][$clave], $item);
            self::sumarGrupoReporteMensual($resumen[$clave . '_detalle'], $item);

            $resumen['total_cirugias']++;

            $minutosOperacion = self::parseMinutosReporteMensual($item['tiempo_operacion'] ?? '');
            $minutosAnestesia = self::parseMinutosReporteMensual($item['tiempo_anestesia'] ?? '');

            $resumen['total_horas'] += ($minutosOperacion + $minutosAnestesia) / 60;

            if ($clave === 'electiva_mayor') {
                $resumen['electiva_mayor']++;
            } elseif ($clave === 'electiva_menor') {
                $resumen['electiva_menor']++;
            } elseif ($clave === 'emergencia_mayor') {
                $resumen['emergencia_mayor']++;
            } elseif ($clave === 'emergencia_menor') {
                $resumen['emergencia_menor']++;
            }
        }

        $resumen['electiva_total'] = $resumen['electiva_mayor'] + $resumen['electiva_menor'];
        $resumen['emergencia_total'] = $resumen['emergencia_mayor'] + $resumen['emergencia_menor'];
        $resumen['total_horas'] = round((float) $resumen['total_horas'], 2);

        $resumen['electiva_mayor_detalle'] = self::redondearGrupoReporteMensual($resumen['electiva_mayor_detalle']);
        $resumen['electiva_menor_detalle'] = self::redondearGrupoReporteMensual($resumen['electiva_menor_detalle']);
        $resumen['emergencia_mayor_detalle'] = self::redondearGrupoReporteMensual($resumen['emergencia_mayor_detalle']);
        $resumen['emergencia_menor_detalle'] = self::redondearGrupoReporteMensual($resumen['emergencia_menor_detalle']);

        $data = [];

        foreach ($mapa as $fila) {
            $data[] = [
                'servicio' => $fila['servicio'],
                'electiva_mayor' => self::redondearGrupoReporteMensual($fila['electiva_mayor']),
                'electiva_menor' => self::redondearGrupoReporteMensual($fila['electiva_menor']),
                'emergencia_mayor' => self::redondearGrupoReporteMensual($fila['emergencia_mayor']),
                'emergencia_menor' => self::redondearGrupoReporteMensual($fila['emergencia_menor']),
            ];
        }

        $ordenServicios = [
            'GINECO. OBST.',
            'CIRUGIA',
            'CIRUGIA PLASTICA',
            'UROLOGIA',
            'TRAUMAT.',
            'OFTALMOLOG.',
            'OTORRINO',
            'NEFROLOGIA',
            'GASTROLOGIA',
            'PEDIATRIA',
            'NEUROCIRUGIA',
            'SIN SERVICIO',
        ];

        usort($data, static function (array $a, array $b) use ($ordenServicios): int {
            $ia = array_search($a['servicio'], $ordenServicios, true);
            $ib = array_search($b['servicio'], $ordenServicios, true);

            $ia = $ia === false ? 999 : $ia;
            $ib = $ib === false ? 999 : $ib;

            if ($ia === $ib) {
                return strcmp($a['servicio'], $b['servicio']);
            }

            return $ia <=> $ib;
        });

        json_response([
            'ok' => true,
            'success' => true,
            'existe_reporte' => true,
            'periodo' => self::nombreMesReporteMensual($mes) . ' DEL ' . $anio,
            'anio' => $anio,
            'mes' => $mes,
            'resumen' => $resumen,
            'data' => $data,
        ]);
    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al generar reporte mensual de cirugías',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

private static function normalizarTextoReporteMensual(mixed $valor): string
{
    $texto = trim((string) ($valor ?? ''));

    $texto = strtr($texto, [
        'Á' => 'A',
        'É' => 'E',
        'Í' => 'I',
        'Ó' => 'O',
        'Ú' => 'U',
        'Ü' => 'U',
        'Ñ' => 'N',
        'á' => 'A',
        'é' => 'E',
        'í' => 'I',
        'ó' => 'O',
        'ú' => 'U',
        'ü' => 'U',
        'ñ' => 'N',
    ]);

    $texto = mb_strtoupper($texto);
    $texto = preg_replace('/\s+/', ' ', $texto);

    return trim($texto ?? '');
}

private static function tieneValorReporteMensual(mixed $valor): bool
{
    $texto = self::normalizarTextoReporteMensual($valor);

    return !in_array($texto, ['', '0', 'NO', 'NULL', 'NINGUNO', '-'], true);
}

private static function normalizarServicioReporteMensual(mixed $valor): string
{
    $texto = self::normalizarTextoReporteMensual($valor);

    if ($texto === '') {
        return 'SIN SERVICIO';
    }

    if (str_contains($texto, 'GINE') || str_contains($texto, 'OBST')) {
        return 'GINECO. OBST.';
    }

    if (str_contains($texto, 'TRAUMA') || str_contains($texto, 'ORTOP')) {
        return 'TRAUMAT.';
    }

    if (str_contains($texto, 'OFTAL')) {
        return 'OFTALMOLOG.';
    }

    if (str_contains($texto, 'OTORR') || str_contains($texto, 'OTORINO') || str_contains($texto, 'ORL')) {
        return 'OTORRINO';
    }

    if (str_contains($texto, 'URO')) {
        return 'UROLOGIA';
    }

    if (str_contains($texto, 'PLAST')) {
        return 'CIRUGIA PLASTICA';
    }

    if (str_contains($texto, 'NEFRO')) {
        return 'NEFROLOGIA';
    }

    if (str_contains($texto, 'GASTRO')) {
        return 'GASTROLOGIA';
    }

    if (str_contains($texto, 'PEDI')) {
        return 'PEDIATRIA';
    }

    if (str_contains($texto, 'NEURO')) {
        return 'NEUROCIRUGIA';
    }

    if (str_contains($texto, 'CIRUG')) {
        return 'CIRUGIA';
    }

    return $texto;
}

private static function parseMinutosReporteMensual(mixed $valor): float
{
    $texto = self::normalizarTextoReporteMensual($valor);

    if ($texto === '') {
        return 0;
    }

    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $texto, $m)) {
        $horas = (int) $m[1];
        $minutos = (int) $m[2];

        return ($horas * 60) + $minutos;
    }

    if (preg_match('/(\d+(?:\.\d+)?)\s*(H|HR|HRS|HORA|HORAS)/i', $texto, $m)) {
        return ((float) $m[1]) * 60;
    }

    if (preg_match('/(\d+(?:\.\d+)?)\s*(M|MIN|MINUTO|MINUTOS)/i', $texto, $m)) {
        return (float) $m[1];
    }

    if (is_numeric($texto)) {
        return (float) $texto;
    }

    if (preg_match('/\d+(?:\.\d+)?/', $texto, $m)) {
        return (float) $m[0];
    }

    return 0;
}

private static function crearGrupoReporteMensual(): array
{
    return [
        'numero' => 0,
        't_operatorio' => 0,
        't_anestesico' => 0,
        't_total_hrs' => 0,
    ];
}

private static function crearFilaReporteMensual(string $servicio): array
{
    return [
        'servicio' => $servicio,
        'electiva_mayor' => self::crearGrupoReporteMensual(),
        'electiva_menor' => self::crearGrupoReporteMensual(),
        'emergencia_mayor' => self::crearGrupoReporteMensual(),
        'emergencia_menor' => self::crearGrupoReporteMensual(),
    ];
}

private static function sumarGrupoReporteMensual(array &$grupo, array $item): void
{
    $grupo['numero']++;

    $minutosOperacion = self::parseMinutosReporteMensual($item['tiempo_operacion'] ?? '');
    $minutosAnestesia = self::parseMinutosReporteMensual($item['tiempo_anestesia'] ?? '');

    $grupo['t_operatorio'] += $minutosOperacion;
    $grupo['t_anestesico'] += $minutosAnestesia;
    $grupo['t_total_hrs'] += ($minutosOperacion + $minutosAnestesia) / 60;
}

private static function redondearGrupoReporteMensual(array $grupo): array
{
    return [
        'numero' => (int) ($grupo['numero'] ?? 0),
        't_operatorio' => round((float) ($grupo['t_operatorio'] ?? 0), 2),
        't_anestesico' => round((float) ($grupo['t_anestesico'] ?? 0), 2),
        't_total_hrs' => round((float) ($grupo['t_total_hrs'] ?? 0), 2),
    ];
}

private static function nombreMesReporteMensual(int $mes): string
{
    $meses = [
        1 => 'ENERO',
        2 => 'FEBRERO',
        3 => 'MARZO',
        4 => 'ABRIL',
        5 => 'MAYO',
        6 => 'JUNIO',
        7 => 'JULIO',
        8 => 'AGOSTO',
        9 => 'SEPTIEMBRE',
        10 => 'OCTUBRE',
        11 => 'NOVIEMBRE',
        12 => 'DICIEMBRE',
    ];

    return $meses[$mes] ?? 'MES';
}

/* =========================================================
   ANÁLISIS DE CIRUGÍAS
========================================================= */

private static function obtenerMesAnioAnalisis(): array
{
    $mes = (int) ($_GET['mes'] ?? 0);
    $anio = (int) ($_GET['anio'] ?? date('Y'));

    if ($mes < 1 || $mes > 12 || $anio < 2000 || $anio > 2100) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Mes o año inválido.',
        ], 422);
    }

    return [$mes, $anio];
}

private static function condicionCampoMarcadoAnalisis(string $campo): string
{
    $camposPermitidos = [
        'cirugia_mayor',
        'cirugia_menor',
    ];

    if (!in_array($campo, $camposPermitidos, true)) {
        return '1 = 0';
    }

    return "
        TRIM(COALESCE({$campo}, '')) <> ''
        AND UPPER(TRIM(COALESCE({$campo}, ''))) NOT IN ('NO', '0', 'NULL', 'NINGUNO', '-')
    ";
}

private static function condicionTipoCirugiaAnalisis(string $tipoCirugia): ?string
{
    $tipoCirugia = strtoupper(trim($tipoCirugia));

    if ($tipoCirugia === 'MAYOR') {
        return self::condicionCampoMarcadoAnalisis('cirugia_mayor');
    }

    if ($tipoCirugia === 'MENOR') {
        return self::condicionCampoMarcadoAnalisis('cirugia_menor');
    }

    return null;
}

private static function aplicarFiltroTipoOrdenAnalisis(array &$where, array &$params, string $tipoOrden): void
{
    $tipoOrden = strtoupper(trim($tipoOrden));

    if ($tipoOrden === '') {
        return;
    }

    if (strpos($tipoOrden, 'EMER') !== false) {
        $where[] = "UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%EMER%'";
        return;
    }

    if (strpos($tipoOrden, 'ELECT') !== false) {
        $where[] = "UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%ELECT%'";
        return;
    }

    $where[] = "UPPER(TRIM(COALESCE(tipo_orden, ''))) = :tipo_orden";
    $params[':tipo_orden'] = $tipoOrden;
}

public static function analisisMeses(): void
{
    self::requireLoginJson();

    try {
        $stmt = db()->query("
            SELECT
                YEAR(fecha) AS anio,
                MONTH(fecha) AS mes,
                COUNT(*) AS total
            FROM cirugias
            WHERE fecha IS NOT NULL
            AND YEAR(fecha) = (
                SELECT MAX(YEAR(fecha))
                FROM cirugias
                WHERE fecha IS NOT NULL
            )
            GROUP BY YEAR(fecha), MONTH(fecha)
            ORDER BY mes ASC
        ");

        json_response([
            'ok' => true,
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);

    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener meses disponibles.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function analisisMensual(): void
{
    self::requireLoginJson();

    try {
        $anio = (int) ($_GET['anio'] ?? date('Y'));

        $stmt = db()->prepare("
            SELECT
                MONTH(fecha) AS mes,
                CASE
                    WHEN UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%EMER%' THEN 'EMERGENCIA'
                    WHEN UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%ELECT%' THEN 'ELECTIVA'
                    ELSE COALESCE(NULLIF(UPPER(TRIM(tipo_orden)), ''), 'SIN TIPO')
                END AS tipo_orden,
                COUNT(*) AS total
            FROM cirugias
            WHERE fecha IS NOT NULL
            AND YEAR(fecha) = :anio
            GROUP BY MONTH(fecha), tipo_orden
            ORDER BY mes ASC, tipo_orden ASC
        ");

        $stmt->execute([
            ':anio' => $anio,
        ]);

        json_response([
            'ok' => true,
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);

    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener análisis mensual.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function analisisTipoOrden(): void
{
    self::requireLoginJson();

    [$mes, $anio] = self::obtenerMesAnioAnalisis();

    try {
        $stmt = db()->prepare("
            SELECT
                CASE
                    WHEN UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%EMER%' THEN 'EMERGENCIA'
                    WHEN UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%ELECT%' THEN 'ELECTIVA'
                    ELSE COALESCE(NULLIF(UPPER(TRIM(tipo_orden)), ''), 'SIN TIPO')
                END AS tipo_orden,
                COUNT(*) AS total_pacientes,
                COUNT(*) AS total
            FROM cirugias
            WHERE fecha IS NOT NULL
            AND MONTH(fecha) = :mes
            AND YEAR(fecha) = :anio
            AND TRIM(COALESCE(tipo_orden, '')) <> ''
            GROUP BY tipo_orden
            ORDER BY
                CASE
                    WHEN tipo_orden = 'EMERGENCIA' THEN 1
                    WHEN tipo_orden = 'ELECTIVA' THEN 2
                    ELSE 3
                END
        ");

        $stmt->execute([
            ':mes' => $mes,
            ':anio' => $anio,
        ]);

        json_response([
            'ok' => true,
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);

    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener tipo de orden.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function analisisResumenPeriodo(): void
{
    self::requireLoginJson();

    [$mes, $anio] = self::obtenerMesAnioAnalisis();

    try {
        $stmt = db()->prepare("
            SELECT
                fecha AS dia_mayor_fecha,
                COUNT(*) AS dia_mayor_total
            FROM cirugias
            WHERE fecha IS NOT NULL
            AND MONTH(fecha) = :mes
            AND YEAR(fecha) = :anio
            GROUP BY fecha
            ORDER BY dia_mayor_total DESC, fecha ASC
            LIMIT 1
        ");

        $stmt->execute([
            ':mes' => $mes,
            ':anio' => $anio,
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        json_response([
            'ok' => true,
            'success' => true,
            'data' => [
                'dia_mayor_fecha' => $fila['dia_mayor_fecha'] ?? null,
                'dia_mayor_total' => (int) ($fila['dia_mayor_total'] ?? 0),
            ],
        ]);

    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener resumen del período.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function analisisMayorMenorElectiva(): void
{
    self::requireLoginJson();

    [$mes, $anio] = self::obtenerMesAnioAnalisis();

    try {
        $condMayor = self::condicionCampoMarcadoAnalisis('cirugia_mayor');
        $condMenor = self::condicionCampoMarcadoAnalisis('cirugia_menor');

        $sql = "
            SELECT
                'MAYOR' AS tipo_cirugia,
                COALESCE(SUM(CASE WHEN {$condMayor} THEN 1 ELSE 0 END), 0) AS total
            FROM cirugias
            WHERE fecha IS NOT NULL
            AND MONTH(fecha) = :mes_mayor
            AND YEAR(fecha) = :anio_mayor
            AND UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%ELECT%'

            UNION ALL

            SELECT
                'MENOR' AS tipo_cirugia,
                COALESCE(SUM(CASE WHEN {$condMenor} THEN 1 ELSE 0 END), 0) AS total
            FROM cirugias
            WHERE fecha IS NOT NULL
            AND MONTH(fecha) = :mes_menor
            AND YEAR(fecha) = :anio_menor
            AND UPPER(TRIM(COALESCE(tipo_orden, ''))) LIKE '%ELECT%'
        ";

        $stmt = db()->prepare($sql);

        $stmt->execute([
            ':mes_mayor' => $mes,
            ':anio_mayor' => $anio,
            ':mes_menor' => $mes,
            ':anio_menor' => $anio,
        ]);

        json_response([
            'ok' => true,
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);

    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener cirugía mayor/menor electiva.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function analisisEspecialidades(): void
{
    self::requireLoginJson();

    [$mes, $anio] = self::obtenerMesAnioAnalisis();

    $tipoOrden = trim((string) ($_GET['tipo_orden'] ?? ''));
    $tipoCirugia = trim((string) ($_GET['tipo_cirugia'] ?? ''));

    try {
        $where = [
            'fecha IS NOT NULL',
            'MONTH(fecha) = :mes',
            'YEAR(fecha) = :anio',
        ];

        $params = [
            ':mes' => $mes,
            ':anio' => $anio,
        ];

        self::aplicarFiltroTipoOrdenAnalisis($where, $params, $tipoOrden);

        $condTipoCirugia = self::condicionTipoCirugiaAnalisis($tipoCirugia);

        if ($condTipoCirugia !== null) {
            $where[] = $condTipoCirugia;
        }

        $sql = "
            SELECT
                COALESCE(NULLIF(TRIM(especialidad), ''), 'SIN ESPECIALIDAD') AS especialidad,
                COUNT(*) AS total
            FROM cirugias
            WHERE " . implode(' AND ', $where) . "
            GROUP BY COALESCE(NULLIF(TRIM(especialidad), ''), 'SIN ESPECIALIDAD')
            ORDER BY total DESC, especialidad ASC
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        json_response([
            'ok' => true,
            'success' => true,
            'tipo_orden' => $tipoOrden !== '' ? $tipoOrden : null,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);

    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener especialidades.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function analisisDetalleEspecialidad(): void
{
    self::requireLoginJson();

    [$mes, $anio] = self::obtenerMesAnioAnalisis();

    $tipoOrden = trim((string) ($_GET['tipo_orden'] ?? ''));
    $tipoCirugia = trim((string) ($_GET['tipo_cirugia'] ?? ''));
    $especialidad = trim((string) ($_GET['especialidad'] ?? ''));
    $personal = trim((string) ($_GET['personal'] ?? ''));

    if ($especialidad === '') {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Especialidad requerida.',
        ], 422);
    }

    try {
        $where = [
            'fecha IS NOT NULL',
            'MONTH(fecha) = :mes',
            'YEAR(fecha) = :anio',
        ];

        $params = [
            ':mes' => $mes,
            ':anio' => $anio,
        ];

        self::aplicarFiltroTipoOrdenAnalisis($where, $params, $tipoOrden);

        $condTipoCirugia = self::condicionTipoCirugiaAnalisis($tipoCirugia);

        if ($condTipoCirugia !== null) {
            $where[] = $condTipoCirugia;
        }

        if (strtoupper($especialidad) === 'SIN ESPECIALIDAD') {
            $where[] = "(especialidad IS NULL OR TRIM(especialidad) = '')";
        } else {
            $where[] = "UPPER(TRIM(especialidad)) = UPPER(TRIM(:especialidad))";
            $params[':especialidad'] = $especialidad;
        }

        if ($personal !== '') {
            $where[] = "(
                cirujano_1 LIKE :personal_1
                OR cirujano_2 LIKE :personal_2
                OR anestesiologo LIKE :personal_3
                OR anestesiologo_recuperacion LIKE :personal_4
                OR enfermera_instrumentista LIKE :personal_5
                OR enfermera_recuperacion LIKE :personal_6
                OR tecnico_enfermeria_1 LIKE :personal_7
                OR tecnico_enfermeria_2 LIKE :personal_8
            )";

            $params[':personal_1'] = '%' . $personal . '%';
            $params[':personal_2'] = '%' . $personal . '%';
            $params[':personal_3'] = '%' . $personal . '%';
            $params[':personal_4'] = '%' . $personal . '%';
            $params[':personal_5'] = '%' . $personal . '%';
            $params[':personal_6'] = '%' . $personal . '%';
            $params[':personal_7'] = '%' . $personal . '%';
            $params[':personal_8'] = '%' . $personal . '%';
        }

        $sql = "
            SELECT
                fecha,
                hora,
                historia_clinica,
                dni,
                nombres_apellidos,
                edad,
                sexo,
                diagnostico_preoperatorio,
                operacion_realizada,
                cirujano_1,
                cirujano_2,
                anestesiologo,
                destino,
                observaciones
            FROM cirugias
            WHERE " . implode(' AND ', $where) . "
            ORDER BY fecha ASC, hora ASC, id ASC
            LIMIT 500
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        json_response([
            'ok' => true,
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);

    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener detalle de especialidad.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

    private static function insertarCirugia(array $data): void
    {
        $cols = [];
        $placeholders = [];
        $params = [];

        foreach (self::CAMPOS as $campo) {
            $cols[] = $campo;
            $placeholders[] = ':' . $campo;

            $valor = $data[$campo] ?? null;

            if ($campo === 'fecha') {
                $valor = self::convertirFecha($valor);
            } elseif ($campo === 'hora') {
                $valor = self::convertirHora($valor);
            } elseif ($campo === 'edad') {
                $valor = self::limpiar($valor);
                $valor = $valor === null ? null : (int) $valor;
            } else {
                $valor = self::limpiar($valor);
            }

            $params[':' . $campo] = $valor;
        }

        $sql = "
            INSERT INTO cirugias
            (" . implode(', ', $cols) . ")
            VALUES
            (" . implode(', ', $placeholders) . ")
        ";

        db()->prepare($sql)->execute($params);
    }

    private static function cargarPhpSpreadsheet(): void
    {
        $autoload = BASE_PATH . '/vendor/autoload.php';

        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            json_response([
                'success' => false,
                'message' => 'Falta instalar PhpSpreadsheet. Ejecuta: composer require phpoffice/phpspreadsheet'
            ], 500);
        }
    }

    private static function limpiar(mixed $valor): mixed
    {
        if ($valor === null) {
            return null;
        }

        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

private static function convertirFecha(mixed $valor): ?string
{
    if ($valor === null || $valor === '') {
        return null;
    }

    if ($valor instanceof DateTimeInterface) {
        return $valor->format('Y-m-d');
    }

    /*
    |--------------------------------------------------------------------------
    | FECHA NUMÉRICA DE EXCEL
    | Ejemplo:
    | 46112 = 2026-03-31
    |--------------------------------------------------------------------------
    */
    if (is_numeric($valor) && class_exists(\PhpOffice\PhpSpreadsheet\Shared\Date::class)) {
        $numero = (float) $valor;

        if ($numero > 20000 && $numero < 80000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($numero)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }
    }

    $texto = trim((string) $valor);

    if ($texto === '') {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | FORMATO ISO: 2026-03-31
    |--------------------------------------------------------------------------
    */
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $texto, $m)) {
        $anio = (int) $m[1];
        $mes = (int) $m[2];
        $dia = (int) $m[3];

        if (checkdate($mes, $dia, $anio)) {
            return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | FORMATO CON SLASH:
    | 31/03/2026 = DD/MM/YYYY
    | 03/31/2026 = MM/DD/YYYY
    |--------------------------------------------------------------------------
    */
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2}|\d{4})$/', $texto, $m)) {
        $a = (int) $m[1];
        $b = (int) $m[2];
        $anio = (int) $m[3];

        if ($anio < 100) {
            $anio += 2000;
        }

        /*
          Si el segundo número es mayor a 12, no puede ser mes.
          Entonces el formato es MM/DD/YYYY.
          Ejemplo: 03/31/2026.
        */
        if ($b > 12) {
            $mes = $a;
            $dia = $b;
        }
        /*
          Si el primer número es mayor a 12, no puede ser mes.
          Entonces el formato es DD/MM/YYYY.
          Ejemplo: 31/03/2026.
        */
        elseif ($a > 12) {
            $dia = $a;
            $mes = $b;
        }
        /*
          Si ambos son menores o iguales a 12, usamos formato peruano:
          DD/MM/YYYY.
        */
        else {
            $dia = $a;
            $mes = $b;
        }

        if (checkdate($mes, $dia, $anio)) {
            return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | FORMATO CON GUION: 31-03-2026
    |--------------------------------------------------------------------------
    */
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2}|\d{4})$/', $texto, $m)) {
        $dia = (int) $m[1];
        $mes = (int) $m[2];
        $anio = (int) $m[3];

        if ($anio < 100) {
            $anio += 2000;
        }

        if (checkdate($mes, $dia, $anio)) {
            return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        }

        return null;
    }

    return null;
}

    private static function convertirHora(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof DateTimeInterface) {
            return $valor->format('H:i:s');
        }

        if (is_numeric($valor) && (float) $valor > 0 && (float) $valor < 1) {
            return gmdate('H:i:s', (int) round((float) $valor * 86400));
        }

        $valor = trim((string) $valor);

        if (preg_match('/^\d{1,2}:\d{2}$/', $valor)) {
            return $valor . ':00';
        }

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $valor)) {
            return $valor;
        }

        $time = strtotime($valor);

        return $time ? date('H:i:s', $time) : null;
    }

    private static function normalizarCabecera(string $texto): string
    {
        $texto = trim(mb_strtoupper($texto));

        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N'
        ]);

        $texto = preg_replace('/\s+/', ' ', $texto);

        return $texto ?? '';
    }

    private static function hojaManual(mixed $fecha): string
    {
        $fecha = self::convertirFecha($fecha);

        if (!$fecha) {
            return 'MANUAL';
        }

        $meses = [
            1 => 'ENE',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'ABR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AGO',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DIC'
        ];

        $time = strtotime($fecha);
        $mes = (int) date('n', $time);

        return $meses[$mes] . date('y', $time);
    }
/* =========================================================
   SIGH - PACIENTES
========================================================= */

public static function pacientes(): void
{
    self::requireLoginJson();

    try {
        $dni = trim((string) ($_GET['dni'] ?? ''));
        $historia = trim((string) ($_GET['historia'] ?? ''));
        $busqueda = trim((string) ($_GET['busqueda'] ?? $_GET['q'] ?? ''));
        $limit = (int) ($_GET['limit'] ?? 150);

        if ($limit <= 0) {
            $limit = 150;
        }

        if ($limit > 150) {
            $limit = 150;
        }

        $pdo = db_sigh();

        /*
        |--------------------------------------------------------------------------
        | MODO AUTOCOMPLETADO
        | /pacientes/buscar?dni=...
        | /pacientes/buscar?historia=...
        |--------------------------------------------------------------------------
        */
        if ($dni !== '' || $historia !== '') {
            if ($dni !== '' && !preg_match('/^\d{8}$/', $dni)) {
                json_response([
                    'success' => false,
                    'message' => 'DNI inválido',
                ], 400);
            }

            $where = ['1 = 1'];
            $params = [];

            if ($dni !== '') {
                $where[] = 'p.NroDocumento = :dni';
                $params[':dni'] = $dni;
            }

            if ($historia !== '') {
                $where[] = 'CAST(p.NroHistoriaClinica AS VARCHAR(50)) = :historia';
                $params[':historia'] = $historia;
            }

            $sql = "
                SELECT TOP 1
                    p.IdPaciente,
                    p.NroHistoriaClinica,
                    p.NroDocumento,
                    p.ApellidoPaterno,
                    p.ApellidoMaterno,
                    p.PrimerNombre,
                    p.SegundoNombre,
                    p.TercerNombre,
                    p.FechaNacimiento,
                    p.IdTipoSexo
                FROM SIGH.dbo.Pacientes p
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.IdPaciente DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$p) {
                json_response([
                    'success' => false,
                    'message' => 'Paciente no encontrado',
                ], 404);
            }

            $cie10 = self::obtenerCIE10PorHistoriaClinica(
                (string) ($p['NroHistoriaClinica'] ?? '')
            );

            json_response([
                'success' => true,
                'data' => [
                    'historia_clinica' => $p['NroHistoriaClinica'] ?? '',
                    'dni' => $p['NroDocumento'] ?? '',
                    'nombres_apellidos' => self::construirNombrePaciente($p),
                    'edad' => self::calcularEdadDesdeFecha($p['FechaNacimiento'] ?? null),
                    'sexo' => self::convertirSexoPaciente($p['IdTipoSexo'] ?? null),
                    'tipo_seguro' => self::obtenerTipoSeguroPaciente(
                        $pdo,
                        (int) ($p['IdPaciente'] ?? 0)
                    ),
                    'codigo_cie10' => $cie10['CodigoCIE10'] ?? '',
                    'codigo_cie10_sin_punto' => $cie10['CodigoCIE10SinPunto'] ?? '',
                    'diagnostico_preoperatorio' => $cie10['Diagnostico'] ?? '',
                    'diagnostico_minsa' => $cie10['DiagnosticoMINSA'] ?? '',
                    'id_diagnostico' => $cie10['IdDiagnostico'] ?? null,
                    'id_atencion' => $cie10['IdAtencion'] ?? null,
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | MODO LISTADO / BÚSQUEDA DE PACIENTES
        | /pacientes?busqueda=...
        |--------------------------------------------------------------------------
        */
        $where = '';
        $params = [];

        if ($busqueda !== '') {
            if (preg_match('/^\d+$/', $busqueda)) {
                $where = "
                    WHERE 
                        CAST(p.NroHistoriaClinica AS VARCHAR(50)) = :historia
                        OR p.NroDocumento LIKE :busqueda
                ";

                $params[':historia'] = $busqueda;
                $params[':busqueda'] = '%' . $busqueda . '%';
            } else {
                $where = "
                    WHERE
                        CONCAT(
                            LTRIM(RTRIM(ISNULL(p.ApellidoPaterno, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.ApellidoMaterno, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.PrimerNombre, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.SegundoNombre, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.TercerNombre, '')))
                        ) COLLATE Latin1_General_CI_AI LIKE :busqueda
                        OR
                        CONCAT(
                            LTRIM(RTRIM(ISNULL(p.PrimerNombre, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.SegundoNombre, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.TercerNombre, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.ApellidoPaterno, ''))), ' ',
                            LTRIM(RTRIM(ISNULL(p.ApellidoMaterno, '')))
                        ) COLLATE Latin1_General_CI_AI LIKE :busqueda
                ";

                $params[':busqueda'] = '%' . $busqueda . '%';
            }
        }

        $sql = "
            SELECT TOP {$limit}
                p.IdPaciente,
                p.NroHistoriaClinica,
                p.NroDocumento,
                p.ApellidoPaterno,
                p.ApellidoMaterno,
                p.PrimerNombre,
                p.SegundoNombre,
                p.TercerNombre,
                p.FechaNacimiento,
                p.Telefono,
                p.DireccionDomicilio,
                p.IdTipoSexo,
                p.Observacion
            FROM SIGH.dbo.Pacientes p
            {$where}
            ORDER BY p.IdPaciente DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        json_response([
            'success' => true,
            'ok' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);

    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'ok' => false,
            'message' => 'Error consultando pacientes en SIGH',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

private static function sexoPaciente(mixed $idTipoSexo): string
{
    $id = (int) $idTipoSexo;

    return match ($id) {
        1 => 'M',
        2 => 'F',
        default => '',
    };
}

/* =========================================================
   SIGH - PERSONAL MÉDICO
========================================================= */

public static function personalMedico(): void
{
    self::requireLoginJson();

    try {
        $busqueda = trim(
            (string) ($_GET['busqueda'] ?? '')
        );

        $campo = trim(
            (string) ($_GET['campo'] ?? '')
        );

        $profesionFiltro = trim(
            (string) ($_GET['profesion'] ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | Cada campo del formulario tiene una profesión fija
        |--------------------------------------------------------------------------
        */
        $profesionesPorCampo = [
            'cirujano_1' => 'CIRUJANO',
            'cirujano_2' => 'CIRUJANO',

            'anestesiologo' => 'ANESTESIOLOGO',
            'anestesiologo_recuperacion' => 'ANESTESIOLOGO',

            'enfermera_instrumentista' =>
                'LICENCIADA(O) ENFERMERIA',

            'enfermera_recuperacion' =>
                'LICENCIADA(O) ENFERMERIA',

            'tecnico_enfermeria_1' =>
                'TECNICO DE ENFERMERIA',

            'tecnico_enfermeria_2' =>
                'TECNICO DE ENFERMERIA',
        ];

        $where = ['1 = 1'];
        $params = [];

        /*
        |--------------------------------------------------------------------------
        | Buscar por DNI, nombre o apellido
        |--------------------------------------------------------------------------
        */
        if ($busqueda !== '') {
            $where[] = "(
                dni LIKE :busqueda_dni
                OR apellidos_nombres LIKE :busqueda_nombre
            )";

            $valorBusqueda = '%' . $busqueda . '%';

            $params[':busqueda_dni'] = $valorBusqueda;
            $params[':busqueda_nombre'] = $valorBusqueda;
        }

        /*
        |--------------------------------------------------------------------------
        | Búsqueda desde los campos del formulario
        |--------------------------------------------------------------------------
        */
        if ($campo !== '') {
            if (!array_key_exists($campo, $profesionesPorCampo)) {
                json_response([
                    'success' => false,
                    'message' => 'Campo de personal no permitido',
                ], 422);
            }

            $profesionCampo = $profesionesPorCampo[$campo];

            /*
             * Comparación exacta.
             * No utiliza LIKE y no permite otra profesión.
             */
            $where[] = "
                UPPER(TRIM(profesion)) = :profesion_campo
            ";

            $where[] = "
                UPPER(TRIM(estado)) = 'ACTIVO'
            ";

            $params[':profesion_campo'] =
                mb_strtoupper(trim($profesionCampo));

            $limit = 10;
        } else {
            /*
             * Filtro de la tabla de Gestión.
             */
            if ($profesionFiltro !== '') {
                $where[] = "
                    UPPER(TRIM(profesion)) = :profesion_filtro
                ";

                $params[':profesion_filtro'] =
                    mb_strtoupper(
                        trim($profesionFiltro)
                    );
            }

            $limit = 200;
        }

        $sql = "
            SELECT
                id,
                dni,
                apellidos_nombres,
                profesion,
                modalidad_contrato,
                colegio_profesional,
                numero_colegiatura,
                registro_especialidad,
                estado
            FROM personal_medico
            WHERE " . implode(' AND ', $where) . "
            ORDER BY apellidos_nombres ASC
            LIMIT {$limit}
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        json_response([
            'success' => true,
            'data' => $stmt->fetchAll(
                PDO::FETCH_ASSOC
            ),
        ]);
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'message' =>
                'Error al buscar el personal médico',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function personalProfesiones(): void
{
    self::requireLoginJson();

    try {
        $stmt = db()->query("
            SELECT DISTINCT profesion
            FROM personal_medico
            WHERE profesion IS NOT NULL
              AND TRIM(profesion) <> ''
            ORDER BY profesion ASC
        ");

        json_response(
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'message' => 'Error al cargar las profesiones',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

/* =========================================================
   SIGH - PROCEDIMIENTOS / CPMS
========================================================= */

public static function procedimientos(): void
{
    self::requireLoginJson();

    try {
        $q = trim((string) (
            $_GET['q']
            ?? $_GET['busqueda']
            ?? $_GET['buscar']
            ?? ''
        ));

        $seccion = trim((string) ($_GET['seccion'] ?? ''));
        $limit = (int) ($_GET['limit'] ?? 100);

        if ($limit <= 0) {
            $limit = 100;
        }

        if ($limit > 200) {
            $limit = 200;
        }

        $pdo = db_sigh();

        $where = ['1 = 1'];
        $params = [];

        if ($q !== '') {
            $where[] = "(
                f.Codigo COLLATE Latin1_General_CI_AI LIKE :q_codigo
                OR f.Nombre COLLATE Latin1_General_CI_AI LIKE :q_nombre
                OR f.NombreMINSA COLLATE Latin1_General_CI_AI LIKE :q_nombre_minsa
                OR g.Descripcion COLLATE Latin1_General_CI_AI LIKE :q_grupo
                OR sg.Descripcion COLLATE Latin1_General_CI_AI LIKE :q_subgrupo
                OR sec.Descripcion COLLATE Latin1_General_CI_AI LIKE :q_seccion
                OR ssec.Descripcion COLLATE Latin1_General_CI_AI LIKE :q_subseccion
            )";

            $valorBusqueda = '%' . $q . '%';

            $params[':q_codigo'] = $valorBusqueda;
            $params[':q_nombre'] = $valorBusqueda;
            $params[':q_nombre_minsa'] = $valorBusqueda;
            $params[':q_grupo'] = $valorBusqueda;
            $params[':q_subgrupo'] = $valorBusqueda;
            $params[':q_seccion'] = $valorBusqueda;
            $params[':q_subseccion'] = $valorBusqueda;
        }

        if ($seccion !== '') {
            $where[] = "
                LTRIM(RTRIM(sec.Descripcion))
                COLLATE Latin1_General_CI_AI
                = :seccion
            ";

            $params[':seccion'] = $seccion;
        }

        $sql = "
            SELECT TOP {$limit}
                f.IdProducto,
                LTRIM(RTRIM(f.Codigo)) AS Codigo,
                LTRIM(RTRIM(f.Nombre)) AS Nombre,
                LTRIM(RTRIM(f.CodMINSA)) AS CodMINSA,
                LTRIM(RTRIM(f.NombreMINSA)) AS NombreMINSA,
                f.EsCPT,
                LTRIM(RTRIM(g.Descripcion)) AS Grupo,
                LTRIM(RTRIM(sg.Descripcion)) AS SubGrupo,
                LTRIM(RTRIM(sec.Descripcion)) AS Seccion,
                LTRIM(RTRIM(ssec.Descripcion)) AS SubSeccion
            FROM SIGH.dbo.FactCatalogoServicios f

            LEFT JOIN SIGH.dbo.FactCatalogoServiciosGrupo g
                ON g.IdServicioGrupo = f.IdServicioGrupo

            LEFT JOIN SIGH.dbo.FactCatalogoServiciosSubGrupo sg
                ON sg.IdServicioSubGrupo = f.IdServicioSubGrupo

            LEFT JOIN SIGH.dbo.FactCatalogoServiciosSeccion sec
                ON sec.IdServicioSeccion = f.IdServicioSeccion

            LEFT JOIN SIGH.dbo.FactCatalogoServiciosSubSeccion ssec
                ON ssec.IdServicioSubSeccion = f.IdServicioSubSeccion

            WHERE " . implode(' AND ', $where) . "

            ORDER BY
                CASE
                    WHEN sec.Descripcion
                         COLLATE Latin1_General_CI_AI
                         LIKE '%cirug%'
                    THEN 0
                    ELSE 1
                END,
                f.Nombre
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map(
            static function (array $item): array {
                return [
                    'id_producto' => $item['IdProducto'] ?? null,
                    'codigo' => $item['Codigo'] ?? '',
                    'nombre' => $item['Nombre'] ?? '',
                    'codigo_minsa' => $item['CodMINSA'] ?? '',
                    'nombre_minsa' => $item['NombreMINSA'] ?? '',
                    'grupo' => $item['Grupo'] ?? '',
                    'subgrupo' => $item['SubGrupo'] ?? '',
                    'seccion' => $item['Seccion'] ?? '',
                    'subseccion' => $item['SubSeccion'] ?? '',
                    'operacion_realizada' => trim(
                        ($item['Codigo'] ?? '')
                        . ' - '
                        . ($item['Nombre'] ?? '')
                    ),
                ];
            },
            $rows
        );

        json_response([
            'ok' => true,
            'success' => true,
            'total' => count($data),
            'data' => $data,
        ]);
    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error consultando procedimientos en SIGH',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function procedimientosSecciones(): void
{
    try {
        $pdo = db_sigh();

        $stmt = $pdo->query("
            SELECT DISTINCT
                sec.IdServicioSeccion,
                LTRIM(RTRIM(sec.Descripcion)) AS Seccion
            FROM SIGH.dbo.FactCatalogoServiciosSeccion sec
            WHERE sec.Descripcion IS NOT NULL
            ORDER BY Seccion
        ");

        json_response([
            'ok' => true,
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error consultando secciones de procedimientos',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

/* =========================================================
   SIGH - CIE10
========================================================= */

public static function cie10(): void
{
    self::requireLoginJson();

    try {
        $q = trim((string) (
            $_GET['q']
            ?? $_GET['busqueda']
            ?? ''
        ));

        $estado = trim((string) ($_GET['estado'] ?? ''));
        $limit = (int) ($_GET['limit'] ?? 100);

        if ($limit <= 0) {
            $limit = 100;
        }

        if ($limit > 200) {
            $limit = 200;
        }

        $where = ['1 = 1'];
        $params = [];

        /*
        |--------------------------------------------------------------------------
        | Búsqueda por código o descripción
        |--------------------------------------------------------------------------
        */
        if ($q !== '') {
            $where[] = "(
                CodigoCIE10 LIKE :q_codigo
                OR codigoCIEsinPto LIKE :q_sin_punto
                OR Descripcion COLLATE Latin1_General_CI_AI LIKE :q_descripcion
                OR DescripcionMINSA COLLATE Latin1_General_CI_AI LIKE :q_minsa
            )";

            $valorBusqueda = '%' . $q . '%';

            $params[':q_codigo'] = $valorBusqueda;
            $params[':q_sin_punto'] = $valorBusqueda;
            $params[':q_descripcion'] = $valorBusqueda;
            $params[':q_minsa'] = $valorBusqueda;
        }

        /*
        |--------------------------------------------------------------------------
        | Filtro activo/inactivo
        |--------------------------------------------------------------------------
        */
        if ($estado === '0' || $estado === '1') {
            $where[] = 'ISNULL(EsActivo, 1) = :estado';
            $params[':estado'] = (int) $estado;
        }

        $pdo = db_sigh();

        $sql = "
            SELECT TOP {$limit}
                IdDiagnostico AS id,
                LTRIM(RTRIM(CodigoCIE10)) AS codigo,
                LTRIM(RTRIM(Descripcion)) AS descripcion
            FROM SIGH.dbo.Diagnosticos
            WHERE " . implode(' AND ', $where) . "
            ORDER BY CodigoCIE10
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        json_response(
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error consultando CIE10 en SIGH',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function cie10Estados(): void
{
    json_response([
        ['id' => 1, 'nombre' => 'Activo'],
        ['id' => 0, 'nombre' => 'Inactivo'],
    ]);
}

public static function cie10Sexos(): void
{
    json_response([
        ['id' => 1, 'nombre' => 'Masculino'],
        ['id' => 2, 'nombre' => 'Femenino'],
    ]);
}

/* =========================================================
   SIGH - OPERACIÓN POR CIE10
========================================================= */

public static function operacionPorCie10(): void
{
    try {
        $cie10 = trim((string) ($_GET['cie10'] ?? ''));
        $diagnostico = trim((string) ($_GET['diagnostico'] ?? ''));

        $texto = trim($cie10 . ' ' . $diagnostico);

        if ($texto === '') {
            json_response([
                'success' => true,
                'encontrado' => false,
                'data' => [
                    'operacion_realizada' => '',
                    'sugerencias_operacion' => [],
                    'mensaje' => 'No se recibió CIE10 ni diagnóstico.',
                ],
            ]);
        }

        $palabras = preg_split('/\s+/', $diagnostico);
        $palabras = array_values(array_filter($palabras, static function ($p) {
            return mb_strlen($p) >= 4;
        }));

        if (empty($palabras) && $cie10 !== '') {
            $palabras[] = $cie10;
        }

        $palabras = array_slice($palabras, 0, 5);

        $pdo = db_sigh();

        $whereParts = [];
        $params = [];

        foreach ($palabras as $i => $palabra) {
            $key = ':t' . $i;
            $params[$key] = '%' . $palabra . '%';

            $whereParts[] = "
                (
                    f.Codigo LIKE {$key}
                    OR f.CodMINSA LIKE {$key}
                    OR f.Nombre COLLATE Latin1_General_CI_AI LIKE {$key}
                    OR f.NombreMINSA COLLATE Latin1_General_CI_AI LIKE {$key}
                    OR g.Descripcion COLLATE Latin1_General_CI_AI LIKE {$key}
                    OR sg.Descripcion COLLATE Latin1_General_CI_AI LIKE {$key}
                    OR sec.Descripcion COLLATE Latin1_General_CI_AI LIKE {$key}
                    OR ssec.Descripcion COLLATE Latin1_General_CI_AI LIKE {$key}
                )
            ";
        }

        $where = implode(' OR ', $whereParts);

        $sql = "
            SELECT TOP 25
                f.IdProducto,
                LTRIM(RTRIM(f.Codigo)) AS Codigo,
                LTRIM(RTRIM(f.Nombre)) AS Nombre,
                LTRIM(RTRIM(f.CodMINSA)) AS CodMINSA,
                LTRIM(RTRIM(f.NombreMINSA)) AS NombreMINSA,
                g.Descripcion AS Grupo,
                sg.Descripcion AS SubGrupo,
                sec.Descripcion AS Seccion,
                ssec.Descripcion AS SubSeccion
            FROM SIGH.dbo.FactCatalogoServicios f
            LEFT JOIN SIGH.dbo.FactCatalogoServiciosGrupo g
                ON g.IdServicioGrupo = f.IdServicioGrupo
            LEFT JOIN SIGH.dbo.FactCatalogoServiciosSubGrupo sg
                ON sg.IdServicioSubGrupo = f.IdServicioSubGrupo
            LEFT JOIN SIGH.dbo.FactCatalogoServiciosSeccion sec
                ON sec.IdServicioSeccion = f.IdServicioSeccion
            LEFT JOIN SIGH.dbo.FactCatalogoServiciosSubSeccion ssec
                ON ssec.IdServicioSubSeccion = f.IdServicioSubSeccion
            WHERE {$where}
            ORDER BY
                CASE 
                    WHEN sec.Descripcion COLLATE Latin1_General_CI_AI LIKE '%cirug%' THEN 0
                    ELSE 1
                END,
                f.Nombre
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sugerencias = array_map(static function (array $item): array {
            return [
                'id_producto' => $item['IdProducto'] ?? null,
                'codigo' => $item['Codigo'] ?? '',
                'nombre' => $item['Nombre'] ?? '',
                'codigo_minsa' => $item['CodMINSA'] ?? '',
                'nombre_minsa' => $item['NombreMINSA'] ?? '',
                'grupo' => $item['Grupo'] ?? '',
                'subgrupo' => $item['SubGrupo'] ?? '',
                'seccion' => $item['Seccion'] ?? '',
                'subseccion' => $item['SubSeccion'] ?? '',
                'operacion_realizada' => trim(($item['Codigo'] ?? '') . ' - ' . ($item['Nombre'] ?? '')),
            ];
        }, $rows);

        json_response([
            'success' => true,
            'ok' => true,
            'encontrado' => count($sugerencias) > 0,
            'data' => [
                'operacion_realizada' => '',
                'operacion_autocompletada' => false,
                'sugerencias_operacion' => $sugerencias,
                'mensaje' => count($sugerencias) > 0
                    ? 'Se encontraron sugerencias. Selecciona la operación correcta.'
                    : 'No se encontraron sugerencias en SIGH.',
            ],
        ]);
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'ok' => false,
            'encontrado' => false,
            'message' => 'Error consultando operación por CIE10',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

/* =========================================================
   SIGH - TABLAS PARA PRUEBA
========================================================= */

public static function tablasSigh(): void
{
    try {
        $pdo = db_sigh();

        $stmt = $pdo->query("
            SELECT 
                TABLE_SCHEMA AS esquema,
                TABLE_NAME AS tabla,
                TABLE_TYPE AS tipo
            FROM SIGH.INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_SCHEMA, TABLE_NAME
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'ok' => true,
            'success' => true,
            'total' => count($data),
            'data' => $data,
        ]);
    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error consultando tablas de SIGH',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

private static function construirNombrePaciente(array $p): string
{
    return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
        $p['ApellidoPaterno'] ?? '',
        $p['ApellidoMaterno'] ?? '',
        $p['PrimerNombre'] ?? '',
        $p['SegundoNombre'] ?? '',
        $p['TercerNombre'] ?? '',
    ]))));
}

private static function calcularEdadDesdeFecha(mixed $fechaNacimiento): string
{
    if (empty($fechaNacimiento)) {
        return '';
    }

    try {
        $fecha = $fechaNacimiento instanceof DateTimeInterface
            ? $fechaNacimiento
            : new DateTime((string) $fechaNacimiento);

        $hoy = new DateTime();

        return (string) $hoy->diff($fecha)->y;
    } catch (Throwable) {
        return '';
    }
}

private static function convertirSexoPaciente(mixed $idTipoSexo): string
{
    $id = (int) $idTipoSexo;

    return match ($id) {
        1 => 'MASCULINO',
        2 => 'FEMENINO',
        default => '',
    };
}

private static function mapearTipoSeguro(mixed $descripcion): string
{
    $texto = mb_strtoupper(trim((string) $descripcion));

    if ($texto === '') return '';
    if (str_contains($texto, 'SIS')) return 'SIS';
    if (str_contains($texto, 'ESSALUD') || str_contains($texto, 'ES SALUD') || str_contains($texto, 'SEGURO SOCIAL')) return 'ESSALUD';
    if (str_contains($texto, 'SOAT')) return 'SOAT';
    if (str_contains($texto, 'FOSPOLI')) return 'FOSPOLI';
    if (str_contains($texto, 'PARTICULAR')) return 'PARTICULAR';

    return $texto;
}

private static function obtenerTipoSeguroPaciente(PDO $pdo, int $idPaciente): string
{
    if ($idPaciente <= 0) {
        return '';
    }

    try {
        $stmt = $pdo->prepare("
            SELECT TOP 1
                ff.Descripcion AS tipo_seguro
            FROM SIGH.dbo.Atenciones a
            LEFT JOIN SIGH.dbo.FuentesFinanciamiento ff
                ON ff.IdFuenteFinanciamiento = a.idFuenteFinanciamiento
            WHERE a.IdPaciente = :idPaciente
            AND ff.Descripcion IS NOT NULL
            ORDER BY a.IdAtencion DESC
        ");

        $stmt->execute([
            ':idPaciente' => $idPaciente,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['tipo_seguro'])) {
            $tipo = self::mapearTipoSeguro($row['tipo_seguro']);

            if ($tipo !== '') {
                return $tipo;
            }
        }
    } catch (Throwable) {
        // Si esa tabla/campo cambia en SIGH, no rompemos el autocompletado.
    }

    try {
        $stmt = $pdo->prepare("
            SELECT TOP 1
                idSunasaPacienteHistorico,
                EstadoDelSeguro,
                YaNoTieneSeguro,
                SisNroAfiliacion,
                FechaInicioAfiliacion
            FROM SIGH.dbo.SunasaPacientesHistoricos
            WHERE idPaciente = :idPaciente
            ORDER BY
                FechaInicioAfiliacion DESC,
                idSunasaPacienteHistorico DESC
        ");

        $stmt->execute([
            ':idPaciente' => $idPaciente,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && (int) ($row['YaNoTieneSeguro'] ?? 0) === 0) {
            return 'SIS';
        }
    } catch (Throwable) {
        // Si no existe SunasaPacientesHistoricos, simplemente no devuelve seguro.
    }

    return '';
}

private static function obtenerCIE10PorHistoriaClinica(string $nroHistoriaClinica): ?array
{
    $historia = trim($nroHistoriaClinica);

    if ($historia === '') {
        return null;
    }

    try {
        $pdo = db_sigh();

        $stmt = $pdo->prepare("
            SELECT TOP 1
                p.IdPaciente,
                p.NroHistoriaClinica,
                a.IdAtencion,
                d.IdDiagnostico,
                LTRIM(RTRIM(d.CodigoCIE10)) AS CodigoCIE10,
                LTRIM(RTRIM(d.codigoCIEsinPto)) AS CodigoCIE10SinPunto,
                d.Descripcion AS Diagnostico,
                d.DescripcionMINSA AS DiagnosticoMINSA
            FROM SIGH.dbo.Pacientes p
            INNER JOIN SIGH.dbo.Atenciones a
                ON a.IdPaciente = p.IdPaciente
            INNER JOIN SIGH.dbo.AtencionesDiagnosticos ad
                ON ad.IdAtencion = a.IdAtencion
            INNER JOIN SIGH.dbo.Diagnosticos d
                ON d.IdDiagnostico = ad.IdDiagnostico
            WHERE CAST(p.NroHistoriaClinica AS VARCHAR(50)) = :historia
            AND ISNULL(d.EsActivo, 1) = 1
            ORDER BY a.IdAtencion DESC, ad.IdAtencionDiagnostico DESC
        ");

        $stmt->execute([
            ':historia' => $historia,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    } catch (Throwable) {
        return null;
    }
}
public static function reportesMeses(): void
{
    self::requireLoginJson();

    try {
        $stmt = db()->query("
            SELECT
                YEAR(fecha) AS anio,
                MONTH(fecha) AS mes,
                COUNT(*) AS total_registros
            FROM cirugias
            WHERE fecha IS NOT NULL
            GROUP BY YEAR(fecha), MONTH(fecha)
            HAVING COUNT(*) > 0
            ORDER BY anio ASC, mes ASC
        ");

        json_response([
            'ok' => true,
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'message' => 'Error al obtener meses disponibles para reportes',
            'debug' => $e->getMessage(),
        ], 500);
    }
}
}