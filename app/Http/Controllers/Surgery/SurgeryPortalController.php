<?php

namespace App\Http\Controllers\Surgery;

use App\Http\Controllers\Controller;
use App\Services\Identity\CentralAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class SurgeryPortalController extends Controller
{
    public function __construct(private readonly CentralAccessService $access) {}

    public function entry(): RedirectResponse
    {
        return redirect('/principal-cirugias');
    }

    public function page(): View
    {
        $user = $this->access->user();
        $administrator = $this->access->isAdministrator();

        return view('surgery.principal', [
            'usuario' => $user?->name ?? '',
            'correo' => $user?->email ?? '',
            'rol' => $administrator ? 0 : 1,
            'esAdmin' => $administrator,
            'puedeAnalisis' => $this->access->hasPermission('cirugias.analytics.view'),
            'puedeReportes' => $this->access->hasPermission('cirugias.reports.view'),
            'puedeGestionarRegistros' => $this->access->hasPermission('cirugias.records.manage'),
            'puedeImportar' => $this->access->hasPermission('cirugias.imports.manage'),
            'puedeGestionarPersonal' => $this->access->hasPermission('cirugias.staff.manage'),
        ]);
    }

    public function manual(): View
    {
        return view('surgery.manual');
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'ok' => true,
            'usuario' => $this->access->user()?->email,
            'rol' => $this->access->isAdministrator() ? 0 : 1,
        ]);
    }

    public function leave(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'ok' => true,
            'message' => 'Salida del módulo de Cirugías registrada.',
            'redirect' => url('/areas'),
        ]);
    }

    public function administration(): RedirectResponse
    {
        return redirect('/admin-ueei');
    }
}
