<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'identity';

    private const ROLE_PERMISSIONS = [
        'consulta_egresos' => [
            'egresos.view',
            'egresos.records.view',
            'egresos.history.view',
        ],
        'operador_egresos' => [
            'egresos.view',
            'egresos.records.view',
            'egresos.records.create',
            'egresos.records.update',
            'egresos.certificates.create',
            'egresos.certificates.update',
            'egresos.history.view',
            'egresos.reports.view',
        ],
        'gestor_egresos' => [
            'egresos.view',
            'egresos.records.view',
            'egresos.records.create',
            'egresos.records.update',
            'egresos.imports.manage',
            'egresos.certificates.create',
            'egresos.certificates.update',
            'egresos.certificates.cancel',
            'egresos.history.view',
            'egresos.reports.view',
            'egresos.configuration.manage',
            'egresos.audit.view',
        ],
    ];

    private const ROLE_NAMES = [
        'consulta_egresos' => 'Consulta de Egresos',
        'operador_egresos' => 'Operador de Egresos',
        'gestor_egresos' => 'Gestor de Egresos',
    ];

    public function up(): void
    {
        $database = DB::connection('identity');
        $applicationId = $database->table('access_applications')
            ->where('code', 'intranet_hsj')
            ->value('id');

        if (! $applicationId) {
            throw new RuntimeException(
                'No existe la aplicación central intranet_hsj en HSJ_Identity.'
            );
        }

        foreach (self::ROLE_PERMISSIONS as $roleCode => $permissionCodes) {
            $database->table('access_roles')->updateOrInsert(
                ['application_id' => $applicationId, 'code' => $roleCode],
                [
                    'name' => self::ROLE_NAMES[$roleCode],
                    'description' => $this->roleDescription($roleCode),
                    'is_system' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $roleId = $database->table('access_roles')
                ->where('application_id', $applicationId)
                ->where('code', $roleCode)
                ->value('id');

            $permissionIds = $database->table('access_permissions')
                ->where('application_id', $applicationId)
                ->whereIn('code', $permissionCodes)
                ->pluck('id');

            if ($permissionIds->count() !== count($permissionCodes)) {
                throw new RuntimeException(
                    "No están registrados todos los permisos del perfil {$roleCode}."
                );
            }

            $database->table('access_role_permissions')
                ->where('role_id', $roleId)
                ->delete();

            foreach ($permissionIds as $permissionId) {
                $database->table('access_role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $database = DB::connection('identity');
        $applicationId = $database->table('access_applications')
            ->where('code', 'intranet_hsj')
            ->value('id');

        if (! $applicationId) {
            return;
        }

        $roleIds = $database->table('access_roles')
            ->where('application_id', $applicationId)
            ->whereIn('code', array_keys(self::ROLE_PERMISSIONS))
            ->pluck('id');

        $database->table('access_account_roles')
            ->whereIn('role_id', $roleIds)
            ->delete();
        $database->table('access_role_permissions')
            ->whereIn('role_id', $roleIds)
            ->delete();
        $database->table('access_roles')
            ->whereIn('id', $roleIds)
            ->delete();
    }

    private function roleDescription(string $roleCode): string
    {
        return match ($roleCode) {
            'consulta_egresos' => 'Consulta de egresos e historial sin capacidades de modificación.',
            'operador_egresos' => 'Registro, corrección y emisión de constancias de egreso.',
            'gestor_egresos' => 'Operación integral, importaciones, reportes, configuración y auditoría.',
        };
    }
};
