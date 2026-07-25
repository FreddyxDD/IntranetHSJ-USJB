<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SCHEMAS = [
        'egresos',
        'cirugias',
        'catalogos',
        'auditoria',
        'staging',
    ];

    public function up(): void
    {
        foreach (self::SCHEMAS as $schema) {
            DB::statement(
                "IF NOT EXISTS (SELECT 1 FROM sys.schemas WHERE name = N'{$schema}')
                    EXEC(N'CREATE SCHEMA [{$schema}] AUTHORIZATION [dbo]')"
            );
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::SCHEMAS) as $schema) {
            DB::statement(
                "IF EXISTS (
                    SELECT 1
                    FROM sys.schemas s
                    WHERE s.name = N'{$schema}'
                      AND NOT EXISTS (
                          SELECT 1
                          FROM sys.objects o
                          WHERE o.schema_id = s.schema_id
                      )
                )
                    EXEC(N'DROP SCHEMA [{$schema}]')"
            );
        }
    }
};
