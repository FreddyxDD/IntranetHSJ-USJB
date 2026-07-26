<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\ConfiguracionConstancia;
use App\Models\Egresos\ConfiguracionConstanciaHistorial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConfiguracionConstanciaController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'active' => $this->configuration(),
                'records' => ConfiguracionConstanciaHistorial::query()
                    ->latest('id')
                    ->limit(20)
                    ->get(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'iniciales_director' => ['nullable', 'string', 'max:20'],
            'iniciales_jefe' => ['required', 'string', 'max:20'],
            'iniciales_ccp' => ['required', 'string', 'max:20'],
            'nombre_director' => ['required', 'string', 'max:180'],
            'nombre_jefe' => ['required', 'string', 'max:180'],
            'cargo_director' => ['required', 'string', 'max:180'],
            'cargo_jefe' => ['required', 'string', 'max:180'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]);
        $actor = EgresoController::centralActor();

        [$configuration, $record] = DB::transaction(function () use ($validated, $actor, $request): array {
            $current = ConfiguracionConstancia::query()->lockForUpdate()->find(1)
                ?? $this->configuration();
            $before = $current->toArray();
            $values = collect($validated)->map(
                fn ($value) => is_string($value) ? trim($value) ?: null : $value
            )->all();
            $current->fill($values);
            $current->updated_by_account_id = $actor['account_id'];
            $current->updated_by_username = $actor['username'];
            $current->save();
            $record = ConfiguracionConstanciaHistorial::query()->create([
                'configuracion_id' => 1,
                ...$values,
                'actor_account_id' => $actor['account_id'],
                'actor_username' => $actor['username'],
                'actor_display_name' => $actor['display_name'],
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 510),
            ]);

            DB::table('auditoria.eventos')->insert([
                'event_uuid' => (string) Str::uuid(),
                'application_code' => 'intranet_hsj',
                'module' => 'egresos',
                'event_type' => 'certificate_configuration.registered',
                'action' => 'register',
                'subject_type' => ConfiguracionConstanciaHistorial::class,
                'subject_id' => (string) $record->id,
                'actor_account_id' => $actor['account_id'],
                'actor_username' => $actor['username'],
                'actor_display_name' => $actor['display_name'],
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 510),
                'data_before' => json_encode($before, JSON_UNESCAPED_UNICODE),
                'data_after' => json_encode($current->fresh()->toArray(), JSON_UNESCAPED_UNICODE),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$current->fresh(), $record->fresh()];
        }, 3);

        return response()->json([
            'ok' => true,
            'message' => 'Configuración institucional registrada y activada.',
            'data' => [
                'active' => $configuration,
                'record' => $record,
            ],
        ]);
    }

    private function configuration(): ConfiguracionConstancia
    {
        return ConfiguracionConstancia::query()->firstOrCreate(
            ['id' => 1],
            [
                'cargo_director' => 'DIRECTOR EJECUTIVO',
                'cargo_jefe' => 'JEFE DE LA UNIDAD DE ESTADÍSTICA E INFORMACIÓN',
            ]
        );
    }
}
