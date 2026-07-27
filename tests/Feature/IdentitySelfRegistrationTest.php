<?php

namespace Tests\Feature;

use App\Services\Identity\SelfRegistrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class IdentitySelfRegistrationTest extends TestCase
{
    private array $originalIdentityConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalIdentityConnection = config('database.connections.identity');
        config()->set('database.connections.identity', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('identity');
        $this->createIdentitySchema();
    }

    protected function tearDown(): void
    {
        DB::purge('identity');
        config()->set('database.connections.identity', $this->originalIdentityConnection);
        parent::tearDown();
    }

    public function test_initial_password_uses_birth_date_and_last_four_dni_digits(): void
    {
        self::assertSame(
            '050319905678',
            SelfRegistrationService::initialPassword('1990-03-05', '12345678')
        );
    }

    public function test_active_identity_creates_a_central_query_account(): void
    {
        $identity = DB::connection('identity');
        $identity->table('personnel_document_types')->insert(['id' => 1, 'code' => 'DNI']);
        $identity->table('people')->insert([
            'id' => 40,
            'document_type_id' => 1,
            'document_number' => '12345678',
            'names' => 'Freddy Richard',
            'paternal_last_name' => 'Mondalgo',
            'maternal_last_name' => 'Castilla',
            'birth_date' => '1990-03-05',
            'email' => null,
            'status' => 'active',
        ]);
        $identity->table('personnel_records')->insert([
            'id' => 80,
            'document_type_id' => 1,
            'document_number' => '12345678',
            'is_active' => true,
            'source_registered_at' => '2026-01-01 08:00:00',
        ]);
        $identity->table('access_applications')->insert([
            'id' => 2,
            'code' => 'intranet_hsj',
            'is_active' => true,
        ]);
        $identity->table('access_applications')->insert([
            'id' => 3,
            'code' => 'legajos_hsj',
            'is_active' => true,
        ]);
        $identity->table('access_roles')->insert([
            'id' => 14,
            'application_id' => 2,
            'code' => 'consulta',
        ]);

        $result = app(SelfRegistrationService::class)->createAccount('12345678');
        $user = $result['user']->fresh('accessAccount.roles');

        self::assertSame('12345678', $user->registration_document_number);
        self::assertSame(SelfRegistrationService::REGISTRATION_SOURCE, $user->registration_source);
        self::assertTrue(Hash::check('050319905678', $user->password));
        self::assertSame('12345678', $user->accessAccount->username);
        self::assertSame('consulta', $user->accessAccount->roles->sole()->code);
        self::assertNull($user->accessAccount->registration_instructions_acknowledged_at);
    }

    public function test_unknown_dni_creates_a_pending_person_and_account_for_admin_approval(): void
    {
        $identity = DB::connection('identity');
        $identity->table('personnel_document_types')->insert(['id' => 1, 'code' => 'DNI']);
        $identity->table('access_applications')->insert([
            'id' => 2,
            'code' => 'intranet_hsj',
            'is_active' => true,
        ]);
        $identity->table('access_roles')->insert([
            'id' => 14,
            'application_id' => 2,
            'code' => 'consulta',
        ]);

        $lookup = app(SelfRegistrationService::class)->lookupDni('87654321');
        self::assertFalse($lookup['found']);

        $result = app(SelfRegistrationService::class)->createPendingAccount([
            'dni' => '87654321',
            'names' => 'Ana María',
            'paternal_last_name' => 'Pérez',
            'maternal_last_name' => 'López',
            'birth_date' => '1992-11-10',
            'email' => 'ana.perez@example.com',
            'phone' => '987654321',
        ]);
        $user = $result['user']->fresh(['person', 'accessAccount.roles']);

        self::assertFalse($user->activo);
        self::assertSame('pending', $user->person->status);
        self::assertSame('self_registration', $user->person->data_origin);
        self::assertSame('pending', $user->accessAccount->status);
        self::assertSame('consulta', $user->accessAccount->roles->sole()->code);
        self::assertNull($user->accessAccount->approved_at);
        self::assertTrue(Hash::check('101119924321', $user->password));

        $approved = app(SelfRegistrationService::class)->approvePendingAccount($user, 99);

        self::assertTrue($approved->activo);
        self::assertSame('active', $approved->person->status);
        self::assertSame('active', $approved->accessAccount->status);
        self::assertSame(99, (int) $approved->accessAccount->approved_by);
        self::assertNotNull($approved->accessAccount->approved_at);
        self::assertNull($approved->accessAccount->registration_instructions_acknowledged_at);
    }

    public function test_inactive_identity_without_employment_is_sent_to_legajos_without_creating_account(): void
    {
        $identity = DB::connection('identity');
        $identity->table('personnel_document_types')->insert(['id' => 1, 'code' => 'DNI']);
        $identity->table('access_applications')->insert([
            'id' => 2,
            'code' => 'intranet_hsj',
            'is_active' => true,
        ]);
        $identity->table('access_applications')->insert([
            'id' => 3,
            'code' => 'legajos_hsj',
            'is_active' => true,
        ]);
        $identity->table('access_roles')->insert([
            'id' => 14,
            'application_id' => 2,
            'code' => 'consulta',
        ]);
        $identity->table('people')->insert([
            'id' => 956,
            'document_type_id' => 1,
            'document_number' => '43243226',
            'names' => 'MONICA ELIANA',
            'paternal_last_name' => 'CARBAJAL',
            'maternal_last_name' => 'ZAIRA',
            'birth_date' => '1985-08-27',
            'status' => 'inactive',
            'data_origin' => 'personnel_source',
        ]);

        $lookup = app(SelfRegistrationService::class)->lookupDni('43243226');

        self::assertSame('personnel_review', $lookup['registration_mode']);
        self::assertSame(956, $lookup['person_id']);

        $result = app(SelfRegistrationService::class)->createPersonnelReviewRequest([
            'dni' => '43243226',
            'names' => 'Mónica Eliana',
            'paternal_last_name' => 'Carbajal',
            'maternal_last_name' => 'Zaira',
            'birth_date' => '1985-08-27',
            'email' => 'monica.carbajal@example.com',
            'phone' => '956123456',
            'request_reason' => 'Solicito que Legajos evalúe si corresponde registrar un nuevo vínculo laboral.',
        ]);

        self::assertSame(1, $identity->table('people')->where('document_number', '43243226')->count());
        self::assertSame(956, $result['person_id']);
        self::assertSame('inactive', $identity->table('people')->where('id', 956)->value('status'));
        self::assertSame('personnel_source', $identity->table('people')->where('id', 956)->value('data_origin'));
        self::assertSame(0, $identity->table('users')->where('registration_document_number', '43243226')->count());
        self::assertSame(0, $identity->table('access_accounts')->where('username', '43243226')->count());
        self::assertSame('pending', $identity->table('personnel_review_requests')->where('id', $result['request_id'])->value('status'));
        self::assertSame(3, (int) $identity->table('personnel_review_requests')->where('id', $result['request_id'])->value('target_application_id'));
    }

    public function test_login_page_explains_validation_and_initial_access(): void
    {
        $html = file_get_contents(resource_path('views/auth/login.blade.php'));

        self::assertStringContainsString('Si tu DNI existe y tiene vínculo activo, la cuenta se activará automáticamente.', $html);
        self::assertStringContainsString('Perfil inicial:', $html);
        self::assertStringContainsString('Confirmo que leí y guardé mis datos de acceso.', $html);
        self::assertStringContainsString('Completa tus datos personales', $html);
        self::assertStringContainsString('Pendiente de aprobación', $html);
    }

    private function createIdentitySchema(): void
    {
        $schema = Schema::connection('identity');

        $schema->create('personnel_document_types', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code');
        });
        $schema->create('people', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('document_type_id')->nullable();
            $table->string('document_number');
            $table->string('names');
            $table->string('paternal_last_name')->nullable();
            $table->string('maternal_last_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status');
            $table->string('data_origin')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();
        });
        $schema->create('personnel_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('document_type_id')->nullable();
            $table->string('document_number');
            $table->boolean('is_active');
            $table->dateTime('source_registered_at')->nullable();
        });
        $schema->create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('personnel_record_id')->nullable();
            $table->string('registration_document_number')->nullable();
            $table->string('registration_source');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('rol');
            $table->string('tipo_usuario');
            $table->boolean('activo');
            $table->rememberToken();
            $table->timestamps();
        });
        $schema->create('access_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->boolean('is_active');
        });
        $schema->create('access_roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('code');
            $table->string('name')->nullable();
            $table->timestamps();
        });
        $schema->create('access_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('personnel_record_id')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('display_name');
            $table->string('status');
            $table->boolean('must_change_password');
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('registration_instructions_acknowledged_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        $schema->create('access_account_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('role_id');
            $table->dateTime('assigned_at');
            $table->unsignedBigInteger('assigned_by')->nullable();
        });
        $schema->create('personnel_review_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('target_application_id');
            $table->string('document_number');
            $table->string('request_type');
            $table->string('status');
            $table->string('submitted_names');
            $table->string('submitted_paternal_last_name');
            $table->string('submitted_maternal_last_name');
            $table->date('submitted_birth_date');
            $table->string('submitted_email');
            $table->string('submitted_phone');
            $table->text('request_reason');
            $table->text('identity_snapshot')->nullable();
            $table->dateTime('requested_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }
}
