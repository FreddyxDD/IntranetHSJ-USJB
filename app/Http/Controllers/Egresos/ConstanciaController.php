<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\Cie10;
use App\Models\Egresos\ConfiguracionConstancia;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\Egreso;
use App\Services\Egresos\AnnualCertificateSequence;
use App\Services\Egresos\ConstanciaDocumentPresenter;
use App\Services\Egresos\ConstanciaEpisodeSelection;
use App\Services\Egresos\ConstanciaTrace;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ConstanciaController extends Controller
{
    public function preview(
        Request $request,
        AnnualCertificateSequence $sequence,
        ConstanciaEpisodeSelection $selection
    ): JsonResponse {
        $validated = $request->validate([
            'egreso_ids' => ['required', 'array', 'min:1', 'max:'.ConstanciaEpisodeSelection::MAX_EPISODES],
            'egreso_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $episodes = $selection->resolve($validated['egreso_ids']);
        $year = now()->year;
        $actor = EgresoController::centralActor();
        $expiresAt = now()->addMinutes(15);
        $preview = $selection->preview($episodes, $sequence->peek($year), $year);
        $preview['preview_token'] = Crypt::encryptString(json_encode([
            'episode_ids' => $episodes->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all(),
            'user_id' => $actor['user_id'],
            'expires_at' => $expiresAt->timestamp,
        ], JSON_THROW_ON_ERROR));
        $preview['confirmation_expires_at'] = $expiresAt->toISOString();

        return response()->json([
            'ok' => true,
            'message' => 'Vista preliminar generada. La numeración aún no ha sido reservada.',
            'data' => $preview,
        ]);
    }

    public function store(
        Request $request,
        AnnualCertificateSequence $sequence,
        ConstanciaEpisodeSelection $selection
    ): JsonResponse {
        $validated = $request->validate([
            'egreso_ids' => ['required_without:egreso_id', 'array', 'min:1', 'max:'.ConstanciaEpisodeSelection::MAX_EPISODES],
            'egreso_ids.*' => ['required', 'integer', 'distinct'],
            'egreso_id' => ['required_without:egreso_ids', 'nullable', 'integer', Rule::exists(Egreso::class, 'id')],
            'observacion' => ['nullable', 'string', 'max:1000'],
            'preview_token' => ['required', 'string'],
        ]);
        $episodeIds = $validated['egreso_ids'] ?? [$validated['egreso_id']];
        $actor = EgresoController::centralActor();
        $this->validatePreviewToken($validated['preview_token'], $episodeIds, $actor);
        $previewTokenHash = hash('sha256', $validated['preview_token']);
        if (Constancia::query()->where('preview_token_hash', $previewTokenHash)->exists()) {
            throw ValidationException::withMessages([
                'preview_token' => 'Esta confirmación ya fue utilizada. Genere una nueva vista preliminar.',
            ]);
        }
        $year = now()->year;
        $ownerKey = AnnualCertificateSequence::OWNER_KEY;

        try {
            $certificate = DB::transaction(function () use (
                $episodeIds,
                $validated,
                $actor,
                $year,
                $ownerKey,
                $request,
                $sequence,
                $selection,
                $previewTokenHash
            ): Constancia {
                $episodes = $selection->resolve($episodeIds, true);
                $snapshots = $selection->snapshots($episodes);
                $first = $snapshots[0];
                $services = collect($snapshots)
                    ->pluck('servicio')
                    ->filter()
                    ->unique();
                $conditions = collect($snapshots)
                    ->pluck('condicion')
                    ->filter()
                    ->unique();
                $financing = collect($snapshots)
                    ->pluck('financia')
                    ->filter()
                    ->unique();
                $number = $sequence->next($year);
                $configuration = ConfiguracionConstancia::query()->find(1);
                $payload = [
                    'egreso_id' => $first['egreso_id'],
                    'numero' => $number,
                    'anio' => $year,
                    'sequence_owner_key' => $ownerKey,
                    'preview_token_hash' => $previewTokenHash,
                    'issuer_account_id' => $actor['account_id'],
                    'issuer_username' => $actor['username'],
                    'issuer_display_name' => $actor['display_name'],
                    'numhc' => $first['numhc'],
                    'doc_iden' => $first['doc_iden'],
                    'doc_tipo_id' => $first['doc_tipo_id'],
                    'doc_iden_original' => $first['doc_iden'],
                    'paciente' => $first['paciente'],
                    'nombres' => $first['nombres'],
                    'apellidos' => $first['apellidos'],
                    'fecing' => $episodes->pluck('fecing')->filter()->min(),
                    'fecegr' => $episodes->pluck('fecegr')->filter()->max(),
                    'ups' => $services->count() === 1 ? $first['ups'] : 'MULTIPLES',
                    'servicio' => $services->count() === 1 ? $services->first() : 'VARIOS SERVICIOS',
                    'condicion' => $conditions->count() === 1 ? $conditions->first() : 'SEGÚN EPISODIO',
                    'financia' => $financing->count() === 1 ? $financing->first() : 'SEGÚN EPISODIO',
                    'iniciales_director' => $configuration?->iniciales_director,
                    'iniciales_jefe' => $configuration?->iniciales_jefe,
                    'iniciales_ccp' => $configuration?->iniciales_ccp,
                    'nombre_director' => $configuration?->nombre_director,
                    'nombre_jefe' => $configuration?->nombre_jefe,
                    'cargo_director' => $configuration?->cargo_director,
                    'cargo_jefe' => $configuration?->cargo_jefe,
                    'configuracion_observacion' => $configuration?->observacion,
                    'observacion' => $validated['observacion'] ?? null,
                    'estado' => 'generada',
                    'source_system' => 'intranet_hsj',
                ];

                foreach (range(1, 4) as $position) {
                    $payload["coddiag{$position}"] = $first["coddiag{$position}"];
                    $payload["descdiag{$position}"] = $first["descdiag{$position}"];
                }
                $payload['source_fingerprint'] = hash('sha256', json_encode([
                    ...$payload,
                    'episode_ids' => collect($snapshots)->pluck('egreso_id')->all(),
                ], JSON_UNESCAPED_UNICODE));
                $certificate = Constancia::query()->create($payload);
                $certificate->episodios()->createMany($snapshots);
                $certificate->load('episodios');
                $after = $certificate->toArray();
                $after['episode_ids'] = $certificate->episodios->pluck('egreso_id')->all();

                app(ConstanciaTrace::class)->record(
                    $certificate,
                    'generar',
                    "Constancia generada con {$certificate->episodios->count()} episodio(s) seleccionado(s).",
                    null,
                    $after,
                    $actor,
                    $request
                );

                return $certificate;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (Constancia::query()->where('preview_token_hash', $previewTokenHash)->exists()) {
                throw ValidationException::withMessages([
                    'preview_token' => 'Esta confirmación ya fue utilizada. Genere una nueva vista preliminar.',
                ]);
            }

            throw $exception;
        }

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
            $locked->load('episodios');
            if ($locked->episodios->count() > 1) {
                throw ValidationException::withMessages([
                    'constancia' => 'Una constancia con varios episodios no puede modificarse parcialmente. Debe anularse y generarse nuevamente con la selección correcta.',
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
            if ($episode = $locked->episodios->first()) {
                $episode->fill([
                    'numhc' => $locked->numhc,
                    'doc_iden' => $locked->documento,
                    'paciente' => $locked->paciente,
                    'nombres' => $locked->nombres,
                    'apellidos' => $locked->apellidos,
                    'fecing' => $locked->fecing,
                    'fecegr' => $locked->fecegr,
                    'ups' => $locked->ups,
                    'servicio' => $locked->servicio,
                    'condicion' => $locked->condicion,
                    'financia' => $locked->financia,
                    'coddiag1' => $locked->coddiag1,
                    'descdiag1' => $locked->descdiag1,
                    'coddiag2' => $locked->coddiag2,
                    'descdiag2' => $locked->descdiag2,
                    'coddiag3' => $locked->coddiag3,
                    'descdiag3' => $locked->descdiag3,
                    'coddiag4' => $locked->coddiag4,
                    'descdiag4' => $locked->descdiag4,
                ])->save();
            }
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
        $this->authorizeDocumentAccess();
        if (! $constancia->canBePrinted()) {
            abort(409, 'La constancia está anulada y su reimpresión no está autorizada. Puede consultarla desde el historial.');
        }

        return view('egresos.certificate', [
            'constancia' => $constancia,
            'document' => $presenter->present($constancia),
            'allowPrint' => true,
            'printAuthorizationUrl' => route('egresos.certificates.authorize-print', $constancia, false),
        ]);
    }

    public function authorizePrint(Request $request, Constancia $constancia): JsonResponse
    {
        $this->authorizeDocumentAccess();
        $actor = EgresoController::centralActor();

        $printable = DB::transaction(function () use ($constancia, $actor, $request): Constancia {
            $locked = Constancia::query()->lockForUpdate()->findOrFail($constancia->id);
            if (! $locked->canBePrinted()) {
                throw ValidationException::withMessages([
                    'constancia' => 'La constancia fue anulada y no puede imprimirse.',
                ]);
            }

            $before = $locked->toArray();
            $locked->print_count = ((int) $locked->print_count) + 1;
            $locked->first_printed_at ??= now();
            $locked->last_printed_at = now();
            $locked->last_printed_by_account_id = $actor['account_id'];
            $locked->last_printed_by_username = $actor['username'];
            $locked->save();

            app(ConstanciaTrace::class)->record(
                $locked,
                'imprimir',
                "Se autorizó la impresión de la constancia {$locked->numero}-{$locked->anio}.",
                $before,
                $locked->fresh()->toArray(),
                $actor,
                $request
            );

            return $locked->fresh();
        }, 3);

        return response()->json([
            'ok' => true,
            'message' => 'Impresión autorizada y registrada.',
            'data' => [
                'print_count' => $printable->print_count,
                'authorized_at' => $printable->last_printed_at,
            ],
        ]);
    }

    public function viewDocument(
        Constancia $constancia,
        ConstanciaDocumentPresenter $presenter
    ): View {
        $this->authorizeDocumentAccess();

        return view('egresos.certificate', [
            'constancia' => $constancia,
            'document' => $presenter->present($constancia),
            'allowPrint' => false,
        ]);
    }

    private function authorizeDocumentAccess(): void
    {
        if (! ueei_tiene_permiso('egresos.history.view')
            && ! ueei_tiene_permiso('egresos.certificates.create')) {
            abort(403);
        }
    }

    private function validatePreviewToken(string $token, array $episodeIds, array $actor): void
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'preview_token' => 'La vista preliminar no es válida. Genérela nuevamente antes de confirmar.',
            ]);
        }

        $expectedIds = collect($episodeIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        $previewIds = collect($payload['episode_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        $sameUser = (int) ($payload['user_id'] ?? 0) === (int) $actor['user_id'];
        $notExpired = (int) ($payload['expires_at'] ?? 0) >= now()->timestamp;

        if ($previewIds !== $expectedIds || ! $sameUser || ! $notExpired) {
            throw ValidationException::withMessages([
                'preview_token' => 'La selección cambió o la vista preliminar venció. Revísela nuevamente antes de generar.',
            ]);
        }
    }
}
