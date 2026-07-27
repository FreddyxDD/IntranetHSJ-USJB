<?php

namespace Tests\Feature\Portal;

use Tests\TestCase;

final class PortalMigrationTest extends TestCase
{
    public function test_login_is_rendered_by_laravel(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Hospital San José');
    }

    public function test_portal_pages_require_the_central_identity(): void
    {
        foreach (['/principal', '/areas', '/perfil', '/informacion'] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_identity_endpoint_preserves_the_anonymous_contract(): void
    {
        $this->getJson('/me-ueei')
            ->assertUnauthorized()
            ->assertJson([
                'ok' => false,
                'message' => 'No autenticado UEeI',
            ]);
    }

    public function test_portal_routes_are_absent_from_the_legacy_router(): void
    {
        $legacyRouter = file_get_contents(base_path('legacy/index.php'));

        $this->assertIsString($legacyRouter);

        foreach ([
            "\$uri === '/login-ueei'",
            "\$uri === '/crear-cuenta-ueei'",
            "\$uri === '/principal'",
            "\$uri === '/areas'",
            "\$uri === '/perfil'",
            "\$uri === '/informacion'",
        ] as $condition) {
            $this->assertStringNotContainsString($condition, $legacyRouter);
        }
    }
}
