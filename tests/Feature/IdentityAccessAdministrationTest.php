<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\InstitutionalSession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class IdentityAccessAdministrationTest extends TestCase
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
        $this->seedIdentity();

        app(InstitutionalSession::class)->start();
        $_SESSION['ueei_id'] = 1;
        $_SESSION['ueei_correo'] = 'admin@hsj.test';
        $_SESSION['account_confirmation_pending'] = false;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        DB::purge('identity');
        config()->set('database.connections.identity', $this->originalIdentityConnection);
        parent::tearDown();
    }

    public function test_personal_data_cannot_be_changed_from_the_access_portal(): void
    {
        $this->putJson('/api/admin-ueei/usuarios/2', [
            'correo' => 'alterado@hsj.test',
            'area_id' => 11,
            'modulos' => [2],
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Los datos personales son de solo lectura y deben actualizarse desde Legajos.');

        self::assertSame('persona@hsj.test', DB::connection('identity')->table('users')->where('id', 2)->value('email'));
        self::assertSame('Persona Legajos', DB::connection('identity')->table('users')->where('id', 2)->value('name'));
    }

    public function test_modules_can_be_assigned_independently_from_the_application_role(): void
    {
        $this->putJson('/api/admin-ueei/usuarios/2', [
            'area_id' => 11,
            'modulos' => [2, 3],
        ])->assertOk()
            ->assertJsonPath('data.modulo_ids.0', 2)
            ->assertJsonPath('data.modulo_ids.1', 3);

        $overrides = DB::connection('identity')->table('access_account_permission_overrides')->get();
        self::assertCount(1, $overrides);
        self::assertTrue((bool) $overrides->sole()->is_granted);

        $user = User::query()->with([
            'accessAccount.roles.application',
            'accessAccount.roles.permissions.application',
            'accessAccount.permissionOverrides.application',
        ])->findOrFail(2);

        self::assertTrue($user->hasPermission('dashboard.view'));
        self::assertTrue($user->hasPermission('cirugias.view'));
        self::assertFalse($user->hasPermission('egresos.view'));

        $this->putJson('/api/admin-ueei/usuarios/2', [
            'area_id' => 11,
            'modulos' => [],
        ])->assertOk()->assertJsonPath('data.modulo_ids', []);

        $user = User::query()->with([
            'accessAccount.roles.application',
            'accessAccount.roles.permissions.application',
            'accessAccount.permissionOverrides.application',
        ])->findOrFail(2);

        self::assertFalse($user->hasPermission('dashboard.view'));
        self::assertFalse($user->hasPermission('cirugias.view'));
        self::assertFalse((bool) DB::connection('identity')
            ->table('access_account_permission_overrides')
            ->where('account_id', 2)
            ->sole()
            ->is_granted);
    }

    public function test_manual_account_creation_is_rejected_in_favor_of_legajos_identity(): void
    {
        $this->postJson('/api/admin-ueei/usuarios', [
            'correo' => 'manual@hsj.test',
        ])->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Las cuentas se crean validando el DNI y los datos personales de Legajos. Este panel solo administra accesos.']);
    }

    private function createIdentitySchema(): void
    {
        $schema = Schema::connection('identity');
        $schema->create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
        });
        $schema->create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('registration_document_number')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('rol');
            $table->string('tipo_usuario');
            $table->boolean('activo');
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
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        $schema->create('access_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('code');
            $table->string('name');
            $table->string('module');
            $table->timestamps();
        });
        $schema->create('access_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('username');
            $table->string('email')->nullable();
            $table->string('display_name');
            $table->string('status');
            $table->boolean('must_change_password');
            $table->timestamps();
        });
        $schema->create('access_account_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('role_id');
            $table->dateTime('assigned_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
        });
        $schema->create('access_role_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });
        $schema->create('access_account_permission_overrides', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('permission_id');
            $table->boolean('is_granted');
            $table->dateTime('assigned_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
        });
    }

    private function seedIdentity(): void
    {
        $identity = DB::connection('identity');
        $identity->table('access_applications')->insert([
            'id' => 2,
            'code' => 'intranet_hsj',
            'is_active' => true,
        ]);
        $identity->table('access_roles')->insert([
            ['id' => 10, 'application_id' => 2, 'code' => 'administrador', 'name' => 'Administrador'],
            ['id' => 11, 'application_id' => 2, 'code' => 'consulta', 'name' => 'Consulta'],
        ]);
        $identity->table('people')->insert([
            ['id' => 1, 'phone' => null, 'birth_date' => '1990-01-01'],
            ['id' => 2, 'phone' => '999999999', 'birth_date' => '1992-02-02'],
        ]);
        $identity->table('users')->insert([
            [
                'id' => 1, 'person_id' => 1, 'registration_document_number' => '11111111',
                'name' => 'Administrador', 'email' => 'admin@hsj.test', 'password' => 'hash',
                'rol' => 'administrador', 'tipo_usuario' => 'administrativo', 'activo' => true,
            ],
            [
                'id' => 2, 'person_id' => 2, 'registration_document_number' => '22222222',
                'name' => 'Persona Legajos', 'email' => 'persona@hsj.test', 'password' => 'hash',
                'rol' => 'consulta', 'tipo_usuario' => 'asistencial', 'activo' => true,
            ],
        ]);
        $identity->table('access_accounts')->insert([
            [
                'id' => 1, 'user_id' => 1, 'person_id' => 1, 'username' => '11111111',
                'email' => 'admin@hsj.test', 'display_name' => 'Administrador',
                'status' => 'active', 'must_change_password' => false,
            ],
            [
                'id' => 2, 'user_id' => 2, 'person_id' => 2, 'username' => '22222222',
                'email' => 'persona@hsj.test', 'display_name' => 'Persona Legajos',
                'status' => 'active', 'must_change_password' => false,
            ],
        ]);
        $identity->table('access_account_roles')->insert([
            ['account_id' => 1, 'role_id' => 10, 'assigned_at' => now()],
            ['account_id' => 2, 'role_id' => 11, 'assigned_at' => now()],
        ]);

        $permissions = collect(config('modules.catalog'))->values()->map(
            fn (array $module, int $index): array => [
                'id' => $index + 1,
                'application_id' => 2,
                'code' => $module['permission'],
                'name' => $module['nombre'],
                'module' => $module['codigo'],
            ]
        )->all();
        $identity->table('access_permissions')->insert($permissions);
        $dashboardPermissionId = collect($permissions)->firstWhere('code', 'dashboard.view')['id'];
        $identity->table('access_role_permissions')->insert([
            'role_id' => 11,
            'permission_id' => $dashboardPermissionId,
        ]);
    }
}
