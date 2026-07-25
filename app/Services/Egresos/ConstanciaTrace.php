<?php

namespace App\Services\Egresos;

use App\Models\Egresos\Constancia;
use App\Models\Egresos\ConstanciaHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConstanciaTrace
{
    public function record(
        Constancia $certificate,
        string $action,
        string $description,
        ?array $before,
        array $after,
        array $actor,
        Request $request
    ): void {
        ConstanciaHistorial::query()->create([
            'source_system' => 'intranet_hsj',
            'constancia_id' => $certificate->id,
            'accion' => $action,
            'descripcion' => $description,
            'datos_anteriores' => $before,
            'datos_nuevos' => $after,
            'actor_account_id' => $actor['account_id'],
            'actor_username' => $actor['username'],
            'actor_display_name' => $actor['display_name'],
            'ip' => $request->ip(),
            'source_fingerprint' => hash(
                'sha256',
                $action.':'.$certificate->id.':'.now()->toISOString().':'.Str::uuid()
            ),
            'occurred_at' => now(),
        ]);

        DB::table('auditoria.eventos')->insert([
            'event_uuid' => (string) Str::uuid(),
            'application_code' => 'intranet_hsj',
            'module' => 'egresos',
            'event_type' => 'certificate.'.$action,
            'action' => $action,
            'subject_type' => Constancia::class,
            'subject_id' => (string) $certificate->id,
            'actor_account_id' => $actor['account_id'],
            'actor_username' => $actor['username'],
            'actor_display_name' => $actor['display_name'],
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 510),
            'data_before' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'data_after' => json_encode($after, JSON_UNESCAPED_UNICODE),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
