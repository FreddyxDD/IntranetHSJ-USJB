<?php

namespace Tests\Feature\Surgery;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class SurgeryMigrationTest extends TestCase
{
    public function test_surgery_pages_and_apis_require_central_access(): void
    {
        $this->get('/cirugias-login')->assertRedirect('/');
        $this->get('/principal-cirugias')->assertRedirect('/');
        $this->getJson('/cirugias')->assertUnauthorized();
    }

    public function test_surgery_routes_are_declared_by_laravel(): void
    {
        $route = Route::getRoutes()->match(
            Request::create('/principal-cirugias', 'GET'),
        );

        $this->assertSame('surgery.page', $route->getName());
        $this->assertContains('module.access:cirugias', $route->gatherMiddleware());
    }

    public function test_legacy_bridge_and_local_surgery_accounts_were_removed(): void
    {
        $this->assertFileDoesNotExist(base_path('legacy/index.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/LegacyApplicationController.php'));
        $this->assertFileDoesNotExist(app_path('controllers/CirugiasAuthController.php'));
        $this->assertFileDoesNotExist(app_path('helpers/cirugias_auth.php'));
    }

    public function test_surgery_uses_laravel_connections_and_csrf_protection(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Surgery/SurgeryController.php'));
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $view = file_get_contents(resource_path('views/surgery/principal.blade.php'));

        $this->assertStringContainsString("DB::connection('sigh')->getPdo()", $controller);
        $this->assertStringNotContainsString('db_sigh()', $controller);
        $this->assertStringNotContainsString("validateCsrfTokens(except: ['*'])", $bootstrap);
        $this->assertStringContainsString('meta name="csrf-token"', $view);
        $this->assertStringContainsString('assets/js/csrf.js', $view);
    }
}
