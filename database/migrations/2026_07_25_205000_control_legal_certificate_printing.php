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
            $table->unsignedInteger('print_count')->default(0);
            $table->dateTime('first_printed_at')->nullable();
            $table->dateTime('last_printed_at')->nullable();
            $table->unsignedBigInteger('last_printed_by_account_id')->nullable();
            $table->string('last_printed_by_username', 120)->nullable();
        });

        Schema::create('egresos.configuracion_constancia_historial', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->tinyInteger('configuracion_id')->default(1);
            $table->string('iniciales_director', 20)->nullable();
            $table->string('iniciales_jefe', 20)->nullable();
            $table->string('iniciales_ccp', 20)->nullable();
            $table->string('nombre_director', 180)->nullable();
            $table->string('nombre_jefe', 180)->nullable();
            $table->string('cargo_director', 180)->nullable();
            $table->string('cargo_jefe', 180)->nullable();
            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('actor_account_id')->nullable();
            $table->string('actor_username', 120)->nullable();
            $table->string('actor_display_name', 360)->nullable();
            $table->string('ip', 80)->nullable();
            $table->string('user_agent', 510)->nullable();
            $table->timestamps();

            $table->index('created_at', 'ix_conf_const_hist_created');
        });

        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('ALTER TABLE [egresos].[constancia_historial] DROP CONSTRAINT [ck_const_hist_accion]');
            DB::statement(
                "ALTER TABLE [egresos].[constancia_historial]
                 ADD CONSTRAINT [ck_const_hist_accion]
                 CHECK ([accion] IN (N'generar', N'editar', N'anular', N'reactivar', N'imprimir'))"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('ALTER TABLE [egresos].[constancia_historial] DROP CONSTRAINT [ck_const_hist_accion]');
            DB::statement(
                "ALTER TABLE [egresos].[constancia_historial]
                 ADD CONSTRAINT [ck_const_hist_accion]
                 CHECK ([accion] IN (N'generar', N'editar', N'anular', N'reactivar'))"
            );
        }

        Schema::dropIfExists('egresos.configuracion_constancia_historial');
        Schema::table('egresos.constancias', function (Blueprint $table): void {
            $table->dropColumn([
                'print_count',
                'first_printed_at',
                'last_printed_at',
                'last_printed_by_account_id',
                'last_printed_by_username',
            ]);
        });
    }
};
