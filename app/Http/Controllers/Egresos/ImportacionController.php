<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\Importacion;
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

        $import = $service->import(
            $validated['archivo'],
            EgresoController::centralActor(),
            $request
        );

        return response()->json([
            'ok' => true,
            'message' => 'Importación procesada. Revise el resumen y las observaciones.',
            'data' => $import,
        ], 201);
    }
}
