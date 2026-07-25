<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\Cie10;
use App\Models\Egresos\ConfiguracionConstancia;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\Egreso;
use App\Services\Egresos\ConstanciaDocumentPresenter;
use App\Services\Egresos\ConstanciaTrace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $certificate = DB::transaction(function () use ($egreso, $validated, $actor, $year, $ownerKey, $request): Constancia {
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
            $configuration = ConfiguracionConstancia::query()->find(1);
            $payload = [
                'egreso_id' => $egreso->id,
                'numero' => $number,
                'anio' => $year,
                'sequence_owner_key' => $ownerKey,
                'issuer_account_id' => $actor['account_id'],
                'issuer_username' => $actor['username'],
                'issuer_display_name' => $actor['display_name'],
                'numhc' => $egreso->numhc,
                'doc_iden' => $egreso->documento,
                'doc_tipo_id' => $egreso->doc_tipo_id,
                'doc_iden_original' => $egreso->doc_iden_original ?: $egreso->documento,
                'paciente' => $egreso->paciente,
                'nombres' => $egreso->nomb,
                'apellidos' => $egreso->apell,
                'fecing' => $egreso->fecing,
                'fecegr' => $egreso->fecegr,
                'ups' => $egreso->ups,
                'servicio' => $egreso->ups,
                'condicion' => $egreso->condicion,
                'financia' => $egreso->financia,
                'iniciales_director' => $configuration?->iniciales_director,
                'iniciales_jefe' => $configuration?->iniciales_jefe,
                'iniciales_ccp' => $configuration?->iniciales_ccp,
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

            app(ConstanciaTrace::class)->record(
                $certificate,
                'generar',
                'Constancia generada desde el módulo central de Egresos.',
                null,
                $certificate->toArray(),
                $actor,
                $request
            );

            return $certificate;
        }, 3);

        return response()->json([
            'ok' => true,
            'message' => 'Constancia generada correctamente.',
            'data' => $certificate,
            'print_url' => route('egresos.certificates.print', $certificate, false),
        ], 201);
    }

    public function update(Request $request, Constancia $constancia): JsonResponse
    {
        $validated = $request->validate([
            'paciente' => ['required', 'string', 'max:250'],
            'nombres' => ['nullable', 'string', 'max:150'],
            'apellidos' => ['nullable', 'string', 'max:150'],
            'doc_iden' => ['nullable', 'string', 'max:30'],
            'numhc' => ['required', 'string', 'max:50'],
            'fecing' => ['nullable', 'date'],
            'fecegr' => ['nullable', 'date', 'after_or_equal:fecing'],
            'ups' => ['nullable', 'string', 'max:100'],
            'servicio' => ['nullable', 'string', 'max:150'],
            'condicion' => ['nullable', 'string', 'max:100'],
            'financia' => ['nullable', 'string', 'max:100'],
            'coddiag1' => ['nullable', 'string', 'max:50'],
            'coddiag2' => ['nullable', 'string', 'max:50'],
            'coddiag3' => ['nullable', 'string', 'max:50'],
            'coddiag4' => ['nullable', 'string', 'max:50'],
            'sigla_servicio' => ['nullable', 'string', 'max:30'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);
        $actor = EgresoController::centralActor();

        $updated = DB::transaction(function () use ($constancia, $validated, $actor, $request): Constancia {
            $locked = Constancia::query()->lockForUpdate()->findOrFail($constancia->id);
            if ($locked->estado === 'anulada') {
                throw ValidationException::withMessages([
                    'constancia' => 'No se puede editar una constancia anulada.',
                ]);
            }

            $before = $locked->toArray();
            $values = collect($validated)->map(
                fn ($value) => is_string($value) ? trim($value) ?: null : $value
            )->all();
            foreach (range(1, 4) as $position) {
                $code = $values["coddiag{$position}"] ?? $locked->getAttribute("coddiag{$position}");
                $normalized = strtoupper(str_replace('.', '', (string) $code));
                $values["descdiag{$position}"] = $normalized === ''
                    ? null
                    : Cie10::query()->where('codigo_normalizado', $normalized)->value('descripcion');
            }
            $values['estado'] = 'editada';
            $locked->fill($values)->save();
            $after = $locked->fresh()->toArray();

            app(ConstanciaTrace::class)->record(
                $locked,
                'editar',
                "Se editó la constancia {$locked->numero}-{$locked->anio}.",
                $before,
                $after,
                $actor,
                $request
            );

            return $locked->fresh();
        }, 3);

        return response()->json([
            'ok' => true,
            'message' => 'Constancia actualizada correctamente.',
            'data' => $updated,
        ]);
    }

    public function cancel(Request $request, Constancia $constancia): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $actor = EgresoController::centralActor();

        $cancelled = DB::transaction(function () use ($constancia, $validated, $actor, $request): Constancia {
            $locked = Constancia::query()->lockForUpdate()->findOrFail($constancia->id);
            if ($locked->estado === 'anulada') {
                throw ValidationException::withMessages([
                    'constancia' => 'La constancia ya se encuentra anulada.',
                ]);
            }

            $before = $locked->toArray();
            $locked->update([
                'estado' => 'anulada',
                'motivo_anulacion' => trim($validated['motivo']),
                'cancelled_by_account_id' => $actor['account_id'],
                'cancelled_by_username' => $actor['username'],
                'cancelled_by_display_name' => $actor['display_name'],
                'cancelled_at' => now(),
            ]);
            $after = $locked->fresh()->toArray();

            app(ConstanciaTrace::class)->record(
                $locked,
                'anular',
                "Se anuló la constancia {$locked->numero}-{$locked->anio}. Motivo: {$validated['motivo']}",
                $before,
                $after,
                $actor,
                $request
            );

            return $locked->fresh();
        }, 3);

        return response()->json([
            'ok' => true,
            'message' => 'Constancia anulada correctamente.',
            'data' => $cancelled,
        ]);
    }

    public function print(
        Constancia $constancia,
        ConstanciaDocumentPresenter $presenter
    ): View {
        if (! ueei_tiene_permiso('egresos.history.view')
            && ! ueei_tiene_permiso('egresos.certificates.create')) {
            abort(403);
        }

        return view('egresos.certificate', [
            'constancia' => $constancia,
            'document' => $presenter->present($constancia),
        ]);
    }
}
