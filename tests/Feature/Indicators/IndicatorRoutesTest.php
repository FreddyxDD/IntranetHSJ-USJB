<?php

namespace Tests\Feature\Indicators;

use Tests\TestCase;

final class IndicatorRoutesTest extends TestCase
{
    public function test_indicator_pages_require_the_central_session(): void
    {
        foreach (['/produccion', '/eficiencia', '/calidad'] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_indicator_apis_reject_anonymous_requests_without_exposing_details(): void
    {
        foreach ([
            '/indicadores/produccion',
            '/indicadores/eficiencia',
            '/indicadores/calidad',
        ] as $path) {
            $this->getJson($path)
                ->assertUnauthorized()
                ->assertExactJson([
                    'ok' => false,
                    'message' => 'Sesión no iniciada.',
                ]);
        }
    }

    public function test_indicator_routes_are_no_longer_declared_in_the_legacy_router(): void
    {
        $this->assertFileDoesNotExist(base_path('legacy/index.php'));
    }

    public function test_uvi_local_account_routes_are_no_longer_declared_in_the_legacy_router(): void
    {
        $this->assertFileDoesNotExist(base_path('legacy/index.php'));
        $this->get('/uvi-login')->assertRedirect('/');
    }
}
