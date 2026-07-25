<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'identity';

    private const PERMISSIONS = [
        'cirugias.analytics.view' => ['Ver análisis de cirugías', 'Consulta de indicadores y análisis quirúrgico.'],
        'cirugias.reports.view' => ['Ver reportes de cirugías', 'Consulta y exportación de reportes quirúrgicos.'],
        'cirugias.records.manage' => ['Gestionar registros de cirugías', 'Creación y actualización manual de registros quirúrgicos.'],
        'cirugias.imports.manage' => ['Gestionar importaciones de cirugías', 'Importación desde Excel y eliminación masiva de datos.'],
        'cirugias.staff.manage' => ['Gestionar personal de cirugías', 'Mantenimiento del personal médico y asistencial.'],
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

        foreach (self::PERMISSIONS as $code => [$name, $description]) {
            $database->table('access_permissions')->updateOrInsert(
                ['application_id' => $applicationId, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'module' => 'Cirugías',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $administratorRoleId = $database->table('access_roles')
            ->where('application_id', $applicationId)
            ->where('code', 'administrador')
            ->value('id');

        if ($administratorRoleId) {
            $permissionIds = $database->table('access_permissions')
                ->where('application_id', $applicationId)
                ->whereIn('code', array_keys(self::PERMISSIONS))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                $database->table('access_role_permissions')->updateOrInsert([
                    'role_id' => $administratorRoleId,
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

        $permissionIds = $database->table('access_permissions')
            ->where('application_id', $applicationId)
            ->whereIn('code', array_keys(self::PERMISSIONS))
            ->pluck('id');

        $database->table('access_role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();
        $database->table('access_permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
