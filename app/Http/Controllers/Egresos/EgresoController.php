<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Egresos\SaveEgresoRequest;
use App\Models\AccessAccount;
use App\Models\Egresos\Cie10;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\Egreso;
use App\Services\Egresos\AnnualCertificateSequence;
use App\Services\Egresos\EgresoTrace;
use App\Services\Egresos\SighPatientSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class EgresoController extends Controller
{
    public function index(): View
    {
        return view('egresos.index', [
            'centralUser' => [
                'id' => (int) ($_SESSION['ueei_id'] ?? 0),
                'name' => (string) ($_SESSION['ueei_nombre'] ?? 'Usuario'),
                'email' => (string) ($_SESSION['ueei_correo'] ?? ''),
                'roles' => array_values($_SESSION['identity_roles'] ?? []),
            ],
            'permissions' => array_values($_SESSION['identity_permissions'] ?? []),
            'abilities' => [
                'viewRecords' => ueei_tiene_permiso('egresos.records.view'),
                'createRecords' => ueei_tiene_permiso('egresos.records.create'),
                'updateRecords' => ueei_tiene_permiso('egresos.records.update'),
                'manageImports' => ueei_tiene_permiso('egresos.imports.manage'),
                'createCertificates' => ueei_tiene_permiso('egresos.certificates.create'),
                'updateCertificates' => ueei_tiene_permiso('egresos.certificates.update'),
                'cancelCertificates' => ueei_tiene_permiso('egresos.certificates.cancel'),
                'viewHistory' => ueei_tiene_permiso('egresos.history.view'),
                'viewReports' => ueei_tiene_permiso('egresos.reports.view'),
                'manageConfiguration' => ueei_tiene_permiso('egresos.configuration.manage'),
                'viewAudit' => ueei_tiene_permiso('egresos.history.view'),
            ],
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $now = now();

        return response()->json([
            'ok' => true,
            'data' => [
                'totalEgresos' => Egreso::query()->count(),
                'egresosMes' => Egreso::query()
                    ->whereYear('fecegr', $now->year)
                    ->whereMonth('fecegr', $now->month)
                    ->count(),
                'reportesGenerados' => Constancia::query()->count(),
                'constanciasActivas' => Constancia::query()->where('estado', '<>', 'anulada')->count(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'ups' => ['nullable', 'string', 'max:50'],
        ]);

        $text = trim((string) ($validated['q'] ?? ''));
        $query = Egreso::query()->orderByDesc('imported_at')->orderByDesc('id');

        $query
            ->when($validated['date_from'] ?? null, fn ($builder, $date) => $builder->whereDate('fecegr', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($builder, $date) => $builder->whereDate('fecegr', '<=', $date))
            ->when($validated['ups'] ?? null, fn ($builder, $ups) => $builder->where('ups', $ups));

        if ($text !== '') {
            $escaped = str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $text);
            $query->where(function ($builder) use ($escaped): void {
                $like = '%'.$escaped.'%';
                $builder->where('numhc', 'like', $like)
                    ->orWhere('doc_numero', 'like', $like)
                    ->orWhere('doc_iden', 'like', $like)
                    ->orWhere('nomb', 'like', $like)
                    ->orWhere('apell', 'like', $like);
            });
        }

        $page = $query->paginate((int) ($validated['per_page'] ?? 20));
        $items = collect($page->items());
        $diagnosisCodes = $items
            ->flatMap(fn (Egreso $egreso): array => [
                $egreso->coddiag1, $egreso->coddiag2, $egreso->coddiag3, $egreso->coddiag4,
            ])
            ->filter()
            ->map(fn ($code): string => strtoupper(str_replace('.', '', trim((string) $code))))
            ->unique();
        $diagnoses = Cie10::query()
            ->whereIn('codigo_normalizado', $diagnosisCodes)
            ->pluck('descripcion', 'codigo_normalizado');

        return response()->json([
            'ok' => true,
            'data' => $items->map(fn (Egreso $egreso): array => $this->mapEgreso($egreso, $diagnoses->all())),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(Egreso $egreso): JsonResponse
    {
        $codes = collect([$egreso->coddiag1, $egreso->coddiag2, $egreso->coddiag3, $egreso->coddiag4])
            ->filter()
            ->map(fn ($code): string => strtoupper(str_replace('.', '', trim((string) $code))));
        $diagnoses = Cie10::query()
            ->whereIn('codigo_normalizado', $codes)
            ->pluck('descripcion', 'codigo_normalizado')
            ->all();

        return response()->json([
            'ok' => true,
            'data' => $this->mapEgreso($egreso, $diagnoses),
        ]);
    }

    public function timeline(Request $request, Egreso $egreso): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:20'],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 8);
        $page = $this->patientEpisodesQuery($egreso)
            ->orderByDesc('fecing')
            ->orderByDesc('fecegr')
            ->orderByDesc('id')
            ->paginate($perPage);
        $items = collect($page->items());
        $diagnosisCodes = $items
            ->flatMap(fn (Egreso $episode): array => [
                $episode->coddiag1, $episode->coddiag2, $episode->coddiag3, $episode->coddiag4,
            ])
            ->filter()
            ->map(fn ($code): string => strtoupper(str_replace('.', '', trim((string) $code))))
            ->unique();
        $diagnoses = Cie10::query()
            ->whereIn('codigo_normalizado', $diagnosisCodes)
            ->pluck('descripcion', 'codigo_normalizado')
            ->all();
        $offset = ($page->currentPage() - 1) * $page->perPage();

        return response()->json([
            'ok' => true,
            'data' => [
                'patient' => [
                    'paciente' => $egreso->paciente,
                    'numhc' => $egreso->numhc,
                    'documento' => $egreso->documento,
                    'total_episodes' => $page->total(),
                ],
                'episodes' => $items->map(function (Egreso $episode, int $index) use (
                    $diagnoses,
                    $egreso,
                    $page,
                    $offset
                ): array {
                    $data = $this->mapEgreso($episode, $diagnoses);
                    $position = $page->total() - $offset - $index;
                    $data['episode_number'] = max(1, $position);
                    $data['is_readmission'] = $position > 1;
                    $data['is_selected'] = $episode->id === $egreso->id;
                    $data['is_latest'] = ($offset + $index) === 0;

                    return $data;
                })->values(),
                'meta' => [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                    'has_more' => $page->hasMorePages(),
                ],
            ],
        ]);
    }

    public function patients(Request $request, SighPatientSource $source): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:20'],
        ]);

        $rows = $source->search($validated['q'])->map(fn ($patient): array => [
            'id' => (int) $patient->IdPaciente,
            'historia_clinica' => (string) $patient->NroHistoriaClinica,
            'documento' => trim((string) $patient->NroDocumento),
            'tipo_documento_id' => $patient->IdDocIdentidad ? (int) $patient->IdDocIdentidad : null,
            'tipo_documento' => (string) ($patient->TipoDocumento ?? ''),
            'nombres' => trim(implode(' ', array_filter([
                $patient->PrimerNombre,
                $patient->SegundoNombre,
                $patient->TercerNombre,
            ]))),
            'apellidos' => trim(implode(' ', array_filter([
                $patient->ApellidoPaterno,
                $patient->ApellidoMaterno,
            ]))),
            'source' => $source->sourceCode(),
        ]);

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function store(
        SaveEgresoRequest $request,
        EgresoTrace $trace,
        SighPatientSource $patientSource
    ): JsonResponse {
        $data = $this->withVerifiedPatientDocument($request->validated(), $patientSource);
        $actor = self::centralActor();

        $egreso = DB::transaction(function () use ($data, $actor, $request, $trace): Egreso {
            $this->lockEgresoWrites();
            $this->ensureNotDuplicate($data);
            $egreso = Egreso::query()->create($this->operationalData($data));
            $trace->record(
                $egreso,
                'create',
                null,
                $egreso->fresh()->toArray(),
                $actor,
                $request,
                ['source' => 'manual_exception']
            );

            return $egreso;
        });

        return response()->json([
            'ok' => true,
            'message' => 'Egreso registrado con trazabilidad central.',
            'data' => $this->mapEgreso($egreso, []),
        ], 201);
    }

    public function update(
        SaveEgresoRequest $request,
        Egreso $egreso,
        EgresoTrace $trace,
        SighPatientSource $patientSource
    ): JsonResponse {
        $data = $this->withVerifiedPatientDocument($request->validated(), $patientSource);
        $actor = self::centralActor();

        DB::transaction(function () use ($data, $egreso, $actor, $request, $trace): void {
            $this->lockEgresoWrites();
            $this->ensureNotDuplicate($data, $egreso->id);
            $before = $egreso->toArray();
            $updates = $data;
            $updates['doc_numero'] = $data['doc_iden'] ?? null;
            $updates['doc_source'] = $data['doc_source'] ?? 'intranet_hsj';
            unset($updates['doc_iden']);
            $egreso->fill($updates);
            $egreso->source_fingerprint = $this->fingerprint($egreso->getAttributes());
            $egreso->save();
            $trace->record(
                $egreso,
                'update',
                $before,
                $egreso->fresh()->toArray(),
                $actor,
                $request,
                ['source' => 'controlled_correction']
            );
        });

        return response()->json([
            'ok' => true,
            'message' => 'Egreso corregido y registrado en auditoría.',
            'data' => $this->mapEgreso($egreso->fresh(), []),
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $rows = Egreso::query()
            ->selectRaw('YEAR(fecegr) as anio, MONTH(fecegr) as mes, COUNT(*) as total')
            ->whereNotNull('fecegr')
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('fecegr', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('fecegr', '<=', $date))
            ->groupByRaw('YEAR(fecegr), MONTH(fecegr)')
            ->orderByRaw('YEAR(fecegr), MONTH(fecegr)')
            ->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function services(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $rows = Egreso::query()
            ->selectRaw("COALESCE(NULLIF(ups, ''), 'SIN UPS') as ups, COUNT(*) as total")
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('fecegr', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('fecegr', '<=', $date))
            ->groupByRaw("COALESCE(NULLIF(ups, ''), 'SIN UPS')")
            ->orderByDesc('total')
            ->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function cie10(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $text = trim($validated['q']);

        $rows = Cie10::query()
            ->where(function ($query) use ($text): void {
                $query->where('codigo', 'like', $text.'%')
                    ->orWhere('descripcion', 'like', '%'.$text.'%');
            })
            ->orderBy('codigo')
            ->limit(20)
            ->get(['id', 'codigo', 'descripcion']);

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function certificates(
        Request $request,
        AnnualCertificateSequence $sequence
    ): JsonResponse {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $text = trim((string) ($validated['q'] ?? ''));
        $query = Constancia::query()
            ->with('historial')
            ->withCount('episodios')
            ->orderByDesc('anio')
            ->orderByDesc('numero')
            ->orderByDesc('id');

        if ($text !== '') {
            $query->where(function ($builder) use ($text): void {
                $like = '%'.$text.'%';
                $builder->where('numhc', 'like', $like)
                    ->orWhere('doc_numero', 'like', $like)
                    ->orWhere('doc_iden', 'like', $like)
                    ->orWhere('paciente', 'like', $like);
            });
        }

        $paginator = $query->paginate(20);
        $pageCertificates = $paginator->getCollection();
        $histories = $pageCertificates->pluck('numhc')->filter()->unique()->values();
        $documents = $pageCertificates->pluck('doc_iden')->filter()->unique()->values();
        $relatedCertificates = ($histories->isEmpty() && $documents->isEmpty())
            ? collect()
            : Constancia::query()
                ->withCount('episodios')
                ->where(function ($builder) use ($histories, $documents): void {
                    if ($histories->isNotEmpty()) {
                        $builder->whereIn('numhc', $histories);
                    }
                    if ($documents->isNotEmpty()) {
                        $method = $histories->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $builder->{$method}('doc_iden', $documents);
                    }
                })
                ->orderByDesc('anio')
                ->orderByDesc('numero')
                ->orderByDesc('id')
                ->get();
        $related = $relatedCertificates
            ->concat($pageCertificates->filter(
                fn (Constancia $certificate): bool => trim((string) $certificate->numhc) === ''
                    && trim((string) $certificate->documento) === ''
            ))
            ->unique('id')
            ->groupBy(fn (Constancia $certificate): string => $this->certificatePatientKey($certificate));

        $paginator->setCollection($pageCertificates->map(function (Constancia $certificate) use ($related): Constancia {
            $patientCertificates = $related->get($this->certificatePatientKey($certificate), collect());
            $certificate->setAttribute('issued_at', $certificate->created_at
                ?? $certificate->source_created_at
                ?? $certificate->imported_at);
            $certificate->setAttribute('patient_group', [
                'total' => $patientCertificates->count(),
                'certificates' => $patientCertificates->take(20)->map(fn (Constancia $item): array => [
                    'id' => $item->id,
                    'numero' => $item->numero,
                    'anio' => $item->anio,
                    'estado' => $item->estado,
                    'issued_at' => $item->created_at ?? $item->source_created_at ?? $item->imported_at,
                    'fecegr' => $item->fecegr?->format('Y-m-d'),
                    'servicio' => $item->servicio ?: $item->ups,
                    'episodios_count' => $item->episodios_count,
                ])->values(),
            ]);

            return $certificate;
        }));

        return response()->json([
            'ok' => true,
            'data' => $paginator,
            'summary' => [
                'next_number' => $sequence->peek(now()->year),
                'year' => now()->year,
                'owner_key' => AnnualCertificateSequence::OWNER_KEY,
            ],
        ]);
    }

    private function certificatePatientKey(Constancia $certificate): string
    {
        $history = trim((string) $certificate->numhc);
        if ($history !== '') {
            return 'hc:'.mb_strtolower($history);
        }

        $document = trim((string) $certificate->documento);

        return $document !== ''
            ? 'doc:'.mb_strtolower($document)
            : 'certificate:'.$certificate->id;
    }

    private function mapEgreso(Egreso $egreso, array $diagnoses): array
    {
        $data = $egreso->toArray();
        $data['paciente'] = $egreso->paciente;
        $data['doc_iden_original'] = $egreso->doc_iden_original ?: $egreso->doc_iden;
        $data['doc_iden'] = $egreso->documento;
        $data['diagnosticos'] = collect(range(1, 4))
            ->map(function (int $position) use ($egreso, $diagnoses): ?array {
                $code = trim((string) $egreso->getAttribute("coddiag{$position}"));
                if ($code === '') {
                    return null;
                }
                $normalized = strtoupper(str_replace('.', '', $code));

                return ['codigo' => $code, 'descripcion' => $diagnoses[$normalized] ?? 'Sin descripción CIE-10'];
            })
            ->filter()
            ->values()
            ->all();

        return $data;
    }

    private function patientEpisodesQuery(Egreso $egreso): Builder
    {
        $history = trim((string) $egreso->numhc);
        if ($history !== '') {
            return Egreso::query()->where('numhc', $history);
        }

        $document = trim((string) $egreso->documento);
        if ($document !== '') {
            return Egreso::query()->where(function (Builder $query) use ($document): void {
                $query->where('doc_numero', $document)
                    ->orWhere('doc_iden', $document);
            });
        }

        return Egreso::query()->whereKey($egreso->id);
    }

    public static function centralActor(): array
    {
        $userId = (int) ($_SESSION['ueei_id'] ?? 0);
        $accountId = AccessAccount::query()->where('user_id', $userId)->value('id');

        return [
            'user_id' => $userId,
            'account_id' => $accountId ? (int) $accountId : null,
            'username' => (string) ($_SESSION['ueei_correo'] ?? ''),
            'display_name' => (string) ($_SESSION['ueei_nombre'] ?? ''),
        ];
    }

    private function operationalData(array $data): array
    {
        $document = $data['doc_iden'] ?? null;

        return [
            ...$data,
            'doc_numero' => $document,
            'doc_iden_original' => $document,
            'doc_source' => $data['doc_source'] ?? 'intranet_hsj',
            'source_system' => 'intranet_hsj',
            'source_id' => null,
            'source_fingerprint' => $this->fingerprint($data),
            'imported_at' => now(),
        ];
    }

    private function fingerprint(array $data): string
    {
        $tracked = collect($data)
            ->only([
                'numhc', 'doc_numero', 'doc_iden', 'nomb', 'apell', 'fecing', 'fecegr', 'ups',
                'condicion', 'financia', 'coddiag1', 'coddiag2', 'coddiag3', 'coddiag4',
            ])
            ->map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value)
            ->all();

        return hash('sha256', json_encode($tracked, JSON_UNESCAPED_UNICODE));
    }

    private function ensureNotDuplicate(array $data, ?int $exceptId = null): void
    {
        $query = Egreso::query()
            ->when($exceptId, fn ($builder) => $builder->where('id', '<>', $exceptId))
            ->whereDate('fecegr', $data['fecegr'])
            ->where(function ($builder) use ($data): void {
                if (! empty($data['numhc'])) {
                    $builder->where('numhc', $data['numhc']);
                }
                if (! empty($data['doc_iden'])) {
                    $method = ! empty($data['numhc']) ? 'orWhere' : 'where';
                    $builder->{$method}('doc_numero', $data['doc_iden']);
                }
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'fecegr' => 'Ya existe un egreso para esta historia clínica o documento en la fecha indicada.',
            ]);
        }
    }

    private function lockEgresoWrites(): void
    {
        $result = DB::selectOne(
            "DECLARE @result INT;
             EXEC @result = sys.sp_getapplock
                @Resource = N'intranet_hsj:egresos:write',
                @LockMode = N'Exclusive',
                @LockOwner = N'Transaction',
                @LockTimeout = 10000;
             SELECT @result AS result;"
        );

        if ((int) ($result->result ?? -999) < 0) {
            throw new \RuntimeException('No fue posible obtener el bloqueo de escritura de Egresos.');
        }
    }

    private function withVerifiedPatientDocument(
        array $data,
        SighPatientSource $source
    ): array {
        $history = trim((string) ($data['numhc'] ?? ''));
        if ($history === '') {
            return $data;
        }

        $patient = $source->byHistories([$history])->get($history);
        $document = trim((string) ($patient->NroDocumento ?? ''));
        if (! $patient || $document === '') {
            return $data;
        }

        return [
            ...$data,
            'doc_iden' => $document,
            'doc_tipo_id' => $patient->IdDocIdentidad ?: null,
            'patient_source_id' => $patient->IdPaciente,
            'doc_source' => $source->sourceCode(),
            'document_verified_at' => now(),
        ];
    }
}
