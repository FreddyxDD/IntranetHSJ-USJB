<?php

namespace Tests\Feature\Appointments;

use Tests\TestCase;

final class AppointmentAdminMigrationTest extends TestCase
{
    public function test_appointment_admin_routes_require_central_access(): void
    {
        $this->get('/citas-admin')->assertRedirect('/');
        $this->getJson('/api/citas-admin/registros')->assertUnauthorized();
        $this->getJson('/api/citas-admin/reportes')->assertUnauthorized();
    }

    public function test_appointment_admin_is_absent_from_the_legacy_router(): void
    {
        $legacyRouter = file_get_contents(base_path('legacy/index.php'));

        $this->assertIsString($legacyRouter);
        $this->assertStringNotContainsString("\$uri === '/citas-admin'", $legacyRouter);
        $this->assertStringNotContainsString("\$uri === '/api/citas-admin/registros'", $legacyRouter);
        $this->assertStringNotContainsString('CitasAdminController', $legacyRouter);
    }
}
