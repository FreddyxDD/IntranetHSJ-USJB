<?php

namespace Tests\Feature;

use Tests\TestCase;

final class IntranetEntryPointTest extends TestCase
{
    public function test_existing_login_view_is_served_by_laravel(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Hospital San José');
    }

    public function test_appointments_api_requires_the_existing_intranet_session(): void
    {
        $this->getJson('/api/citas-admin/citas-diarias?fechaInicio=2026-07-21')
            ->assertUnauthorized()
            ->assertJson(['ok' => false]);
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
