<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\ConfiguracionConstancia;
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
            'data' => $this->configuration(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'iniciales_director' => ['nullable', 'string', 'max:20'],
            'iniciales_jefe' => ['nullable', 'string', 'max:20'],
            'iniciales_ccp' => ['nullable', 'string', 'max:20'],
            'nombre_director' => ['nullable', 'string', 'max:180'],
            'nombre_jefe' => ['nullable', 'string', 'max:180'],
            'cargo_director' => ['nullable', 'string', 'max:180'],
            'cargo_jefe' => ['nullable', 'string', 'max:180'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]);
        $actor = EgresoController::centralActor();

        $configuration = DB::transaction(function () use ($validated, $actor): ConfiguracionConstancia {
            $current = $this->configuration();
            $before = $current->toArray();
            $current->fill(collect($validated)->map(
                fn ($value) => is_string($value) ? trim($value) ?: null : $value
            )->all());
            $current->updated_by_account_id = $actor['account_id'];
            $current->updated_by_username = $actor['username'];
            $current->save();

            DB::table('auditoria.eventos')->insert([
                'event_uuid' => (string) Str::uuid(),
                'application_code' => 'intranet_hsj',
                'module' => 'egresos',
                'event_type' => 'certificate_configuration.updated',
                'action' => 'update',
                'subject_type' => ConfiguracionConstancia::class,
                'subject_id' => '1',
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

            return $current->fresh();
        }, 3);

        return response()->json([
            'ok' => true,
            'message' => 'Configuración institucional actualizada.',
            'data' => $configuration,
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
