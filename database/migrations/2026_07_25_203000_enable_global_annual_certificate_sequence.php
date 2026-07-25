<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const GLOBAL_OWNER = 'application:egresos';

    public function up(): void
    {
        Schema::table('egresos.constancias', function (Blueprint $table): void {
            $table->string('nombre_director', 180)->nullable();
            $table->string('nombre_jefe', 180)->nullable();
            $table->string('cargo_director', 180)->nullable();
            $table->string('cargo_jefe', 180)->nullable();
            $table->text('configuracion_observacion')->nullable();
        });

        DB::table('egresos.constancias')
            ->select('anio')
            ->selectRaw('MAX(numero) AS ultimo_numero')
            ->groupBy('anio')
            ->orderBy('anio')
            ->get()
            ->each(function (object $row): void {
                DB::table('egresos.correlativos')->updateOrInsert(
                    [
                        'sequence_owner_key' => self::GLOBAL_OWNER,
                        'anio' => (int) $row->anio,
                    ],
                    [
                        'ultimo_numero' => (int) $row->ultimo_numero,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });

        DB::table('egresos.constancia_historial')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $eventType = 'certificate.'.$row->accion;
                    $exists = DB::table('auditoria.eventos')
                        ->where('module', 'egresos')
                        ->where('event_type', $eventType)
                        ->where('subject_id', (string) $row->constancia_id)
                        ->where('occurred_at', $row->occurred_at)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('auditoria.eventos')->insert([
                        'event_uuid' => (string) Str::uuid(),
                        'application_code' => 'intranet_hsj',
                        'module' => 'egresos',
                        'event_type' => $eventType,
                        'action' => $row->accion,
                        'subject_type' => 'App\\Models\\Egresos\\Constancia',
                        'subject_id' => (string) $row->constancia_id,
                        'actor_account_id' => $row->actor_account_id,
                        'actor_username' => $row->actor_username,
                        'actor_display_name' => $row->actor_display_name,
                        'ip' => $row->ip,
                        'data_before' => $row->datos_anteriores,
                        'data_after' => $row->datos_nuevos,
                        'metadata' => json_encode([
                            'backfilled_from' => 'egresos.constancia_historial',
                            'history_id' => (int) $row->id,
                        ], JSON_UNESCAPED_UNICODE),
                        'occurred_at' => $row->occurred_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('egresos.correlativos')
            ->where('sequence_owner_key', self::GLOBAL_OWNER)
            ->delete();
        DB::table('auditoria.eventos')
            ->where('module', 'egresos')
            ->where('metadata', 'like', '%"backfilled_from":"egresos.constancia_historial"%')
            ->delete();

        Schema::table('egresos.constancias', function (Blueprint $table): void {
            $table->dropColumn([
                'nombre_director',
                'nombre_jefe',
                'cargo_director',
                'cargo_jefe',
                'configuracion_observacion',
            ]);
        });
    }
};
