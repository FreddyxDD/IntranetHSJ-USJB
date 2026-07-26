<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\Cie10;
use App\Models\Egresos\Cie10Importacion;
use App\Models\Egresos\Cie10ImportacionFila;
use App\Services\Egresos\Cie10CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class Cie10CatalogController extends Controller
{
    public function page(): View
    {
        return view('egresos.cie10', [
            'centralUser' => [
                'name' => (string) ($_SESSION['ueei_nombre'] ?? 'Usuario'),
                'email' => (string) ($_SESSION['ueei_correo'] ?? ''),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'in:ACTIVO,INACTIVO'],
            'cotejo_sexo' => ['nullable', 'string', 'in:AMBOS,HOMBRE,MUJER'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $text = trim((string) ($validated['q'] ?? ''));
        $query = Cie10::query()->orderBy('codigo');
        if ($text !== '') {
            $escaped = str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $text);
            $query->where(function ($builder) use ($escaped): void {
                $builder->where('codigo', 'like', $escaped.'%')
                    ->orWhere('descripcion', 'like', '%'.$escaped.'%');
            });
        }
        $query
            ->when($validated['estado'] ?? null, fn ($builder, $value) => $builder->where('estado', $value))
            ->when($validated['cotejo_sexo'] ?? null, fn ($builder, $value) => $builder->where('cotejo_sexo', $value));
        $page = $query->paginate(25);

        return response()->json([
            'ok' => true,
            'data' => collect($page->items())->map(fn (Cie10 $row): array => $this->catalogRow($row)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(Request $request, Cie10CatalogService $service): JsonResponse
    {
        $cie10 = $service->create(
            $request->only(['codigo', 'descripcion', 'estado', 'cotejo_sexo']),
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => 'Código CIE-10 creado y auditado.',
            'data' => $this->catalogRow($cie10),
        ], 201);
    }

    public function update(
        Request $request,
        Cie10 $cie10,
        Cie10CatalogService $service
    ): JsonResponse {
        $validated = $request->validate(['version' => ['required', 'string', 'size:64']]);
        $updated = $service->update(
            $cie10,
            $request->only(['descripcion', 'estado', 'cotejo_sexo']),
            $validated['version'],
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => 'Código CIE-10 actualizado y auditado.',
            'data' => $this->catalogRow($updated),
        ]);
    }

    public function destroy(
        Request $request,
        Cie10 $cie10,
        Cie10CatalogService $service
    ): JsonResponse {
        $validated = $request->validate(['version' => ['required', 'string', 'size:64']]);
        $updated = $service->deactivate(
            $cie10,
            $validated['version'],
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => 'Código desactivado. Se conservó para la trazabilidad clínica.',
            'data' => $this->catalogRow($updated),
        ]);
    }

    public function imports(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => Cie10Importacion::query()->latest('id')->limit(20)->get(),
        ]);
    }

    public function previewImport(Request $request, Cie10CatalogService $service): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'max:20480', 'extensions:csv,xlsx'],
        ]);
        $batch = $service->previewImport(
            $validated['archivo'],
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => 'Archivo analizado. Ningún código fue modificado todavía.',
            'data' => $batch,
        ], 201);
    }

    public function showImport(Request $request, Cie10Importacion $importacion): JsonResponse
    {
        $validated = $request->validate([
            'estado' => ['nullable', 'string', 'in:nuevo,actualizar,sin_cambios,error,insertado,actualizado'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $rows = Cie10ImportacionFila::query()
            ->where('importacion_id', $importacion->id)
            ->when($validated['estado'] ?? null, fn ($query, $status) => $query->where('estado', $status))
            ->orderBy('fila')
            ->paginate(50);

        return response()->json([
            'ok' => true,
            'data' => ['importacion' => $importacion, 'filas' => $rows],
        ]);
    }

    public function confirmImport(
        Request $request,
        Cie10Importacion $importacion,
        Cie10CatalogService $service
    ): JsonResponse {
        $batch = $service->confirmImport(
            $importacion,
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => "Carga confirmada: {$batch->nuevos} nuevos y {$batch->actualizaciones} actualizados.",
            'data' => $batch,
        ]);
    }

    private function catalogRow(Cie10 $row): array
    {
        return [
            'id' => (int) $row->id,
            'codigo' => $row->codigo,
            'descripcion' => $row->descripcion,
            'estado' => $row->estado,
            'cotejo_sexo' => $row->cotejo_sexo,
            'source_system' => $row->source_system,
            'version' => $row->source_fingerprint,
            'updated_at' => optional($row->updated_at)->toIso8601String(),
        ];
    }
}
