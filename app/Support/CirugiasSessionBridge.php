<?php

namespace App\Support;

final class CirugiasSessionBridge
{
    public static function sync(array &$session): bool
    {
        if (empty($session['ueei_id']) || empty($session['ueei_correo'])) {
            return false;
        }

        $roles = is_array($session['identity_roles'] ?? null)
            ? $session['identity_roles']
            : [];
        $isAdmin = ($session['ueei_rol'] ?? null) === 'admin'
            || in_array('administrador', $roles, true);

        $session['cirugias_id'] = (int) $session['ueei_id'];
        $session['cirugias_usuario_id'] = (int) $session['ueei_id'];
        $session['cirugias_usuario'] = (string) (
            $session['ueei_nombre']
            ?? $session['ueei_correo']
        );
        $session['cirugias_rol'] = $isAdmin ? 0 : 1;

        return true;
    }

    public static function destination(array $session): string
    {
        return '/principal-cirugias';
    }
}
