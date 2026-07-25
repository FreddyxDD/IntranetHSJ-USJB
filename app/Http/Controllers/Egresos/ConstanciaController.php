<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\Cie10;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\ConstanciaHistorial;
use App\Models\Egresos\Egreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ConstanciaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'egreso_id' => ['required', 'integer', Rule::exists(Egreso::class, 'id')],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);
        $actor = EgresoController::centralActor();
        $egreso = Egreso::query()->findOrFail($validated['egreso_id']);
        $year = now()->year;
        $ownerKey = $actor['account_id'] ? 'account:'.$actor['account_id'] : 'user:'.$actor['user_id'];

        $certificate = DB::transaction(function () use ($egreso, $validated, $actor, $year, $ownerKey): Constancia {
            $counter = DB::table('egresos.correlativos')
                ->where('sequence_owner_key', $ownerKey)
                ->where('anio', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                DB::table('egresos.correlativos')->insert([
                    'sequence_owner_key' => $ownerKey,
                    'anio' => $year,
                    'ultimo_numero' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $counter = DB::table('egresos.correlativos')
                    ->where('sequence_owner_key', $ownerKey)
                    ->where('anio', $year)
                    ->lockForUpdate()
                    ->first();
            }

            $number = ((int) $counter->ultimo_numero) + 1;
            DB::table('egresos.correlativos')
                ->where('id', $counter->id)
                ->update(['ultimo_numero' => $number, 'updated_at' => now()]);

            $diagnoses = Cie10::query()
                ->whereIn('codigo_normalizado', collect(range(1, 4))
                    ->map(fn (int $i): string => strtoupper(str_replace('.', '', (string) $egreso->getAttribute("coddiag{$i}"))))
                    ->filter())
                ->pluck('descripcion', 'codigo_normalizado');
            $payload = [
                'egreso_id' => $egreso->id,
                'numero' => $number,
                'anio' => $year,
                'sequence_owner_key' => $ownerKey,
                'issuer_account_id' => $actor['account_id'],
                'issuer_username' => $actor['username'],
                'issuer_display_name' => $actor['display_name'],
                'numhc' => $egreso->numhc,
                'doc_iden' => $egreso->doc_iden,
                'paciente' => $egreso->paciente,
                'nombres' => $egreso->nomb,
                'apellidos' => $egreso->apell,
                'fecing' => $egreso->fecing,
                'fecegr' => $egreso->fecegr,
                'ups' => $egreso->ups,
                'servicio' => $egreso->ups,
                'condicion' => $egreso->condicion,
                'financia' => $egreso->financia,
                'observacion' => $validated['observacion'] ?? null,
                'estado' => 'generada',
                'source_system' => 'intranet_hsj',
            ];

            foreach (range(1, 4) as $position) {
                $code = (string) $egreso->getAttribute("coddiag{$position}");
                $payload["coddiag{$position}"] = $code ?: null;
                $payload["descdiag{$position}"] = $code
                    ? $diagnoses[strtoupper(str_replace('.', '', $code))] ?? null
                    : null;
            }
            $payload['source_fingerprint'] = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
            $certificate = Constancia::query()->create($payload);

            ConstanciaHistorial::query()->create([
                'source_system' => 'intranet_hsj',
                'constancia_id' => $certificate->id,
                'accion' => 'generar',
                'descripcion' => 'Constancia generada desde el módulo central de Egresos.',
                'datos_nuevos' => $certificate->toArray(),
                'actor_account_id' => $actor['account_id'],
                'actor_username' => $actor['username'],
                'actor_display_name' => $actor['display_name'],
                'ip' => request()->ip(),
                'source_fingerprint' => hash('sha256', 'generate:'.$certificate->id.':'.now()->toISOString()),
                'occurred_at' => now(),
            ]);
            DB::table('auditoria.eventos')->insert([
                'event_uuid' => (string) Str::uuid(),
                'application_code' => 'intranet_hsj',
                'module' => 'egresos',
                'event_type' => 'certificate.generated',
                'action' => 'generate',
                'subject_type' => Constancia::class,
                'subject_id' => (string) $certificate->id,
                'actor_account_id' => $actor['account_id'],
                'actor_username' => $actor['username'],
                'actor_display_name' => $actor['display_name'],
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 510),
                'data_after' => json_encode($certificate->toArray(), JSON_UNESCAPED_UNICODE),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $certificate;
        }, 3);

        return response()->json([
            'ok' => true,
            'message' => 'Constancia generada correctamente.',
            'data' => $certificate,
            'print_url' => route('egresos.certificates.print', $certificate),
        ], 201);
    }

    public function print(Constancia $constancia): View
    {
        if (! ueei_tiene_permiso('egresos.history.view')
            && ! ueei_tiene_permiso('egresos.certificates.create')) {
            abort(403);
        }

        return view('egresos.certificate', ['constancia' => $constancia]);
    }
}
