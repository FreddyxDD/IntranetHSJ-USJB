<?php

namespace App\Services\Egresos;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class Cie10Trace
{
    public function record(
        string $eventType,
        string $action,
        ?string $subjectId,
        ?array $before,
        ?array $after,
        array $actor,
        Request $request,
        array $metadata = []
    ): void {
        DB::table('auditoria.eventos')->insert([
            'event_uuid' => (string) Str::uuid(),
            'application_code' => 'intranet_hsj',
            'module' => 'egresos',
            'event_type' => 'cie10.'.$eventType,
            'action' => $action,
            'subject_type' => 'catalogos.cie10',
            'subject_id' => $subjectId,
            'actor_account_id' => $actor['account_id'],
            'actor_username' => $actor['username'],
            'actor_display_name' => $actor['display_name'],
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 510),
            'data_before' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'data_after' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
