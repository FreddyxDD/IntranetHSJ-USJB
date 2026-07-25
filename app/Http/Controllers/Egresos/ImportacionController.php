<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\Importacion;
use App\Models\Egresos\ImportacionFila;
use App\Services\Egresos\EgresoImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ImportacionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => Importacion::query()->orderByDesc('created_at')->limit(30)->get(),
        ]);
    }

    public function store(Request $request, EgresoImportService $service): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'max:20480', 'extensions:csv,xlsx,dbf'],
        ]);

        $import = $service->preview(
            $validated['archivo'],
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => 'Archivo analizado. Revise los resultados antes de confirmar la carga.',
            'data' => $import,
        ], 201);
    }

    public function show(Request $request, Importacion $importacion): JsonResponse
    {
        $validated = $request->validate([
            'estado' => ['nullable', 'string', 'in:nuevo,reingreso,duplicado,observado,error,insertado'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $rows = ImportacionFila::query()
            ->where('importacion_id', $importacion->id)
            ->when($validated['estado'] ?? null, fn ($query, $status) => $query->where('estado', $status))
            ->orderBy('fila')
            ->paginate(50);

        return response()->json([
            'ok' => true,
            'data' => [
                'importacion' => $importacion,
                'filas' => $rows,
            ],
        ]);
    }

    public function commit(
        Request $request,
        Importacion $importacion,
        EgresoImportService $service
    ): JsonResponse {
        $result = $service->commit(
            $importacion,
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => "Carga confirmada: {$result->insertados} episodio(s) insertado(s).",
            'data' => $result,
        ]);
    }
}
