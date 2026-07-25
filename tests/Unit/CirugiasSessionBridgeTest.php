<?php

namespace Tests\Unit;

use App\Support\CirugiasSessionBridge;
use PHPUnit\Framework\TestCase;

final class CirugiasSessionBridgeTest extends TestCase
{
    public function test_central_administrator_enters_cirugias_without_a_second_login(): void
    {
        $session = [
            'ueei_id' => 2,
            'ueei_correo' => 'admin@example.test',
            'ueei_nombre' => 'Administrador HSJ',
            'ueei_rol' => 'admin',
            'identity_roles' => ['administrador'],
        ];

        self::assertTrue(CirugiasSessionBridge::sync($session));
        self::assertSame(2, $session['cirugias_usuario_id']);
        self::assertSame('Administrador HSJ', $session['cirugias_usuario']);
        self::assertSame(0, $session['cirugias_rol']);
        self::assertSame('/cirugias-admin', CirugiasSessionBridge::destination($session));
    }

    public function test_authorized_non_admin_keeps_operator_scope(): void
    {
        $session = [
            'ueei_id' => 8,
            'ueei_correo' => 'cirugias@example.test',
            'ueei_rol' => 'consulta',
            'identity_roles' => ['consulta_cirugias'],
        ];

        self::assertTrue(CirugiasSessionBridge::sync($session));
        self::assertSame(1, $session['cirugias_rol']);
        self::assertSame('/principal-cirugias', CirugiasSessionBridge::destination($session));
    }

    public function test_bridge_rejects_an_incomplete_central_session(): void
    {
        $session = [];

        self::assertFalse(CirugiasSessionBridge::sync($session));
        self::assertArrayNotHasKey('cirugias_usuario', $session);
    }
}
