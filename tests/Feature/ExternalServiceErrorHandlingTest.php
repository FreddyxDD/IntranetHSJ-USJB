<?php

namespace Tests\Feature;

use App\Http\Controllers\Appointments\AppointmentApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ExternalServiceErrorHandlingTest extends TestCase
{
    public function test_error_screen_uses_the_animated_mascot(): void
    {
        $html = view('errors.service-unavailable', [
            'message' => 'Servicio temporalmente no disponible.',
            'reference' => 'INTRA-SIGH-TEST',
        ])->render();

        $this->assertStringContainsString('hsj-bullterrier-error.gif', $html);
        $this->assertStringContainsString('hsj-dog-chase', $html);
        $this->assertStringContainsString('hsj-error-escape', $html);
        $this->assertStringContainsString('INTRA-SIGH-TEST', $html);
        $this->assertStringNotContainsString('SQLSTATE', $html);
    }

    public function test_appointment_api_does_not_expose_database_details(): void
    {
        DB::shouldReceive('connection')
            ->once()
            ->with('sigh')
            ->andThrow(new RuntimeException('SQLSTATE[08001] TCP Provider: Host desconocido.'));

        $response = app(AppointmentApiController::class)->daily(
            Request::create('/api/citas-admin/citas-diarias', 'GET'),
        );
        $payload = $response->getData(true);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertFalse($payload['ok']);
        $this->assertArrayHasKey('reference', $payload);
        $this->assertStringNotContainsString('SQLSTATE', json_encode($payload));
        $this->assertStringNotContainsString('Host desconocido', json_encode($payload));
        $this->assertMatchesRegularExpression('/^INTRA-CITAS-\d{14}-[A-Z0-9]{6}$/', $payload['reference']);
    }

    public function test_migrated_api_and_frontend_strip_debug_messages(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Appointments/AppointmentAdminController.php'));
        $javascript = file_get_contents(base_path('public/assets/js/citasadmin.js'));

        $this->assertStringContainsString("unset(\$payload['debug'])", $controller);
        $this->assertStringNotContainsString('result.debug', $javascript);
    }
}
