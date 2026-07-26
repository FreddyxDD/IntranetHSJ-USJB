<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egresos.constancias', function (Blueprint $table): void {
            $table->char('preview_token_hash', 64)->nullable();
        });

        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement(
                'CREATE UNIQUE INDEX [uq_constancias_preview_token]
                 ON [egresos].[constancias] ([preview_token_hash])
                 WHERE [preview_token_hash] IS NOT NULL'
            );
        } else {
            Schema::table('egresos.constancias', function (Blueprint $table): void {
                $table->unique('preview_token_hash', 'uq_constancias_preview_token');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('DROP INDEX [uq_constancias_preview_token] ON [egresos].[constancias]');
        } else {
            Schema::table('egresos.constancias', function (Blueprint $table): void {
                $table->dropUnique('uq_constancias_preview_token');
            });
        }

        Schema::table('egresos.constancias', function (Blueprint $table): void {
            $table->dropColumn('preview_token_hash');
        });
    }
};
