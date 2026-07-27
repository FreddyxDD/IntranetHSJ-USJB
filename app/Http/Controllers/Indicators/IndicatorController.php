<?php

namespace App\Http\Controllers\Indicators;

use App\Http\Controllers\Controller;
use App\Services\Identity\CentralAccessService;
use App\Support\UserFacingError;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class IndicatorController extends Controller
{
    public function __construct(private readonly CentralAccessService $access) {}

    public function productionPage(): View
    {
        return view('indicators.production', $this->pageData());
    }

    public function efficiencyPage(): View
    {
        return view('indicators.efficiency', $this->pageData());
    }

    public function qualityPage(): View
    {
        return view('indicators.quality', $this->pageData());
    }

    public function production(): JsonResponse
    {
        return $this->safeList(
            fn () => DB::connection('modules')
                ->table('indicadores_produccion_rendimiento')
                ->select([
                    'Orden',
                    'Nom_Indicador',
                    'Variables',
                    'ENE',
                    'ENE_Valor',
                    'FEB',
                    'FEB_Valor',
                    'Total_Anual',
                    'Valor_Final',
                ])
                ->orderBy('Orden')
                ->orderBy('Variables')
                ->get(),
            'producción',
        );
    }

    public function efficiency(): JsonResponse
    {
        return $this->safeList(
            fn () => DB::connection('modules')
                ->table('indicadores_eficiencia')
                ->select([
                    'Orden',
                    'Nombre_Indicador',
                    'Variable',
                    'Ene',
                    'Ene_Valor',
                    'Feb',
                    'Feb_Valor',
                    'Mar',
                    'Mar_Valor',
                    'Total_Anual',
                    'Valor_Final',
                ])
                ->orderBy('Orden')
                ->orderByRaw($this->efficiencyVariableOrder())
                ->orderBy('Variable')
                ->get(),
            'eficiencia',
        );
    }

    public function quality(): JsonResponse
    {
        return $this->safeList(
            fn () => DB::connection('modules')
                ->table('indicadores_calidad')
                ->select([
                    'Orden',
                    'Nombre_Indicador',
                    'Variable',
                    'Ene',
                    'Ene_Valor',
                    'Feb',
                    'Feb_Valor',
                    'Total_Anual',
                    'Valor_Final',
                ])
                ->orderBy('Orden')
                ->orderBy('Variable')
                ->get(),
            'calidad',
        );
    }

    public function efficiencyAdmin(): JsonResponse
    {
        if ($denied = $this->ensureAdministrator()) {
            return $denied;
        }

        return $this->efficiency();
    }

    public function qualityAdmin(): JsonResponse
    {
        if ($denied = $this->ensureAdministrator()) {
            return $denied;
        }

        return $this->quality();
    }

    public function updateEfficiency(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAdministrator()) {
            return $denied;
        }

        $rules = [
            'originalOrden' => ['required', 'integer'],
            'originalNombreIndicador' => ['required', 'string', 'max:255'],
            'originalVariable' => ['required', 'string', 'max:255'],
            'Orden' => ['required', 'integer'],
            'Nombre_Indicador' => ['required', 'string', 'max:255'],
            'Variable' => ['required', 'string', 'max:255'],
            'Ene' => ['nullable', 'integer'],
            'Ene_Valor' => ['nullable', 'numeric'],
            'Feb' => ['nullable', 'integer'],
            'Feb_Valor' => ['nullable', 'numeric'],
            'Mar' => ['nullable', 'integer'],
            'Mar_Valor' => ['nullable', 'numeric'],
            'Total_Anual' => ['nullable', 'numeric'],
            'Valor_Final' => ['nullable', 'numeric'],
        ];

        return $this->updateIndicator(
            $request,
            'indicadores_eficiencia',
            $rules,
            ['Ene', 'Ene_Valor', 'Feb', 'Feb_Valor', 'Mar', 'Mar_Valor', 'Total_Anual', 'Valor_Final'],
            'eficiencia',
        );
    }

    public function updateQuality(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAdministrator()) {
            return $denied;
        }

        $rules = [
            'originalOrden' => ['required', 'integer'],
            'originalNombreIndicador' => ['required', 'string', 'max:255'],
            'originalVariable' => ['required', 'string', 'max:255'],
            'Orden' => ['required', 'integer'],
            'Nombre_Indicador' => ['required', 'string', 'max:255'],
            'Variable' => ['required', 'string', 'max:255'],
            'Ene' => ['nullable', 'string', 'max:255'],
            'Ene_Valor' => ['nullable', 'numeric'],
            'Feb' => ['nullable', 'string', 'max:255'],
            'Feb_Valor' => ['nullable', 'numeric'],
            'Total_Anual' => ['nullable', 'numeric'],
            'Valor_Final' => ['nullable', 'numeric'],
        ];

        return $this->updateIndicator(
            $request,
            'indicadores_calidad',
            $rules,
            ['Ene', 'Ene_Valor', 'Feb', 'Feb_Valor', 'Total_Anual', 'Valor_Final'],
            'calidad',
        );
    }

    private function updateIndicator(
        Request $request,
        string $table,
        array $rules,
        array $optionalFields,
        string $label,
    ): JsonResponse {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $data = $validator->validated();
        $query = DB::connection('modules')->table($table)
            ->where('Orden', $data['originalOrden'])
            ->where('Nombre_Indicador', trim($data['originalNombreIndicador']))
            ->where('Variable', trim($data['originalVariable']));

        try {
            if (! $query->exists()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se encontró el registro a actualizar.',
                ], 404);
            }

            $values = [
                'Orden' => $data['Orden'],
                'Nombre_Indicador' => trim($data['Nombre_Indicador']),
                'Variable' => trim($data['Variable']),
            ];

            foreach ($optionalFields as $field) {
                $values[$field] = $data[$field] ?? null;
            }

            $query->update($values);

            return response()->json([
                'ok' => true,
                'message' => "Registro de {$label} actualizado correctamente.",
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception, "actualizar el indicador de {$label}");
        }
    }

    private function safeList(callable $query, string $label): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'data' => $query()]);
        } catch (Throwable $exception) {
            return $this->failure($exception, "obtener indicadores de {$label}");
        }
    }

    private function failure(Throwable $exception, string $operation): JsonResponse
    {
        $reference = UserFacingError::report($exception, 'INTRA-IND', [
            'operation' => $operation,
        ]);

        return response()->json([
            'ok' => false,
            'message' => "No fue posible {$operation}. Intenta nuevamente.",
            'reference' => $reference,
        ], 503);
    }

    private function ensureAdministrator(): ?JsonResponse
    {
        if ($this->access->isAdministrator()) {
            return null;
        }

        return response()->json([
            'ok' => false,
            'message' => 'No tienes permisos de administrador.',
        ], 403);
    }

    private function pageData(): array
    {
        $user = $this->access->user();

        return [
            'correo' => $user?->email ?? '',
            'rol' => $this->access->isAdministrator() ? 'admin' : 'trabajador',
        ];
    }

    private function efficiencyVariableOrder(): string
    {
        return <<<'SQL'
            CASE
                WHEN Nombre_Indicador = 'Rendimiento de Sala de Operaciones'
                    AND Variable LIKE '%Cirugias y Procedimientos Ejecutadas%' THEN 1
                WHEN Nombre_Indicador = 'Rendimiento de Sala de Operaciones'
                    AND Variable LIKE '%Salas de Operaciones Utilizadas%' THEN 2
                WHEN Nombre_Indicador = 'Porcentaje de Cirugias Suspendidas'
                    AND Variable LIKE '%suspendidas%' THEN 1
                WHEN Nombre_Indicador = 'Porcentaje de Cirugias Suspendidas'
                    AND Variable LIKE '%programadas%' THEN 2
                WHEN Nombre_Indicador = 'Porcentaje de ocupacion cama'
                    AND Variable LIKE '%pacientes-dia%' THEN 1
                WHEN Nombre_Indicador = 'Porcentaje de ocupacion cama'
                    AND Variable LIKE '%pacientes dia%' THEN 1
                WHEN Nombre_Indicador = 'Porcentaje de ocupacion cama'
                    AND Variable LIKE '%camas operativas%' THEN 2
                WHEN Nombre_Indicador = 'Intervalo de Sustitucion de camas'
                    AND Variable LIKE '%Dias cama disponibles%' THEN 1
                WHEN Nombre_Indicador = 'Intervalo de Sustitucion de camas'
                    AND Variable LIKE '%egresos hospitalarios%' THEN 2
                ELSE 999
            END
            SQL;
    }
}
