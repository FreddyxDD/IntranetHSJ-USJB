<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egresos.egresos', function (Blueprint $table): void {
            $table->char('episode_fingerprint', 64)->nullable();
            $table->index('episode_fingerprint', 'ix_egresos_episode_fingerprint');
        });

        Schema::create('egresos.importacion_filas', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('importacion_id');
            $table->integer('fila');
            $table->string('estado', 30);
            $table->string('paciente_clave', 180)->nullable();
            $table->string('numhc', 50)->nullable();
            $table->string('doc_iden', 50)->nullable();
            $table->integer('patient_source_id')->nullable();
            $table->unsignedBigInteger('existing_egreso_id')->nullable();
            $table->unsignedBigInteger('imported_egreso_id')->nullable();
            $table->json('datos');
            $table->json('mensajes')->nullable();
            $table->timestamps();

            $table->foreign('importacion_id', 'fk_import_rows_import')
                ->references('id')->on('egresos.importaciones')->cascadeOnDelete();
            $table->foreign('existing_egreso_id', 'fk_import_rows_existing')
                ->references('id')->on('egresos.egresos');
            $table->foreign('imported_egreso_id', 'fk_import_rows_inserted')
                ->references('id')->on('egresos.egresos');
            $table->unique(['importacion_id', 'fila'], 'uq_import_rows_line');
            $table->index(['importacion_id', 'estado'], 'ix_import_rows_status');
            $table->index(['numhc', 'doc_iden'], 'ix_import_rows_identity');
        });

        DB::statement(
            "ALTER TABLE [egresos].[importacion_filas]
             ADD CONSTRAINT [ck_import_rows_status]
             CHECK ([estado] IN (
                N'nuevo', N'reingreso', N'duplicado', N'observado',
                N'error', N'insertado'
             ))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos.importacion_filas');

        Schema::table('egresos.egresos', function (Blueprint $table): void {
            $table->dropIndex('ix_egresos_episode_fingerprint');
            $table->dropColumn('episode_fingerprint');
        });
    }
};
