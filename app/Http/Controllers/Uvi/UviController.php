<?php

namespace App\Http\Controllers\Uvi;

use App\Http\Controllers\Controller;
use App\Services\Identity\CentralAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class UviController extends Controller
{
    public function __construct(private readonly CentralAccessService $access) {}

    public function index(): RedirectResponse
    {
        return redirect($this->access->isAdministrator() ? '/admin-ueei' : '/principal');
    }

    public function retiredLocalAccounts(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Las cuentas locales de UVI fueron retiradas. Administra el acceso mediante los perfiles centrales.',
        ], 410);
    }
}
