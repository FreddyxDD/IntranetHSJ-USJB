<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\AppointmentAudit;
use App\Models\AppointmentProgramState;
use App\Support\UserFacingError;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final class AppointmentApiController extends Controller
{
    private const PROGRAM_STATES = ['PROGRAMADO', 'EN_PROCESO', 'CERRADO', 'CANCELADO'];

    public function daily(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'fechaInicio' => ['nullable', 'date_format:Y-m-d'],
            'fechaFin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fechaInicio'],
        ]);

        $from = $filters['fechaInicio'] ?? now()->toDateString();
        $to = $filters['fechaFin'] ?? $from;

        try {
            $rows = DB::connection('sigh')->select($this->dailyQuery(), [$from, $to, $from, $to]);

            $states = AppointmentProgramState::query()
                ->whereIn('programacion_id', array_map(fn (object $row): int => (int) $row->IdProgramacion, $rows))
                ->get()
                ->keyBy('programacion_id');

            $data = array_map(fn (object $row): array => [
                'idProgramacion' => (int) $row->IdProgramacion,
                'ticket' => 'PROG-'.(int) $row->IdProgramacion,
                'fecha' => $this->date($row->Fecha),
                'fechaProgramacion' => $this->date($row->Fecha),
                'idDepartamento' => $row->IdDepartamento,
                'departamento' => trim((string) $row->Departamento),
                'idEspecialidad' => $row->IdEspecialidad,
                'especialidad' => trim((string) $row->Especialidad),
                'idServicio' => $row->IdServicio,
                'servicio' => trim((string) $row->Servicio),
                'idMedico' => $row->IdMedico,
                'medico' => trim((string) $row->Medico),
                'horaInicio' => $this->time($row->HoraInicio),
                'horaFin' => $this->time($row->HoraFin),
                'idTurno' => $row->IdTurno,
                'turno' => trim((string) $row->Turno),
                'tiempoPromedioAtencion' => (int) $row->TiempoPromedioAtencion,
                'minutosProgramados' => (int) $row->MinutosProgramados,
                'cuposProgramados' => max(0, (int) $row->CuposProgramados),
                'citasOtorgadas' => max(0, (int) $row->CitasOtorgadas),
                'citasAtendidas' => max(0, (int) $row->CitasAtendidas),
                'citasAdicionales' => max(0, (int) $row->CitasAdicionales),
                'citasBloqueadas' => max(0, (int) $row->CitasBloqueadas),
                'cuposDisponibles' => max(0, (int) $row->CuposDisponibles),
                'estado' => $states->get((int) $row->IdProgramacion)?->estado ?? 'PROGRAMADO',
                'observacion' => $states->get((int) $row->IdProgramacion)?->observacion ?? '',
            ], $rows);

            return response()->json([
                'ok' => true,
                'success' => true,
                'total' => count($data),
                'fechaInicio' => $from,
                'fechaFin' => $to,
                'origen' => 'SIGH.dbo.Citas',
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            $reference = UserFacingError::report($exception, 'INTRA-CITAS', [
                'operation' => 'daily',
                'from' => $from,
                'to' => $to,
            ]);

            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'El servicio de citas no está disponible temporalmente. Intenta nuevamente en unos minutos.',
                'reference' => $reference,
            ], 503);
        }
    }

    public function updateProgramState(Request $request, int $programacion): JsonResponse
    {
        $data = $request->validate([
            'estado' => ['required', 'in:'.implode(',', self::PROGRAM_STATES)],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        $program = DB::connection('sigh')->selectOne(
            'SELECT IdProgramacion, CAST(Fecha AS date) AS Fecha FROM dbo.ProgramacionMedica WHERE IdProgramacion = ?',
            [$programacion],
        );

        if (! $program) {
            return response()->json(['ok' => false, 'success' => false, 'error' => 'Programación no encontrada.'], 404);
        }

        $previous = AppointmentProgramState::query()->where('programacion_id', $programacion)->first();
        $state = AppointmentProgramState::query()->updateOrCreate(
            ['programacion_id' => $programacion],
            [
                'fecha' => $data['fecha'] ?? $this->date($program->Fecha),
                'estado' => $data['estado'],
                'observacion' => $data['observacion'] ?? null,
                'updated_by' => (int) ($_SESSION['ueei_id'] ?? 0),
            ],
        );

        AppointmentAudit::query()->create([
            'user_id' => (int) ($_SESSION['ueei_id'] ?? 0) ?: null,
            'action' => 'update',
            'entity_type' => 'medical_program',
            'entity_id' => (string) $programacion,
            'old_values' => $previous?->only(['estado', 'observacion']),
            'new_values' => $state->only(['estado', 'observacion']),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'success' => true,
            'message' => 'Estado de la programación actualizado correctamente.',
        ]);
    }

    public function patients(int $programacion): JsonResponse
    {
        try {
            $rows = DB::connection('sigh')->select(<<<'SQL'
                SELECT TOP 500
                    c.IdCita, c.IdProgramacion, c.IdPaciente, c.Fecha,
                    c.FechaSolicitud, c.HoraSolicitud, c.HoraInicio, c.HoraFin,
                    c.IdEstadoCita, ISNULL(c.EsCitaAdicional, 0) AS EsCitaAdicional,
                    ISNULL(c.IdAtencion, c.IdCita) AS NumeroCuenta,
                    p.NroHistoriaClinica, p.NroDocumento, p.ApellidoPaterno,
                    p.ApellidoMaterno, p.PrimerNombre, p.SegundoNombre,
                    p.TercerNombre, p.FechaNacimiento, p.IdTipoSexo
                FROM dbo.Citas c
                LEFT JOIN dbo.Pacientes p ON p.IdPaciente = c.IdPaciente
                WHERE c.IdProgramacion = ?
                ORDER BY c.HoraInicio, c.IdCita
                SQL, [$programacion]);

            $today = now()->toDateString();
            $data = array_map(function (object $row) use ($today): array {
                $appointmentDate = $this->date($row->Fecha);
                $additional = (bool) $row->EsCitaAdicional;
                $color = $additional ? 'adicional' : ($appointmentDate < $today ? 'anterior' : ($appointmentDate === $today ? 'hoy' : 'programada'));
                $patient = trim(implode(' ', array_filter([
                    $row->ApellidoPaterno, $row->ApellidoMaterno, $row->PrimerNombre,
                    $row->SegundoNombre, $row->TercerNombre,
                ])));

                return [
                    'idCita' => (int) $row->IdCita,
                    'idProgramacion' => (int) $row->IdProgramacion,
                    'idPaciente' => (int) $row->IdPaciente,
                    'numeroCuenta' => trim((string) $row->NumeroCuenta),
                    'tipoSeguro' => 'SIS',
                    'historiaClinica' => trim((string) $row->NroHistoriaClinica),
                    'documento' => trim((string) $row->NroDocumento),
                    'paciente' => mb_strtoupper($patient, 'UTF-8'),
                    'sexo' => match ((int) $row->IdTipoSexo) {
                        1 => 'MASCULINO', 2 => 'FEMENINO', default => ''
                    },
                    'fechaNacimiento' => $this->date($row->FechaNacimiento),
                    'fechaCita' => $appointmentDate,
                    'fechaRegistro' => $this->date($row->FechaSolicitud),
                    'horaRegistro' => $this->time($row->HoraSolicitud),
                    'fechaAtencion' => '',
                    'horaAtencion' => '',
                    'horaInicio' => $this->time($row->HoraInicio),
                    'horaFin' => $this->time($row->HoraFin),
                    'idEstadoCita' => $row->IdEstadoCita,
                    'estadoCita' => match ((int) $row->IdEstadoCita) {
                        1 => 'SEPARADO', 2 => 'ATENDIDO', 3 => 'ANULADO', default => 'SEPARADO'
                    },
                    'esCitaAdicional' => $additional,
                    'tipoCita' => $additional ? 'ADICIONAL' : 'NORMAL',
                    'colorCita' => $color,
                ];
            }, $rows);

            return response()->json([
                'ok' => true,
                'success' => true,
                'total' => count($data),
                'idProgramacion' => $programacion,
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            $reference = UserFacingError::report($exception, 'INTRA-CITAS', [
                'operation' => 'patients',
                'programacion_id' => $programacion,
            ]);

            return response()->json([
                'ok' => false,
                'success' => false,
                'error' => 'El servicio de citas no está disponible temporalmente. Intenta nuevamente en unos minutos.',
                'reference' => $reference,
            ], 503);
        }
    }

    private function dailyQuery(): string
    {
        return <<<'SQL'
            SELECT TOP 1000
                pm.IdProgramacion, CAST(pm.Fecha AS date) AS Fecha,
                pm.IdDepartamento, LTRIM(RTRIM(ISNULL(dh.Nombre, ''))) AS Departamento,
                pm.IdEspecialidad, LTRIM(RTRIM(ISNULL(e.Nombre, ''))) AS Especialidad,
                pm.IdServicio, LTRIM(RTRIM(ISNULL(s.Nombre, ''))) AS Servicio,
                pm.IdMedico,
                UPPER(LTRIM(RTRIM(ISNULL(emp.ApellidoPaterno, '') + ' ' + ISNULL(emp.ApellidoMaterno, '') + ' ' + ISNULL(emp.Nombres, '')))) AS Medico,
                pm.HoraInicio, pm.HoraFin, pm.IdTurno,
                LTRIM(RTRIM(ISNULL(t.Descripcion, ''))) AS Turno,
                ISNULL(pm.TiempoPromedioAtencion, 0) AS TiempoPromedioAtencion,
                CASE WHEN pm.HoraInicio IS NOT NULL AND pm.HoraFin IS NOT NULL
                    THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) ELSE 0 END AS MinutosProgramados,
                CASE WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0 AND pm.HoraInicio IS NOT NULL AND pm.HoraFin IS NOT NULL
                    THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion ELSE 0 END AS CuposProgramados,
                COUNT(DISTINCT CASE WHEN ISNULL(c.EsCitaAdicional, 0) = 0 AND ISNULL(c.IdEstadoCita, 0) NOT IN (2, 3) THEN c.IdCita END) AS CitasOtorgadas,
                COUNT(DISTINCT CASE WHEN ISNULL(c.IdEstadoCita, 0) = 2 THEN c.IdCita END) AS CitasAtendidas,
                COUNT(DISTINCT CASE WHEN ISNULL(c.EsCitaAdicional, 0) = 1 AND ISNULL(c.IdEstadoCita, 0) NOT IN (2, 3) THEN c.IdCita END) AS CitasAdicionales,
                COUNT(DISTINCT cb.IdCitaBloqueada) AS CitasBloqueadas,
                CASE WHEN ISNULL(pm.TiempoPromedioAtencion, 0) > 0 AND pm.HoraInicio IS NOT NULL AND pm.HoraFin IS NOT NULL
                    THEN DATEDIFF(MINUTE, CAST(pm.HoraInicio AS time), CAST(pm.HoraFin AS time)) / pm.TiempoPromedioAtencion
                        - COUNT(DISTINCT CASE WHEN ISNULL(c.EsCitaAdicional, 0) = 0 AND ISNULL(c.IdEstadoCita, 0) <> 3 THEN c.IdCita END)
                        - COUNT(DISTINCT cb.IdCitaBloqueada) ELSE 0 END AS CuposDisponibles
            FROM dbo.ProgramacionMedica pm
            LEFT JOIN dbo.Servicios s ON s.IdServicio = pm.IdServicio
            LEFT JOIN dbo.Especialidades e ON e.IdEspecialidad = pm.IdEspecialidad
            LEFT JOIN dbo.DepartamentosHospital dh ON dh.IdDepartamento = pm.IdDepartamento
            LEFT JOIN dbo.Turnos t ON t.IdTurno = pm.IdTurno
            LEFT JOIN dbo.Medicos m ON m.IdMedico = pm.IdMedico
            LEFT JOIN dbo.Empleados emp ON emp.IdEmpleado = m.IdEmpleado
            LEFT JOIN dbo.Citas c ON c.IdProgramacion = pm.IdProgramacion
                AND CAST(c.Fecha AS date) BETWEEN CAST(? AS date) AND CAST(? AS date)
            LEFT JOIN dbo.CitasBloqueadas cb ON cb.IdMedico = pm.IdMedico
                AND CAST(cb.Fecha AS date) = CAST(pm.Fecha AS date)
                AND cb.HoraInicio >= pm.HoraInicio AND cb.HoraFin <= pm.HoraFin
            WHERE CAST(pm.Fecha AS date) BETWEEN CAST(? AS date) AND CAST(? AS date)
            GROUP BY pm.IdProgramacion, CAST(pm.Fecha AS date), pm.IdDepartamento, dh.Nombre,
                pm.IdEspecialidad, e.Nombre, pm.IdServicio, s.Nombre, pm.IdMedico,
                emp.ApellidoPaterno, emp.ApellidoMaterno, emp.Nombres, pm.HoraInicio,
                pm.HoraFin, pm.IdTurno, t.Descripcion, pm.TiempoPromedioAtencion
            ORDER BY CAST(pm.Fecha AS date) DESC, CAST(pm.HoraInicio AS time), e.Nombre, s.Nombre
            SQL;
    }

    private function date(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : substr((string) ($value ?? ''), 0, 10);
    }

    private function time(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        return preg_match('/(\d{2}:\d{2})/', (string) ($value ?? ''), $matches) ? $matches[1] : trim((string) ($value ?? ''));
    }
}
