<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'identity';

    private const PERMISSIONS = [
        'egresos.view' => ['Ingresar a Egresos', 'Acceso al módulo de Egresos.'],
        'egresos.records.view' => ['Consultar egresos', 'Consulta de registros de egreso hospitalario.'],
        'egresos.records.create' => ['Registrar egresos', 'Creación manual de registros de egreso.'],
        'egresos.records.update' => ['Corregir egresos', 'Actualización controlada de registros de egreso.'],
        'egresos.imports.manage' => ['Gestionar importaciones', 'Importación y revisión de lotes de Egresos.'],
        'egresos.certificates.create' => ['Generar constancias', 'Emisión de constancias de egreso.'],
        'egresos.certificates.update' => ['Editar constancias', 'Actualización de constancias con historial obligatorio.'],
        'egresos.certificates.cancel' => ['Anular constancias', 'Anulación de constancias con motivo y auditoría.'],
        'egresos.history.view' => ['Consultar historial', 'Consulta del historial de constancias y cambios.'],
        'egresos.reports.view' => ['Consultar reportes', 'Consulta y exportación de reportes de Egresos.'],
        'egresos.configuration.manage' => ['Configurar Egresos', 'Mantenimiento de parámetros funcionales de Egresos.'],
        'egresos.audit.view' => ['Consultar auditoría', 'Consulta de eventos de auditoría del módulo.'],
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

        foreach (self::PERMISSIONS as $code => [$name, $description]) {
            $database->table('access_permissions')->updateOrInsert(
                ['application_id' => $applicationId, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'module' => 'Egresos',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $administratorRoleId = $database->table('access_roles')
            ->where('application_id', $applicationId)
            ->where('code', 'administrador')
            ->value('id');

        if (! $administratorRoleId) {
            return;
        }

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

        if ($permissionIds->isEmpty()) {
            return;
        }

        $database->table('access_role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();
        $database->table('access_permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }
};
