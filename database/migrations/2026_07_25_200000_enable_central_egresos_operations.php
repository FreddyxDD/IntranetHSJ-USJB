<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'DROP INDEX [uq_egresos_source] ON [egresos].[egresos]'
        );
        DB::statement(
            'ALTER TABLE [egresos].[egresos] ALTER COLUMN [source_id] INT NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX [uq_egresos_source]
             ON [egresos].[egresos] ([source_system], [source_id])
             WHERE [source_id] IS NOT NULL'
        );

        DB::statement(
            'CREATE INDEX [ix_egresos_ups_fecha] ON [egresos].[egresos] ([ups], [fecegr])'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX [ix_egresos_ups_fecha] ON [egresos].[egresos]');

        if (DB::table('egresos.egresos')->whereNull('source_id')->exists()) {
            throw new RuntimeException(
                'No se puede restaurar source_id como obligatorio: existen egresos creados en Intranet.'
            );
        }

        DB::statement('DROP INDEX [uq_egresos_source] ON [egresos].[egresos]');
        DB::statement('ALTER TABLE [egresos].[egresos] ALTER COLUMN [source_id] INT NOT NULL');
        DB::statement(
            'CREATE UNIQUE INDEX [uq_egresos_source]
             ON [egresos].[egresos] ([source_system], [source_id])
             WHERE [source_id] IS NOT NULL'
        );
    }
};
