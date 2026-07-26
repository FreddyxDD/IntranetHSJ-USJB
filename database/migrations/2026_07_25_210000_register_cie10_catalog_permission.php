<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'identity';

    private const CODE = 'egresos.catalogs.manage';

    public function up(): void
    {
        $database = DB::connection('identity');
        $applicationId = $database->table('access_applications')
            ->where('code', 'intranet_hsj')
            ->value('id');

        if (! $applicationId) {
            throw new RuntimeException('No existe la aplicación central intranet_hsj.');
        }

        $database->table('access_permissions')->updateOrInsert(
            ['application_id' => $applicationId, 'code' => self::CODE],
            [
                'name' => 'Administrar catálogo CIE-10',
                'description' => 'CRUD, desactivación y carga masiva controlada del catálogo CIE-10.',
                'module' => 'Egresos',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $permissionId = $database->table('access_permissions')
            ->where('application_id', $applicationId)
            ->where('code', self::CODE)
            ->value('id');
        $roleIds = $database->table('access_roles')
            ->where('application_id', $applicationId)
            ->whereIn('code', ['administrador', 'gestor_egresos'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            $database->table('access_role_permissions')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $database = DB::connection('identity');
        $applicationId = $database->table('access_applications')
            ->where('code', 'intranet_hsj')
            ->value('id');
        $permissionId = $database->table('access_permissions')
            ->where('application_id', $applicationId)
            ->where('code', self::CODE)
            ->value('id');

        if ($permissionId) {
            $database->table('access_role_permissions')->where('permission_id', $permissionId)->delete();
            $database->table('access_permissions')->where('id', $permissionId)->delete();
        }
    }
};
