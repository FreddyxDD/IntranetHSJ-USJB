<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/response.php';
require_once BASE_PATH . '/app/helpers/modulos.php';

final class CitasAdminController
{
    private static function dbCitas(): PDO
    {
        return db_citas();
    }

    private static function dbSigh(): PDO
    {
        return db_sigh();
    }

    /*
       NUEVA LÓGICA:
       Antes se validaba $_SESSION['citas_admin_usuario'].
       Ahora el acceso depende del login general del intranet y del permiso citas_admin.
    */
    private static function requireAdmin(): void
    {
        if (empty($_SESSION['ueei_correo'])) {
            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'No has iniciado sesión en el intranet.',
            ], 401);
        }

        if (!modulo_autorizado('citas_admin')) {
            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'No tienes permiso para usar el módulo de Citas.',
            ], 403);
        }
    }
    private static function fechaInicio(): string
    {
        /*
           Citas diarias:
           - Si llega fecha por GET, se respeta aunque sea pasada o futura.
           - Si no llega fecha, se usa la fecha actual.
           - No se fuerza a hoy cuando el usuario escoge una fecha anterior.
        */
        $fecha = self::normalizarFechaFiltro($_GET['fechaInicio'] ?? '');
    
        if ($fecha === '') {
            return date('Y-m-d');
        }
    
        return $fecha;
    }
    private static function fechaFin(): string
    {
        /*
           La tabla de citas diarias solo debe consultar un día cuando se envía una fecha.
           Si fechaFin viene vacía, se usa fechaInicio.
        */
        $fechaInicio = self::fechaInicio();
        $fecha = self::normalizarFechaFiltro($_GET['fechaFin'] ?? '');
    
        if ($fecha === '') {
            return $fechaInicio;
        }
    
        if ($fecha < $fechaInicio) {
            return $fechaInicio;
        }
    
        return $fecha;
    }



    private static function normalizarFechaFiltro(mixed $valor): string
    {
        $fecha = trim((string) $valor);

        if ($fecha === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        return substr($fecha, 0, 10);
    }

    private static function valorFecha(mixed $valor): string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if ($valor === null || $valor === '') {
            return '';
        }

        return substr((string) $valor, 0, 10);
    }

    private static function valorHora(mixed $valor): string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('H:i');
        }

        if ($valor === null || $valor === '') {
            return '';
        }

        $texto = (string) $valor;

        if (preg_match('/(\d{2}:\d{2})/', $texto, $m)) {
            return $m[1];
        }

        return $texto;
    }

    private static function normalizarTexto(mixed $valor): string
    {
        return trim((string) ($valor ?? ''));
    }

        private static function normalizarNombreEspecialidadReporte(string $valor): string
    {
        $texto = trim($valor);

        if ($texto === '') {
            return '';
        }

        $texto = function_exists('mb_strtoupper')
            ? mb_strtoupper($texto, 'UTF-8')
            : strtoupper($texto);

        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);

        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim((string) $texto);
    }

    private static function especialidadExcluidaReporte(string $especialidad): bool
    {
        $especialidadesExcluidas = [
            'ECOGRAFIA',
            'ECOGRAFIA GENERAL',
            'ECOGRAFIA GINECO OBSTETRICA',
            'ECOGRAFIAS',
            'LABORATORIO CLINICO',
            'TERAPIA DE LENGUAJE I (SESION)',
            'TERAPIA FISICA I (SESION)',
            'TERAPIA FISICA II (SESION)',
            'TERAPIA FISICA III (SESION)',
            'TERAPIA FISICA IV (SESION)',
            'TERAPIA OCUPACIONAL',
            'TERAPIA OCUPACIONAL (SESION)',
        ];

        $nombreNormalizado =
            self::normalizarNombreEspecialidadReporte($especialidad);

        return in_array(
            $nombreNormalizado,
            $especialidadesExcluidas,
            true
        );
    }

    private static function columnaExisteSigh(string $tabla, string $columna): bool
    {
        try {
            $stmt = self::dbSigh()->prepare("
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = :tabla
                  AND COLUMN_NAME = :columna
            ");

            $stmt->execute([
                ':tabla' => $tabla,
                ':columna' => $columna,
            ]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }


    private static function tipoColumnaSigh(string $tabla, string $columna): string
    {
        try {
            $stmt = self::dbSigh()->prepare("
                SELECT TOP 1 DATA_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = :tabla
                  AND COLUMN_NAME = :columna
            ");

            $stmt->execute([
                ':tabla' => $tabla,
                ':columna' => $columna,
            ]);

            return strtolower(trim((string) ($stmt->fetchColumn() ?: '')));
        } catch (Throwable) {
            return '';
        }
    }



    private static function primeraColumnaExistenteSigh(string $tabla, array $columnas): ?string
    {
        foreach ($columnas as $columna) {
            if (self::columnaExisteSigh($tabla, $columna)) {
                return $columna;
            }
        }

        return null;
    }

    private static function crearTablaEstadosDiarios(): void
    {
        self::dbCitas()->exec("
            CREATE TABLE IF NOT EXISTS citas_diarias_admin_estados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_programacion INT NOT NULL,
                fecha DATE NULL,
                estado VARCHAR(30) NOT NULL DEFAULT 'PROGRAMADO',
                observacion VARCHAR(255) NULL,
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_citas_diarias_programacion (id_programacion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private static function estadosDiariosPorProgramacion(array $rows): array
    {
        self::crearTablaEstadosDiarios();

        $ids = [];

        foreach ($rows as $row) {
            $id = (int) ($row['IdProgramacion'] ?? 0);

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = self::dbCitas()->prepare("
            SELECT
                id_programacion,
                fecha,
                estado,
                observacion
            FROM citas_diarias_admin_estados
            WHERE id_programacion IN ($placeholders)
        ");

        $stmt->execute(array_values($ids));

        $estados = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $idProgramacion = (int) ($row['id_programacion'] ?? 0);

            if ($idProgramacion > 0) {
                $estados[$idProgramacion] = [
                    'fecha' => self::valorFecha($row['fecha'] ?? ''),
                    'estado' => strtoupper(trim((string) ($row['estado'] ?? 'PROGRAMADO'))),
                    'observacion' => trim((string) ($row['observacion'] ?? '')),
                ];
            }
        }

        return $estados;
    }

    public static function registros(): void
    {
        try {
            self::requireAdmin();

            $stmt = self::dbCitas()->query("
                SELECT
                    IdRegistro AS idRegistro,
                    Ticket AS ticket,
                    NumHC AS historiaClinica,
                    Nombre AS nombre,
                    Apellido AS apellido,
                    Sexo AS sexo,
                    TipoDocumento AS tipoDocumento,
                    DocIden AS docIden,
                    Telefono AS telefono,
                    IdEspecialidad AS idEspecialidad,
                    Especialidad AS especialidad,
                    IdServicio AS idServicio,
                    Servicio AS servicio,
                    IdMedico AS idMedico,
                    Medico AS medico,
                    FechaRegistro AS fechaRegistro,
                    Estado AS estado
                FROM salaesperaregistros
                ORDER BY IdRegistro DESC
                LIMIT 1000
            ");

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response([
                'ok' => true,
                'success' => true,
                'total' => count($data),
                'data' => $data,
                'registros' => $data,
            ]);
        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'Error cargando registros de citas reservadas.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

public static function citasDiarias(): void
{
    try {
        self::requireAdmin();

        $fechaInicio = self::fechaInicio();
        $fechaFin = self::fechaFin();

        /*
           Consulta segura para SQL Server:
           - No repite el mismo placeholder varias veces.
           - Si las fechas vienen vacías, muestra programaciones anteriores y posteriores.
           - Si existe IdEstadoCita, separa Otorgadas vs Atendidas.
        */
        $tieneEstadoCita = self::columnaExisteSigh('Citas', 'IdEstadoCita');
        $estadoExpr = $tieneEstadoCita ? 'ISNULL(c.IdEstadoCita, 0)' : '0';

        $wherePm = [];
        $joinCitasFechas = [];
        $params = [];

        if ($fechaInicio !== '') {
            $wherePm[] = 'CAST(pm.Fecha AS date) >= CONVERT(date, :fechaInicioPm, 23)';
            $joinCitasFechas[] = 'CAST(c.Fecha AS date) >= CONVERT(date, :fechaInicioCita, 23)';
            $params[':fechaInicioPm'] = $fechaInicio;
            $params[':fechaInicioCita'] = $fechaInicio;
        }

        if ($fechaFin !== '') {
            $wherePm[] = 'CAST(pm.Fecha AS date) <= CONVERT(date, :fechaFinPm, 23)';
            $joinCitasFechas[] = 'CAST(c.Fecha AS date) <= CONVERT(date, :fechaFinCita, 23)';
            $params[':fechaFinPm'] = $fechaFin;
            $params[':fechaFinCita'] = $fechaFin;
        }

        $wherePmSql = $wherePm !== [] ? 'WHERE ' . implode(' AND ', $wherePm) : 'WHERE 1 = 1';
        $joinCitasFechaSql = $joinCitasFechas !== []
            ? "\n                AND " . implode("\n                AND ", $joinCitasFechas)
            : '';

        $sql = "
            SELECT TOP 1000
                pm.IdProgramacion,
                CAST(pm.Fecha AS date) AS Fecha,
                CAST(pm.Fecha AS date) AS FechaProgramacion,
                pm.IdDepartamento,
                LTRIM(RTRIM(ISNULL(dh.Nombre, ''))) AS Departamento,
                pm.IdEspecialidad,
                LTRIM(RTRIM(ISNULL(e.Nombre, ''))) AS Especialidad,
                pm.IdServicio,
                LTRIM(RTRIM(ISNULL(s.Nombre, ''))) AS Servicio,
                pm.IdMedico,
                UPPER(LTRIM(RTRIM(
                    ISNULL(emp.ApellidoPaterno, '') + ' ' +
                    ISNULL(emp.ApellidoMaterno, '') + ' ' +
                    ISNULL(emp.Nombres, '')
                ))) AS Medico,
                pm.HoraInicio,
                pm.HoraFin,
                pm.IdTurno,
                LTRIM(RTRIM(ISNULL(t.Descripcion, ''))) AS Turno,
                ISNULL(pm.TiempoPromedioAtencion, 0) AS TiempoPromedioAtencion,

                CASE
                    WHEN pm.HoraInicio IS NOT NULL
                     AND pm.HoraFin IS NOT NULL
                    THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time))
                    ELSE 0
                END AS MinutosProgramados,

                CASE
                    WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0
                     AND pm.HoraInicio IS NOT NULL
                     AND pm.HoraFin IS NOT NULL
                    THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion
                    ELSE 0
                END AS CuposProgramados,

                COUNT(DISTINCT CASE
                    WHEN ISNULL(c.EsCitaAdicional, 0) = 0
                     AND {$estadoExpr} NOT IN (2, 3) THEN c.IdCita
                END) AS CitasOtorgadas,

                COUNT(DISTINCT CASE
                    WHEN {$estadoExpr} = 2 THEN c.IdCita
                END) AS CitasAtendidas,

                COUNT(DISTINCT CASE
                    WHEN ISNULL(c.EsCitaAdicional, 0) = 1
                     AND {$estadoExpr} NOT IN (2, 3) THEN c.IdCita
                END) AS CitasAdicionales,

                COUNT(DISTINCT cb.IdCitaBloqueada) AS CitasBloqueadas,

                CASE
                    WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0
                     AND pm.HoraInicio IS NOT NULL
                     AND pm.HoraFin IS NOT NULL
                    THEN
                        (
                            DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time))
                            / pm.TiempoPromedioAtencion
                        )
                        - COUNT(DISTINCT CASE
                            WHEN ISNULL(c.EsCitaAdicional, 0) = 0
                             AND {$estadoExpr} <> 3 THEN c.IdCita
                          END)
                        - COUNT(DISTINCT cb.IdCitaBloqueada)
                    ELSE 0
                END AS CuposDisponibles

            FROM ProgramacionMedica pm

            LEFT JOIN Servicios s
                ON s.IdServicio = pm.IdServicio

            LEFT JOIN Especialidades e
                ON e.IdEspecialidad = pm.IdEspecialidad

            LEFT JOIN DepartamentosHospital dh
                ON dh.IdDepartamento = pm.IdDepartamento

            LEFT JOIN Turnos t
                ON t.IdTurno = pm.IdTurno

            LEFT JOIN Medicos m
                ON m.IdMedico = pm.IdMedico

            LEFT JOIN Empleados emp
                ON emp.IdEmpleado = m.IdEmpleado

            LEFT JOIN Citas c
                ON c.IdProgramacion = pm.IdProgramacion{$joinCitasFechaSql}

            LEFT JOIN CitasBloqueadas cb
                ON cb.IdMedico = pm.IdMedico
                AND CAST(cb.Fecha AS date) = CAST(pm.Fecha AS date)
                AND cb.HoraInicio >= pm.HoraInicio
                AND cb.HoraFin <= pm.HoraFin

            {$wherePmSql}

            GROUP BY
                pm.IdProgramacion,
                CAST(pm.Fecha AS date),
                pm.IdDepartamento,
                dh.Nombre,
                pm.IdEspecialidad,
                e.Nombre,
                pm.IdServicio,
                s.Nombre,
                pm.IdMedico,
                emp.ApellidoPaterno,
                emp.ApellidoMaterno,
                emp.Nombres,
                pm.HoraInicio,
                pm.HoraFin,
                pm.IdTurno,
                t.Descripcion,
                pm.TiempoPromedioAtencion

            ORDER BY
                CAST(pm.Fecha AS date) DESC,
                CASE
                    WHEN UPPER(LTRIM(RTRIM(ISNULL(t.Descripcion, '')))) LIKE '%MAÑ%' THEN 1
                    WHEN UPPER(LTRIM(RTRIM(ISNULL(t.Descripcion, '')))) LIKE '%MAN%' THEN 1
                    WHEN CAST(pm.HoraInicio AS time) < '12:00' THEN 1
                    WHEN UPPER(LTRIM(RTRIM(ISNULL(t.Descripcion, '')))) LIKE '%TAR%' THEN 2
                    WHEN CAST(pm.HoraInicio AS time) >= '12:00' THEN 2
                    ELSE 3
                END ASC,
                CAST(pm.HoraInicio AS time) ASC,
                e.Nombre ASC,
                s.Nombre ASC
        ";

        $stmt = self::dbSigh()->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $origen = 'programacion_medica';

        if ($rows === []) {
            $whereCitas = [];
            $paramsCitas = [];

            if ($fechaInicio !== '') {
                $whereCitas[] = 'CAST(c.Fecha AS date) >= CONVERT(date, :fechaInicioCitasDirectas, 23)';
                $paramsCitas[':fechaInicioCitasDirectas'] = $fechaInicio;
            }

            if ($fechaFin !== '') {
                $whereCitas[] = 'CAST(c.Fecha AS date) <= CONVERT(date, :fechaFinCitasDirectas, 23)';
                $paramsCitas[':fechaFinCitasDirectas'] = $fechaFin;
            }

            $whereCitasSql = $whereCitas !== [] ? 'WHERE ' . implode(' AND ', $whereCitas) : 'WHERE 1 = 1';

            $sqlCitas = "
                SELECT TOP 1000
                    ISNULL(pm.IdProgramacion, c.IdProgramacion) AS IdProgramacion,
                    CAST(c.Fecha AS date) AS Fecha,
                    CAST(ISNULL(pm.Fecha, c.Fecha) AS date) AS FechaProgramacion,
                    pm.IdDepartamento,
                    LTRIM(RTRIM(ISNULL(dh.Nombre, ''))) AS Departamento,
                    pm.IdEspecialidad,
                    LTRIM(RTRIM(ISNULL(e.Nombre, ''))) AS Especialidad,
                    pm.IdServicio,
                    LTRIM(RTRIM(ISNULL(s.Nombre, ''))) AS Servicio,
                    pm.IdMedico,
                    UPPER(LTRIM(RTRIM(
                        ISNULL(emp.ApellidoPaterno, '') + ' ' +
                        ISNULL(emp.ApellidoMaterno, '') + ' ' +
                        ISNULL(emp.Nombres, '')
                    ))) AS Medico,
                    pm.HoraInicio,
                    pm.HoraFin,
                    pm.IdTurno,
                    LTRIM(RTRIM(ISNULL(t.Descripcion, ''))) AS Turno,
                    ISNULL(pm.TiempoPromedioAtencion, 0) AS TiempoPromedioAtencion,

                    CASE
                        WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0
                         AND pm.HoraInicio IS NOT NULL
                         AND pm.HoraFin IS NOT NULL
                        THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time))
                        ELSE 0
                    END AS MinutosProgramados,

                    CASE
                        WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0
                         AND pm.HoraInicio IS NOT NULL
                         AND pm.HoraFin IS NOT NULL
                        THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion
                        ELSE COUNT(DISTINCT c.IdCita)
                    END AS CuposProgramados,

                    COUNT(DISTINCT CASE
                        WHEN ISNULL(c.EsCitaAdicional, 0) = 0
                         AND {$estadoExpr} NOT IN (2, 3) THEN c.IdCita
                    END) AS CitasOtorgadas,

                    COUNT(DISTINCT CASE
                        WHEN {$estadoExpr} = 2 THEN c.IdCita
                    END) AS CitasAtendidas,

                    COUNT(DISTINCT CASE
                        WHEN ISNULL(c.EsCitaAdicional, 0) = 1
                         AND {$estadoExpr} NOT IN (2, 3) THEN c.IdCita
                    END) AS CitasAdicionales,

                    0 AS CitasBloqueadas,

                    CASE
                        WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0
                         AND pm.HoraInicio IS NOT NULL
                         AND pm.HoraFin IS NOT NULL
                        THEN
                            (
                                DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time))
                                / pm.TiempoPromedioAtencion
                            )
                            - COUNT(DISTINCT CASE
                                WHEN ISNULL(c.EsCitaAdicional, 0) = 0
                                 AND {$estadoExpr} <> 3 THEN c.IdCita
                              END)
                        ELSE 0
                    END AS CuposDisponibles

                FROM Citas c

                LEFT JOIN ProgramacionMedica pm
                    ON pm.IdProgramacion = c.IdProgramacion

                LEFT JOIN Servicios s
                    ON s.IdServicio = pm.IdServicio

                LEFT JOIN Especialidades e
                    ON e.IdEspecialidad = pm.IdEspecialidad

                LEFT JOIN DepartamentosHospital dh
                    ON dh.IdDepartamento = pm.IdDepartamento

                LEFT JOIN Turnos t
                    ON t.IdTurno = pm.IdTurno

                LEFT JOIN Medicos m
                    ON m.IdMedico = pm.IdMedico

                LEFT JOIN Empleados emp
                    ON emp.IdEmpleado = m.IdEmpleado

                {$whereCitasSql}

                GROUP BY
                    ISNULL(pm.IdProgramacion, c.IdProgramacion),
                    CAST(c.Fecha AS date),
                    CAST(ISNULL(pm.Fecha, c.Fecha) AS date),
                    pm.IdDepartamento,
                    dh.Nombre,
                    pm.IdEspecialidad,
                    e.Nombre,
                    pm.IdServicio,
                    s.Nombre,
                    pm.IdMedico,
                    emp.ApellidoPaterno,
                    emp.ApellidoMaterno,
                    emp.Nombres,
                    pm.HoraInicio,
                    pm.HoraFin,
                    pm.IdTurno,
                    t.Descripcion,
                    pm.TiempoPromedioAtencion

                ORDER BY
                    CAST(c.Fecha AS date) DESC,
                    CAST(pm.HoraInicio AS time) ASC,
                    e.Nombre ASC,
                    s.Nombre ASC
            ";

            $stmtCitas = self::dbSigh()->prepare($sqlCitas);
            $stmtCitas->execute($paramsCitas);

            $rows = $stmtCitas->fetchAll(PDO::FETCH_ASSOC);
            $origen = 'citas_directas';
        }

        $estadosGuardados = [];

        $data = array_map(static function (array $row) use ($estadosGuardados): array {
            $idProgramacion = (int) ($row['IdProgramacion'] ?? 0);
            $estadoGuardado = $estadosGuardados[$idProgramacion] ?? null;

            return [
                'idProgramacion' => $idProgramacion,
                'ticket' => 'PROG-' . $idProgramacion,
                'fecha' => CitasAdminController::valorFecha($row['Fecha'] ?? ''),
                'idDepartamento' => $row['IdDepartamento'] ?? null,
                'fechaProgramacion' => CitasAdminController::valorFecha($row['FechaProgramacion'] ?? ''),
                'departamento' => CitasAdminController::normalizarTexto($row['Departamento'] ?? ''),
                'idEspecialidad' => $row['IdEspecialidad'] ?? null,
                'especialidad' => CitasAdminController::normalizarTexto($row['Especialidad'] ?? ''),
                'idServicio' => $row['IdServicio'] ?? null,
                'servicio' => CitasAdminController::normalizarTexto($row['Servicio'] ?? ''),
                'idMedico' => $row['IdMedico'] ?? null,
                'medico' => CitasAdminController::normalizarTexto($row['Medico'] ?? ''),
                'horaInicio' => CitasAdminController::valorHora($row['HoraInicio'] ?? ''),
                'horaFin' => CitasAdminController::valorHora($row['HoraFin'] ?? ''),
                'idTurno' => $row['IdTurno'] ?? null,
                'turno' => CitasAdminController::normalizarTexto($row['Turno'] ?? ''),
                'tiempoPromedioAtencion' => (int) ($row['TiempoPromedioAtencion'] ?? 0),
                'minutosProgramados' => (int) ($row['MinutosProgramados'] ?? 0),
                'cuposProgramados' => max(0, (int) ($row['CuposProgramados'] ?? 0)),
                'citasOtorgadas' => max(0, (int) ($row['CitasOtorgadas'] ?? 0)),
                'citasAtendidas' => max(0, (int) ($row['CitasAtendidas'] ?? 0)),
                'citasAdicionales' => max(0, (int) ($row['CitasAdicionales'] ?? 0)),
                'citasBloqueadas' => max(0, (int) ($row['CitasBloqueadas'] ?? 0)),
                'cuposDisponibles' => max(0, (int) ($row['CuposDisponibles'] ?? 0)),
                'estado' => $estadoGuardado['estado'] ?? 'PROGRAMADO',
                'observacion' => $estadoGuardado['observacion'] ?? '',
            ];
        }, $rows);

        json_response([
            'ok' => true,
            'success' => true,
            'total' => count($data),
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'origen' => $origen,
            'data' => $data,
        ]);
    } catch (Throwable $e) {
        json_response([
            'ok' => false,
            'success' => false,
            'error' => 'Error cargando citas diarias desde SIGH.',
            'debug' => $e->getMessage(),
        ], 500);
    }
}


    public static function reportes(): void
    {
        $nivelBuffer = ob_get_level();
        ob_start();

        try {
            set_time_limit(120);
            self::requireAdmin();

            $mesReporte = trim((string) ($_GET['mes'] ?? ''));

            if (!preg_match('/^\d{4}-\d{2}$/', $mesReporte)) {
                $mesReporte = date('Y-m');
            }

            $inicioMes = DateTimeImmutable::createFromFormat('!Y-m', $mesReporte);

            if (!$inicioMes || $inicioMes->format('Y-m') !== $mesReporte) {
                $inicioMes = new DateTimeImmutable(date('Y-m-01'));
                $mesReporte = $inicioMes->format('Y-m');
            }

            $fechaInicio = $inicioMes->format('Y-m-01');
            $fechaFin = $inicioMes
                ->modify('last day of this month')
                ->format('Y-m-d');

            $fechaInicioSql = $inicioMes->format('Ymd');
            $fechaFinExclusivaSql = $inicioMes
                ->modify('first day of next month')
                ->format('Ymd');

            /*
               ESTADOS REALES DE SIGH

               Citas.IdAtencion -> Atenciones.IdAtencion

               Atenciones.idEstadoAtencion:
               0 = Anulado
               1 = Registrado
               2 = Cerrado

               El reporte no interpreta inasistencias, asistencias válidas
               ni pendientes. Solo presenta los estados almacenados.
            */
            $sql = "
                ;WITH CitasMes AS (
                    SELECT
                        c.IdCita,
                        c.IdEspecialidad,
                        c.IdServicio,
                        c.IdMedico,
                        c.IdAtencion,
                        a.idEstadoAtencion
                    FROM Citas c
                    INNER JOIN Atenciones a
                        ON a.IdAtencion = c.IdAtencion
                    WHERE c.Fecha >= CONVERT(
                              datetime,
                              :fechaInicioCitasReporte,
                              112
                          )
                      AND c.Fecha < CONVERT(
                              datetime,
                              :fechaFinExclusivaCitasReporte,
                              112
                          )
                      AND a.idEstadoAtencion IN (0, 1, 2)
                )

                SELECT TOP 5000
                    cm.IdEspecialidad,
                    LTRIM(RTRIM(ISNULL(e.Nombre, ''))) AS Especialidad,
                    cm.IdServicio,
                    LTRIM(RTRIM(ISNULL(s.Nombre, ''))) AS Servicio,
                    cm.IdMedico,
                    UPPER(
                        LTRIM(
                            RTRIM(
                                ISNULL(emp.ApellidoPaterno, '') + ' ' +
                                ISNULL(emp.ApellidoMaterno, '') + ' ' +
                                ISNULL(emp.Nombres, '')
                            )
                        )
                    ) AS Medico,

                    COUNT(cm.IdCita) AS TotalCitas,

                    SUM(
                        CASE
                            WHEN cm.idEstadoAtencion = 0 THEN 1
                            ELSE 0
                        END
                    ) AS Anulados,

                    SUM(
                        CASE
                            WHEN cm.idEstadoAtencion = 1 THEN 1
                            ELSE 0
                        END
                    ) AS Registrados,

                    SUM(
                        CASE
                            WHEN cm.idEstadoAtencion = 2 THEN 1
                            ELSE 0
                        END
                    ) AS Cerrados

                FROM CitasMes cm

                LEFT JOIN Especialidades e
                    ON e.IdEspecialidad = cm.IdEspecialidad

                LEFT JOIN Servicios s
                    ON s.IdServicio = cm.IdServicio

                LEFT JOIN Medicos m
                    ON m.IdMedico = cm.IdMedico

                LEFT JOIN Empleados emp
                    ON emp.IdEmpleado = m.IdEmpleado

                GROUP BY
                    cm.IdEspecialidad,
                    e.Nombre,
                    cm.IdServicio,
                    s.Nombre,
                    cm.IdMedico,
                    emp.ApellidoPaterno,
                    emp.ApellidoMaterno,
                    emp.Nombres

                ORDER BY
                    Especialidad ASC,
                    Servicio ASC,
                    Medico ASC

                OPTION (RECOMPILE)
            ";

            $stmt = self::dbSigh()->prepare($sql);
            $stmt->execute([
                ':fechaInicioCitasReporte' => $fechaInicioSql,
                ':fechaFinExclusivaCitasReporte' => $fechaFinExclusivaSql,
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $especialidades = [];

            foreach ($rows as $row) {
                $idEspecialidad = (int) ($row['IdEspecialidad'] ?? 0);
                $especialidad = self::normalizarTexto($row['Especialidad'] ?? '');
                $idServicio = (int) ($row['IdServicio'] ?? 0);
                $servicio = self::normalizarTexto($row['Servicio'] ?? '');
                $idMedico = (int) ($row['IdMedico'] ?? 0);
                $medico = self::normalizarTexto($row['Medico'] ?? '');

            if ($especialidad === '') {
                $especialidad = $idEspecialidad > 0
                    ? 'Especialidad ' . $idEspecialidad
                    : 'Especialidad sin nombre';
            }

            /*
            * Estas especialidades no deben aparecer en el ranking,
            * en el detalle ni en el total del reporte.
            */
            if (self::especialidadExcluidaReporte($especialidad)) {
                continue;
            }
                            if ($servicio === '') {
                    $servicio = $idServicio > 0
                        ? 'Consultorio ' . $idServicio
                        : 'Consultorio sin nombre';
                }

                if ($medico === '') {
                    $medico = $idMedico > 0
                        ? 'Personal ' . $idMedico
                        : 'Personal no identificado';
                }

                $totalCitas = max(0, (int) ($row['TotalCitas'] ?? 0));
                $anulados = max(0, (int) ($row['Anulados'] ?? 0));
                $registrados = max(0, (int) ($row['Registrados'] ?? 0));
                $cerrados = max(0, (int) ($row['Cerrados'] ?? 0));

                $claveEspecialidad = $idEspecialidad > 0
                    ? 'id:' . $idEspecialidad
                    : 'nombre:' . strtolower($especialidad);

                $claveServicio = $idServicio > 0
                    ? 'id:' . $idServicio
                    : 'nombre:' . strtolower($servicio);

                $claveMedico = $idMedico > 0
                    ? 'id:' . $idMedico
                    : 'nombre:' . strtolower($medico);

                if (!isset($especialidades[$claveEspecialidad])) {
                    $especialidades[$claveEspecialidad] = [
                        'idEspecialidad' => $idEspecialidad > 0
                            ? $idEspecialidad
                            : null,
                        'especialidad' => $especialidad,
                        'totalCitas' => 0,
                        'anulados' => 0,
                        'registrados' => 0,
                        'cerrados' => 0,
                        'totalConsultorios' => 0,
                        'totalPersonal' => 0,
                        'consultorios' => [],
                    ];
                }

                $especialidades[$claveEspecialidad]['totalCitas'] += $totalCitas;
                $especialidades[$claveEspecialidad]['anulados'] += $anulados;
                $especialidades[$claveEspecialidad]['registrados'] += $registrados;
                $especialidades[$claveEspecialidad]['cerrados'] += $cerrados;

                if (!isset($especialidades[$claveEspecialidad]['consultorios'][$claveServicio])) {
                    $especialidades[$claveEspecialidad]['consultorios'][$claveServicio] = [
                        'idServicio' => $idServicio > 0 ? $idServicio : null,
                        'servicio' => $servicio,
                        'totalCitas' => 0,
                        'anulados' => 0,
                        'registrados' => 0,
                        'cerrados' => 0,
                        'totalPersonal' => 0,
                        'nombresPersonal' => [],
                        'personal' => [],
                    ];
                }

                $consultorio =& $especialidades[$claveEspecialidad]['consultorios'][$claveServicio];
                $consultorio['totalCitas'] += $totalCitas;
                $consultorio['anulados'] += $anulados;
                $consultorio['registrados'] += $registrados;
                $consultorio['cerrados'] += $cerrados;

                if (!isset($consultorio['personal'][$claveMedico])) {
                    $consultorio['personal'][$claveMedico] = [
                        'idMedico' => $idMedico > 0 ? $idMedico : null,
                        'medico' => $medico,
                        'totalCitas' => 0,
                        'anulados' => 0,
                        'registrados' => 0,
                        'cerrados' => 0,
                    ];
                }

                $consultorio['personal'][$claveMedico]['totalCitas'] += $totalCitas;
                $consultorio['personal'][$claveMedico]['anulados'] += $anulados;
                $consultorio['personal'][$claveMedico]['registrados'] += $registrados;
                $consultorio['personal'][$claveMedico]['cerrados'] += $cerrados;
                unset($consultorio);
            }

            $data = [];

            foreach ($especialidades as $especialidad) {
                $consultorios = [];

                foreach ($especialidad['consultorios'] as $consultorio) {
                    if ((int) ($consultorio['totalCitas'] ?? 0) <= 0) {
                        continue;
                    }

                    $personal = array_values($consultorio['personal']);

                    usort($personal, static fn(array $a, array $b): int =>
                        ($b['totalCitas'] <=> $a['totalCitas'])
                        ?: strcmp($a['medico'], $b['medico'])
                    );

                    $consultorio['personal'] = $personal;
                    $consultorio['totalPersonal'] = count($personal);
                    $consultorio['nombresPersonal'] = array_values(array_map(
                        static fn(array $persona): string =>
                            (string) ($persona['medico'] ?? 'Personal no identificado'),
                        $personal
                    ));
                    $consultorios[] = $consultorio;
                }

                usort($consultorios, static fn(array $a, array $b): int =>
                    ($b['totalCitas'] <=> $a['totalCitas'])
                    ?: strcmp($a['servicio'], $b['servicio'])
                );

                $especialidad['consultorios'] = $consultorios;
                $especialidad['totalConsultorios'] = count($consultorios);

                $personalUnico = [];

                foreach ($consultorios as $consultorioFinal) {
                    foreach (($consultorioFinal['personal'] ?? []) as $personaFinal) {
                        $idPersona = (int) ($personaFinal['idMedico'] ?? 0);
                        $nombrePersona = strtolower(trim((string) ($personaFinal['medico'] ?? '')));
                        $clavePersona = $idPersona > 0
                            ? 'id:' . $idPersona
                            : 'nombre:' . $nombrePersona;

                        if ($clavePersona !== 'nombre:') {
                            $personalUnico[$clavePersona] = true;
                        }
                    }
                }

                $especialidad['totalPersonal'] = count($personalUnico);

                if ((int) ($especialidad['totalCitas'] ?? 0) > 0) {
                    $data[] = $especialidad;
                }
            }

            usort($data, static fn(array $a, array $b): int =>
                ($b['totalCitas'] <=> $a['totalCitas'])
                ?: strcmp($a['especialidad'], $b['especialidad'])
            );

            while (ob_get_level() > $nivelBuffer) {
                ob_end_clean();
            }

            json_response([
                'ok' => true,
                'success' => true,
                'mes' => $mesReporte,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'totalEspecialidades' => count($data),
                'especialidadesRanking' => $data,
                'detalleEspecialidades' => $data,
            ]);
        } catch (Throwable $e) {
            while (ob_get_level() > $nivelBuffer) {
                ob_end_clean();
            }

            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'Error generando los reportes de citas diarias.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public static function actualizarEstado(int $id): void
    {
        try {
            self::requireAdmin();

            $input = get_json_input();
            $estado = strtoupper(trim((string) ($input['estado'] ?? '')));
            $permitidos = ['REGISTRADO', 'ATENDIDO', 'ANULADO'];

            if (!in_array($estado, $permitidos, true)) {
                json_response([
                    'ok' => false,
                    'success' => false,
                    'error' => 'Estado inválido.',
                ], 400);
            }

            $stmt = self::dbCitas()->prepare("
                UPDATE salaesperaregistros
                SET Estado = :estado
                WHERE IdRegistro = :id
            ");

            $stmt->execute([
                ':estado' => $estado,
                ':id' => $id,
            ]);

            json_response([
                'ok' => true,
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
            ]);
        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'Error actualizando estado.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public static function actualizarEstadoCitaDiaria(int $idProgramacion): void
    {
        try {
            self::requireAdmin();
            self::crearTablaEstadosDiarios();

            $input = get_json_input();
            $estado = strtoupper(trim((string) ($input['estado'] ?? '')));
            $fecha = trim((string) ($input['fecha'] ?? date('Y-m-d')));
            $observacion = trim((string) ($input['observacion'] ?? ''));

            $permitidos = [
                'PROGRAMADO',
                'ATENDIDO',
                'ANULADO',
                'CITA_ADICIONAL',
            ];

            if ($idProgramacion <= 0) {
                json_response([
                    'ok' => false,
                    'success' => false,
                    'error' => 'Programación inválida.',
                ], 400);
            }

            if (!in_array($estado, $permitidos, true)) {
                json_response([
                    'ok' => false,
                    'success' => false,
                    'error' => 'Estado inválido para cita diaria.',
                ], 400);
            }

            $stmt = self::dbCitas()->prepare("
                INSERT INTO citas_diarias_admin_estados
                    (id_programacion, fecha, estado, observacion)
                VALUES
                    (:id_programacion, :fecha, :estado, :observacion)
                ON DUPLICATE KEY UPDATE
                    fecha = VALUES(fecha),
                    estado = VALUES(estado),
                    observacion = VALUES(observacion),
                    actualizado_en = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                ':id_programacion' => $idProgramacion,
                ':fecha' => $fecha !== '' ? $fecha : null,
                ':estado' => $estado,
                ':observacion' => $observacion !== '' ? $observacion : null,
            ]);

            json_response([
                'ok' => true,
                'success' => true,
                'message' => 'Estado de cita diaria actualizado correctamente.',
            ]);
        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'No se pudo guardar el estado porque MySQL citas no respondió.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public static function pacientesCitaDiaria(int $idProgramacion): void
    {
        try {
            self::requireAdmin();

            if ($idProgramacion <= 0) {
                json_response([
                    'ok' => false,
                    'success' => false,
                    'error' => 'Programación inválida.',
                ], 400);
            }

            $columnaCuenta = 'c.IdCita';

            foreach (['IdCuentaAtencion', 'NroCuenta', 'NumeroCuenta', 'NroCuentaAtencion'] as $columna) {
                if (self::columnaExisteSigh('Citas', $columna)) {
                    $columnaCuenta = 'c.' . $columna;
                    break;
                }
            }

            $columnaFechaRegistro = self::primeraColumnaExistenteSigh('Citas', [
                'FechaRegistro',
                'FechaCreacion',
                'FechaSolicitud',
                'FechaRegistroCita',
                'FechaHoraRegistro',
                'FechaIngreso',
            ]);

            $columnaFechaAtencion = self::primeraColumnaExistenteSigh('Citas', [
                'FechaAtencion',
                'FechaHoraAtencion',
                'FechaAtendido',
                'FechaActualizacion',
                'FechaModificacion',
            ]);

            $selectFechaRegistro = $columnaFechaRegistro
                ? 'c.' . $columnaFechaRegistro . ' AS FechaRegistroCita'
                : 'NULL AS FechaRegistroCita';

            $selectFechaAtencion = $columnaFechaAtencion
                ? 'c.' . $columnaFechaAtencion . ' AS FechaAtencionCita'
                : 'NULL AS FechaAtencionCita';

            $selectEstadoCita = self::columnaExisteSigh('Citas', 'IdEstadoCita')
                ? 'c.IdEstadoCita AS IdEstadoCita'
                : '1 AS IdEstadoCita';

            $sql = "
                SELECT TOP 500
                    c.IdCita,
                    c.IdProgramacion,
                    c.IdPaciente,
                    c.Fecha,
                    {$selectFechaRegistro},
                    {$selectFechaAtencion},
                    c.HoraInicio,
                    c.HoraFin,
                    {$selectEstadoCita},
                    ISNULL(c.EsCitaAdicional, 0) AS EsCitaAdicional,
                    {$columnaCuenta} AS NumeroCuenta,
                    p.NroHistoriaClinica,
                    p.NroDocumento,
                    p.ApellidoPaterno,
                    p.ApellidoMaterno,
                    p.PrimerNombre,
                    p.SegundoNombre,
                    p.TercerNombre,
                    p.FechaNacimiento,
                    p.IdTipoSexo
                FROM Citas c
                LEFT JOIN Pacientes p ON p.IdPaciente = c.IdPaciente
                WHERE c.IdProgramacion = :idProgramacion
                ORDER BY c.HoraInicio ASC, c.IdCita ASC
            ";

            $stmt = self::dbSigh()->prepare($sql);

            $stmt->execute([
                ':idProgramacion' => $idProgramacion,
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $hoy = date('Y-m-d');

            $data = array_map(static function (array $row) use ($hoy): array {
                $paciente = trim(
                    ($row['ApellidoPaterno'] ?? '') . ' ' .
                    ($row['ApellidoMaterno'] ?? '') . ' ' .
                    ($row['PrimerNombre'] ?? '') . ' ' .
                    ($row['SegundoNombre'] ?? '') . ' ' .
                    ($row['TercerNombre'] ?? '')
                );

                $idTipoSexo = (string) ($row['IdTipoSexo'] ?? '');

                $sexo = match ($idTipoSexo) {
                    '1' => 'MASCULINO',
                    '2' => 'FEMENINO',
                    default => '',
                };

                $estadoCita = match ((string) ($row['IdEstadoCita'] ?? '')) {
                    '1' => 'SEPARADO',
                    '2' => 'ATENDIDO',
                    '3' => 'ANULADO',
                    default => 'SEPARADO',
                };

                $fechaCita = CitasAdminController::valorFecha($row['Fecha'] ?? '');
                $esCitaAdicional = (int) ($row['EsCitaAdicional'] ?? 0) === 1;
                $colorCita = $esCitaAdicional ? 'adicional' : 'programada';

                if (!$esCitaAdicional && $fechaCita !== '') {
                    if ($fechaCita < $hoy) {
                        $colorCita = 'anterior';
                    } elseif ($fechaCita === $hoy) {
                        $colorCita = 'hoy';
                    }
                }

                return [
                    'idCita' => (int) ($row['IdCita'] ?? 0),
                    'idProgramacion' => (int) ($row['IdProgramacion'] ?? 0),
                    'idPaciente' => (int) ($row['IdPaciente'] ?? 0),
                    'numeroCuenta' => trim((string) ($row['NumeroCuenta'] ?? '')),
                    'tipoSeguro' => 'SIS',
                    'historiaClinica' => trim((string) ($row['NroHistoriaClinica'] ?? '')),
                    'documento' => trim((string) ($row['NroDocumento'] ?? '')),
                    'paciente' => mb_strtoupper($paciente, 'UTF-8'),
                    'sexo' => $sexo,
                    'fechaNacimiento' => CitasAdminController::valorFecha($row['FechaNacimiento'] ?? ''),
                    'fechaCita' => $fechaCita,
                    'fechaRegistro' => CitasAdminController::valorFecha($row['FechaRegistroCita'] ?? ''),
                    'horaRegistro' => CitasAdminController::valorHora($row['FechaRegistroCita'] ?? ''),
                    'fechaAtencion' => CitasAdminController::valorFecha($row['FechaAtencionCita'] ?? ''),
                    'horaAtencion' => CitasAdminController::valorHora($row['FechaAtencionCita'] ?? ''),
                    'horaInicio' => CitasAdminController::valorHora($row['HoraInicio'] ?? ''),
                    'horaFin' => CitasAdminController::valorHora($row['HoraFin'] ?? ''),
                    'idEstadoCita' => $row['IdEstadoCita'] ?? null,
                    'estadoCita' => $estadoCita,
                    'esCitaAdicional' => $esCitaAdicional,
                    'tipoCita' => $esCitaAdicional ? 'ADICIONAL' : 'NORMAL',
                    'colorCita' => $colorCita,
                ];
            }, $rows);

            json_response([
                'ok' => true,
                'success' => true,
                'total' => count($data),
                'idProgramacion' => $idProgramacion,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'Error cargando pacientes de la cita diaria desde SIGH.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public static function actualizarEstadoPacienteCita(string $idCita): void
    {
        try {
            self::requireAdmin();

            $idCita = trim($idCita);

            if ($idCita === '' || !ctype_digit($idCita)) {
                json_response([
                    'ok' => false,
                    'success' => false,
                    'error' => 'No se encontró el identificador de la cita.',
                ], 400);
            }

            $input = get_json_input();
            $estado = strtoupper(trim((string) ($input['estado'] ?? '')));

            $idEstado = match ($estado) {
                'ATENDIDO' => 2,
                'ANULADO' => 3,
                'SEPARADO', 'PENDIENTE', 'PENDIENTE_ATENCION' => 1,
                default => 0,
            };

            if ($idEstado === 0) {
                json_response([
                    'ok' => false,
                    'success' => false,
                    'error' => 'Estado inválido para la cita del paciente.',
                ], 400);
            }

            $sets = ['IdEstadoCita = :idEstado'];
            $params = [
                ':idEstado' => $idEstado,
                ':idCita' => (int) $idCita,
            ];

            if ($estado === 'ATENDIDO') {
                $columnaAtencion = self::primeraColumnaExistenteSigh('Citas', [
                    'FechaAtencion',
                    'FechaHoraAtencion',
                    'FechaAtendido',
                    'FechaActualizacion',
                    'FechaModificacion',
                ]);

                if ($columnaAtencion !== null) {
                    $sets[] = $columnaAtencion . ' = GETDATE()';
                }
            }

            $sql = 'UPDATE Citas SET ' . implode(', ', $sets) . ' WHERE IdCita = :idCita';
            $stmt = self::dbSigh()->prepare($sql);
            $stmt->execute($params);

            json_response([
                'ok' => true,
                'success' => true,
                'message' => 'Estado de la cita del paciente actualizado correctamente.',
            ]);
        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'success' => false,
                'error' => 'No se pudo actualizar el estado de la cita del paciente.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

}