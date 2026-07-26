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

    public function test_login_page_explains_validation_and_initial_access(): void
    {
        $html = file_get_contents(base_path('views/ueei/index.php'));

        self::assertStringContainsString('Solo podrás crear la cuenta si tu DNI se encuentra activo en HSJ_Identity.', $html);
        self::assertStringContainsString('Perfil inicial:', $html);
        self::assertStringContainsString('Confirmo que leí y guardé mis datos de acceso.', $html);
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
            $table->string('status');
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
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        $schema->create('access_account_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('role_id');
            $table->dateTime('assigned_at');
            $table->unsignedBigInteger('assigned_by')->nullable();
        });
    }
}
