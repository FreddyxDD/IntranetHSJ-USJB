<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogos.cie10_importaciones', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('archivo', 255);
            $table->char('file_sha256', 64);
            $table->string('estado', 30)->default('analizado');
            $table->unsignedBigInteger('actor_account_id')->nullable();
            $table->string('actor_username', 120)->nullable();
            $table->string('actor_display_name', 360)->nullable();
            $table->integer('nuevos')->default(0);
            $table->integer('actualizaciones')->default(0);
            $table->integer('sin_cambios')->default(0);
            $table->integer('errores')->default(0);
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['file_sha256', 'estado'], 'ix_cie10_imp_hash_status');
            $table->index(['created_at', 'id'], 'ix_cie10_imp_created');
        });

        Schema::create('catalogos.cie10_importacion_filas', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('importacion_id');
            $table->integer('fila');
            $table->string('estado', 30);
            $table->unsignedBigInteger('cie10_id')->nullable();
            $table->string('codigo', 20)->nullable();
            $table->string('codigo_normalizado', 20)->nullable();
            $table->json('datos');
            $table->json('datos_anteriores')->nullable();
            $table->json('mensajes')->nullable();
            $table->timestamps();

            $table->foreign('importacion_id', 'fk_cie10_imp_rows_batch')
                ->references('id')->on('catalogos.cie10_importaciones')->cascadeOnDelete();
            $table->foreign('cie10_id', 'fk_cie10_imp_rows_catalog')
                ->references('id')->on('catalogos.cie10');
            $table->unique(['importacion_id', 'fila'], 'uq_cie10_imp_rows_line');
            $table->index(['importacion_id', 'estado'], 'ix_cie10_imp_rows_status');
            $table->index('codigo_normalizado', 'ix_cie10_imp_rows_code');
        });

        DB::statement(
            "ALTER TABLE [catalogos].[cie10_importaciones]
             ADD CONSTRAINT [ck_cie10_imp_status]
             CHECK ([estado] IN (N'analizado', N'confirmado', N'fallido'))"
        );
        DB::statement(
            "ALTER TABLE [catalogos].[cie10_importacion_filas]
             ADD CONSTRAINT [ck_cie10_imp_row_status]
             CHECK ([estado] IN (
                N'nuevo', N'actualizar', N'sin_cambios', N'error',
                N'insertado', N'actualizado'
             ))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogos.cie10_importacion_filas');
        Schema::dropIfExists('catalogos.cie10_importaciones');
    }
};
