<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'identity';

    private const READER_PERMISSIONS = [
        'cirugias.view',
        'cirugias.analytics.view',
        'cirugias.reports.view',
    ];

    private const MANAGER_PERMISSIONS = [
        'cirugias.view',
        'cirugias.analytics.view',
        'cirugias.reports.view',
        'cirugias.records.manage',
        'cirugias.imports.manage',
        'cirugias.staff.manage',
    ];

    public function up(): void
    {
        $database = DB::connection('identity');
        $applicationId = $database->table('access_applications')
            ->where('code', 'intranet_hsj')
            ->value('id');

        if (! $applicationId) {
            throw new RuntimeException('No existe la aplicación central intranet_hsj en HSJ_Identity.');
        }

        $readerRoleId = $database->table('access_roles')
            ->where('application_id', $applicationId)
            ->where('code', 'cirugias')
            ->value('id');

        if (! $readerRoleId) {
            throw new RuntimeException('No existe el perfil central cirugias en HSJ_Identity.');
        }

        $database->table('access_roles')->updateOrInsert(
            ['application_id' => $applicationId, 'code' => 'gestor_cirugias'],
            [
                'name' => 'Gestor de Cirugías',
                'description' => 'Gestión operativa completa del módulo de Cirugías.',
                'is_system' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $managerRoleId = $database->table('access_roles')
            ->where('application_id', $applicationId)
            ->where('code', 'gestor_cirugias')
            ->value('id');

        $this->syncPermissions($database, (int) $readerRoleId, $applicationId, self::READER_PERMISSIONS);
        $this->syncPermissions($database, (int) $managerRoleId, $applicationId, self::MANAGER_PERMISSIONS);
    }

    public function down(): void
    {
        $database = DB::connection('identity');
        $applicationId = $database->table('access_applications')
            ->where('code', 'intranet_hsj')
            ->value('id');
        $managerRoleId = $database->table('access_roles')
            ->where('application_id', $applicationId)
            ->where('code', 'gestor_cirugias')
            ->value('id');

        if (! $managerRoleId) {
            return;
        }

        $database->table('access_role_permissions')
            ->where('role_id', $managerRoleId)
            ->delete();

        $hasAccounts = $database->table('access_account_roles')
            ->where('role_id', $managerRoleId)
            ->exists();

        if (! $hasAccounts) {
            $database->table('access_roles')->where('id', $managerRoleId)->delete();
        }
    }

    private function syncPermissions($database, int $roleId, int|string $applicationId, array $codes): void
    {
        $permissionIds = $database->table('access_permissions')
            ->where('application_id', $applicationId)
            ->whereIn('code', $codes)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $database->table('access_role_permissions')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
};
