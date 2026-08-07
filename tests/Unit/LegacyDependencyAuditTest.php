<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class LegacyDependencyAuditTest extends TestCase
{
    private const REMOVED_SYMBOLS = [
        'normalize_email(',
        'ueei_tiene_permiso(',
        'ueei_usuario_es_admin(',
        'modulos_autorizados(',
        'modulo_autorizado(',
        'require_permiso_api(',
        'require_modulo(',
        'require_modulo_api(',
        'json_response(',
        'get_json_input(',
        'cirugias_require_',
        'db_sigh(',
        'db_citas(',
        'url_path(',
        'app_base(',
    ];

    public function test_active_application_has_no_calls_to_removed_php_helpers(): void
    {
        foreach ([app_path(), base_path('routes'), resource_path('views')] as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                $source = file_get_contents($file);

                foreach (self::REMOVED_SYMBOLS as $symbol) {
                    self::assertStringNotContainsString(
                        $symbol,
                        $source,
                        "Dependencia retirada [{$symbol}] encontrada en {$file}",
                    );
                }
            }
        }
    }

    public function test_all_controller_routes_reference_existing_actions(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);
            self::assertTrue(method_exists($controller, $method), "Acción inexistente: {$action}");
        }
    }

    public function test_fortify_login_url_returns_to_the_institutional_login(): void
    {
        $route = Route::getRoutes()->match(Request::create('/login', 'GET'));

        self::assertSame('institutional.login.compatibility', $route->getName());
        $this->get('/login')->assertRedirect('/');

        $logoutRoute = Route::getRoutes()->match(Request::create('/logout', 'POST'));
        self::assertSame('institutional.logout.compatibility', $logoutRoute->getName());
    }

    /** @return iterable<string> */
    private function phpFiles(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php'], true)) {
                yield $file->getPathname();
            }
        }
    }
}
