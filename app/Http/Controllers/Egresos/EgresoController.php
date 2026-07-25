<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Egresos\SaveEgresoRequest;
use App\Models\AccessAccount;
use App\Models\Egresos\Cie10;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\Egreso;
use App\Services\Egresos\EgresoTrace;
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
        $query = Egreso::query()->orderByDesc('fecegr')->orderByDesc('id');

        $query
            ->when($validated['date_from'] ?? null, fn ($builder, $date) => $builder->whereDate('fecegr', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($builder, $date) => $builder->whereDate('fecegr', '<=', $date))
            ->when($validated['ups'] ?? null, fn ($builder, $ups) => $builder->where('ups', $ups));

        if ($text !== '') {
            $escaped = str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $text);
            $query->where(function ($builder) use ($escaped): void {
                $like = '%'.$escaped.'%';
                $builder->where('numhc', 'like', $like)
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

    public function store(SaveEgresoRequest $request, EgresoTrace $trace): JsonResponse
    {
        $data = $request->validated();
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
        EgresoTrace $trace
    ): JsonResponse {
        $data = $request->validated();
        $actor = self::centralActor();

        DB::transaction(function () use ($data, $egreso, $actor, $request, $trace): void {
            $this->lockEgresoWrites();
            $this->ensureNotDuplicate($data, $egreso->id);
            $before = $egreso->toArray();
            $egreso->fill($data);
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

    public function certificates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $text = trim((string) ($validated['q'] ?? ''));
        $query = Constancia::query()->with('historial')->orderByDesc('created_at');

        if ($text !== '') {
            $query->where(function ($builder) use ($text): void {
                $like = '%'.$text.'%';
                $builder->where('numhc', 'like', $like)
                    ->orWhere('doc_iden', 'like', $like)
                    ->orWhere('paciente', 'like', $like);
            });
        }

        return response()->json(['ok' => true, 'data' => $query->paginate(20)]);
    }

    private function mapEgreso(Egreso $egreso, array $diagnoses): array
    {
        $data = $egreso->toArray();
        $data['paciente'] = $egreso->paciente;
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
        return [
            ...$data,
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
                'numhc', 'doc_iden', 'nomb', 'apell', 'fecing', 'fecegr', 'ups',
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
                    $builder->{$method}('doc_iden', $data['doc_iden']);
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
}
