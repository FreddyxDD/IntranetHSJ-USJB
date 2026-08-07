<?php

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
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

    public function test_html_logout_redirects_to_login_instead_of_rendering_json(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/logout-ueei')
            ->assertRedirect(route('login'));
    }

    public function test_json_logout_preserves_the_async_contract_and_returns_redirect(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->postJson('/logout-ueei')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'success' => true,
                'redirect' => route('login'),
            ]);
    }

    public function test_identity_administration_requires_the_central_session(): void
    {
        $this->get('/admin-ueei')->assertRedirect('/');
        $this->getJson('/api/admin-ueei/usuarios')->assertUnauthorized();
    }

    public function test_portal_routes_are_absent_from_the_legacy_router(): void
    {
        $this->assertFileDoesNotExist(base_path('legacy/index.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/LegacyApplicationController.php'));
    }
}
