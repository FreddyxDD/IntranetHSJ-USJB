<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Identity\CentralAccessService;
use Illuminate\Contracts\View\View;

final class PortalController extends Controller
{
    public function __construct(private readonly CentralAccessService $access) {}

    public function principal(): View
    {
        return view('portal.principal', $this->commonData());
    }

    public function areas(): View
    {
        $data = $this->commonData();
        $data['modulos'] = array_map(function (array $module): array {
            $module['card_class'] = 'card card--'.str_replace('_', '-', (string) $module['codigo']);
            $module['icon_html'] = $this->moduleIcon($module);

            return $module;
        }, $data['modulos']);
        $data['quickAccess'] = array_slice($data['modulos'], 0, 3);

        return view('portal.areas', $data);
    }

    public function profile(): View
    {
        $user = $this->access->user();
        $role = $this->access->isAdministrator() ? 'admin' : 'trabajador';

        return view('portal.profile', [
            'usuario' => [
                'id' => (int) $user?->id,
                'correo' => (string) $user?->email,
                'rol' => $role,
                'rol_texto' => $this->access->isAdministrator() ? 'Administrador' : 'Personal autorizado',
                'estado' => 1,
                'estado_texto' => 'Activo',
                'fecha_creacion' => optional($user?->created_at)->format('Y-m-d H:i:s'),
                'fecha_actualizacion' => optional($user?->updated_at)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function information(): View
    {
        return view('portal.information');
    }

    private function commonData(): array
    {
        $user = $this->access->user();
        $email = (string) $user?->email;
        $role = $this->access->isAdministrator() ? 'admin' : 'trabajador';

        return [
            'correo' => $email,
            'rol' => $role,
            'areaId' => null,
            'modulos' => $this->access->modules(),
            'isAdmin' => $this->access->isAdministrator(),
            'profileLabel' => trim((string) strtok($email, '@')) ?: 'Mi perfil',
            'profileInitial' => mb_strtoupper(mb_substr(trim((string) strtok($email, '@')) ?: 'M', 0, 1)),
        ];
    }

    private function moduleIcon(array $module): string
    {
        $icon = trim((string) ($module['icono'] ?? ''));
        $name = e((string) ($module['nombre'] ?? 'Módulo'));

        if ($icon === '') {
            return '<i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>';
        }

        if (str_starts_with($icon, '/')) {
            return '<img src="'.e($icon).'" alt="'.$name.'" />';
        }

        if (str_starts_with($icon, 'bi ')) {
            return '<i class="'.e($icon).'" aria-hidden="true"></i>';
        }

        return e($icon);
    }
}
