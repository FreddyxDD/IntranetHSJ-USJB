<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\AccessAccount;
use App\Models\Egresos\Cie10;
use App\Models\Egresos\Constancia;
use App\Models\Egresos\Egreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                'createCertificates' => ueei_tiene_permiso('egresos.certificates.create'),
                'viewHistory' => ueei_tiene_permiso('egresos.history.view'),
                'viewReports' => ueei_tiene_permiso('egresos.reports.view'),
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
        ]);

        $text = trim((string) ($validated['q'] ?? ''));
        $query = Egreso::query()->orderByDesc('fecegr')->orderByDesc('id');

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

    public function monthly(): JsonResponse
    {
        $rows = Egreso::query()
            ->selectRaw('YEAR(fecegr) as anio, MONTH(fecegr) as mes, COUNT(*) as total')
            ->whereNotNull('fecegr')
            ->groupByRaw('YEAR(fecegr), MONTH(fecegr)')
            ->orderByRaw('YEAR(fecegr), MONTH(fecegr)')
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
}
