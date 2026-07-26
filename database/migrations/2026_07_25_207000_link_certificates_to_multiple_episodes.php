<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresos.constancia_episodios', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('constancia_id');
            $table->unsignedBigInteger('egreso_id')->nullable();
            $table->unsignedTinyInteger('posicion');
            $table->string('source_system', 40)->default('intranet_hsj');
            $table->string('numhc', 50)->nullable();
            $table->integer('doc_tipo_id')->nullable();
            $table->string('doc_iden', 30)->nullable();
            $table->string('paciente', 250)->nullable();
            $table->string('nombres', 150)->nullable();
            $table->string('apellidos', 150)->nullable();
            $table->date('fecing')->nullable();
            $table->date('fecegr')->nullable();
            $table->string('ups', 100)->nullable();
            $table->string('servicio', 150)->nullable();
            $table->string('condicion', 100)->nullable();
            $table->string('financia', 100)->nullable();
            $table->string('coddiag1', 50)->nullable();
            $table->text('descdiag1')->nullable();
            $table->string('coddiag2', 50)->nullable();
            $table->text('descdiag2')->nullable();
            $table->string('coddiag3', 50)->nullable();
            $table->text('descdiag3')->nullable();
            $table->string('coddiag4', 50)->nullable();
            $table->text('descdiag4')->nullable();
            $table->timestamps();

            $table->foreign('constancia_id', 'fk_const_episode_certificate')
                ->references('id')->on('egresos.constancias')->cascadeOnDelete();
            $table->foreign('egreso_id', 'fk_const_episode_discharge')
                ->references('id')->on('egresos.egresos')->nullOnDelete();
            $table->unique(['constancia_id', 'posicion'], 'uq_const_episode_position');
            $table->unique(['constancia_id', 'egreso_id'], 'uq_const_episode_discharge');
            $table->index('egreso_id', 'ix_const_episode_discharge');
        });

        DB::statement(
            'INSERT INTO [egresos].[constancia_episodios] (
                [constancia_id], [egreso_id], [posicion], [source_system],
                [numhc], [doc_tipo_id], [doc_iden], [paciente], [nombres],
                [apellidos], [fecing], [fecegr], [ups], [servicio],
                [condicion], [financia], [coddiag1], [descdiag1], [coddiag2],
                [descdiag2], [coddiag3], [descdiag3], [coddiag4], [descdiag4],
                [created_at], [updated_at]
            )
            SELECT
                [id], [egreso_id], 1, [source_system], [numhc], [doc_tipo_id],
                [doc_iden], [paciente], [nombres], [apellidos], [fecing],
                [fecegr], [ups], [servicio], [condicion], [financia],
                [coddiag1], [descdiag1], [coddiag2], [descdiag2], [coddiag3],
                [descdiag3], [coddiag4], [descdiag4],
                COALESCE([created_at], SYSDATETIME()),
                COALESCE([updated_at], SYSDATETIME())
            FROM [egresos].[constancias]'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos.constancia_episodios');
    }
};
