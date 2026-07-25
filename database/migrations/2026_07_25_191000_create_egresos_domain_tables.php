<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogos.cie10', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 40)->default('egresos_legacy');
            $table->integer('source_id')->nullable();
            $table->string('codigo', 20);
            $table->string('codigo_normalizado', 20);
            $table->text('descripcion');
            $table->string('estado', 50)->nullable();
            $table->string('cotejo_sexo', 50)->nullable();
            $table->char('source_fingerprint', 64);
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('source_updated_at')->nullable();
            $table->dateTime('imported_at')->useCurrent();
            $table->timestamps();

            $table->unique('codigo_normalizado', 'uq_cie10_codigo_normalizado');
            $table->index('codigo', 'ix_cie10_codigo');
            $table->index('estado', 'ix_cie10_estado');
            $table->index('source_fingerprint', 'ix_cie10_fingerprint');
        });

        Schema::create('egresos.importaciones', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 40);
            $table->integer('source_id')->nullable();
            $table->string('archivo', 255);
            $table->unsignedBigInteger('actor_account_id')->nullable();
            $table->unsignedBigInteger('actor_person_id')->nullable();
            $table->string('actor_username', 120)->nullable();
            $table->string('actor_display_name', 360)->nullable();
            $table->integer('insertados')->default(0);
            $table->integer('omitidos')->default(0);
            $table->integer('errores')->default(0);
            $table->json('detalle')->nullable();
            $table->char('file_sha256', 64)->nullable();
            $table->string('estado', 30)->default('completed');
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index('file_sha256', 'ix_egr_import_sha');
            $table->index(['estado', 'created_at'], 'ix_egr_import_status_date');
        });

        Schema::create('egresos.egresos', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 40)->default('egresos_legacy');
            $table->integer('source_id');
            $table->unsignedBigInteger('importacion_id')->nullable();
            $table->string('renipress', 50)->nullable();
            $table->string('e_ubig', 50)->nullable();
            $table->string('e_cdpto', 50)->nullable();
            $table->string('e_cprov', 50)->nullable();
            $table->string('e_cdist', 50)->nullable();
            $table->string('cod_disa', 50)->nullable();
            $table->string('cod_red', 50)->nullable();
            $table->string('cod_mred', 50)->nullable();
            $table->string('numhc', 50)->nullable();
            $table->string('nomb', 150)->nullable();
            $table->string('apell', 150)->nullable();
            $table->string('doc_iden', 50)->nullable();
            $table->string('etnia', 50)->nullable();
            $table->string('sexo', 10)->nullable();
            $table->string('edad', 10)->nullable();
            $table->string('tipoedad', 10)->nullable();
            $table->string('ubigeo', 50)->nullable();
            $table->string('cdpto', 50)->nullable();
            $table->string('cprov', 50)->nullable();
            $table->string('cdist', 50)->nullable();
            $table->date('fecing')->nullable();
            $table->date('fecegr')->nullable();
            $table->string('totalest', 50)->nullable();
            $table->string('ups', 50)->nullable();
            $table->string('condicion', 50)->nullable();
            $table->string('financia', 50)->nullable();
            $table->string('coddiag1', 50)->nullable();
            $table->string('coddiag2', 50)->nullable();
            $table->string('coddiag3', 50)->nullable();
            $table->string('coddiag4', 50)->nullable();
            $table->string('cemorb1', 50)->nullable();
            $table->string('cemorb2', 50)->nullable();
            $table->string('codcpt1', 50)->nullable();
            $table->string('codcpt2', 50)->nullable();
            $table->string('codcpt3', 50)->nullable();
            $table->string('codcpt4', 50)->nullable();
            $table->string('estadio', 50)->nullable();
            $table->string('valor_t', 50)->nullable();
            $table->string('valor_n', 50)->nullable();
            $table->string('valor_m', 50)->nullable();
            $table->string('tratamien', 200)->nullable();
            $table->string('prof_parto', 50)->nullable();
            $table->date('fecparto')->nullable();
            $table->string('rnvivo', 10)->nullable();
            $table->string('rnmuerto', 10)->nullable();
            $table->string('codpsal', 50)->nullable();
            $table->date('fechareg')->nullable();
            $table->string('estado', 20)->nullable();
            $table->char('source_fingerprint', 64);
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('imported_at')->useCurrent();
            $table->timestamps();

            $table->foreign('importacion_id', 'fk_egresos_importacion')
                ->references('id')->on('egresos.importaciones')->nullOnDelete();
            $table->index('numhc', 'ix_egresos_numhc');
            $table->index('doc_iden', 'ix_egresos_documento');
            $table->index('fecegr', 'ix_egresos_fecha_egreso');
            $table->index('fecing', 'ix_egresos_fecha_ingreso');
            $table->index('coddiag1', 'ix_egresos_diag1');
            $table->index(['numhc', 'fecegr'], 'ix_egresos_hc_fecha');
            $table->index(['doc_iden', 'fecegr'], 'ix_egresos_doc_fecha');
            $table->index('source_fingerprint', 'ix_egresos_fingerprint');
        });

        Schema::create('egresos.constancias', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 40)->default('egresos_legacy');
            $table->integer('source_id')->nullable();
            $table->unsignedBigInteger('egreso_id')->nullable();
            $table->integer('numero');
            $table->smallInteger('anio');
            $table->string('sequence_owner_key', 160);
            $table->unsignedBigInteger('issuer_account_id')->nullable();
            $table->unsignedBigInteger('issuer_person_id')->nullable();
            $table->integer('issuer_legacy_user_id')->nullable();
            $table->string('issuer_username', 120)->nullable();
            $table->string('issuer_display_name', 360)->nullable();
            $table->string('numhc', 50)->nullable();
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
            $table->string('iniciales_director', 20)->nullable();
            $table->string('iniciales_jefe', 20)->nullable();
            $table->string('iniciales_ccp', 20)->nullable();
            $table->string('sigla_servicio', 30)->nullable();
            $table->string('nombre_pdf', 255)->nullable();
            $table->text('observacion')->nullable();
            $table->string('estado', 20)->default('generada');
            $table->text('motivo_anulacion')->nullable();
            $table->unsignedBigInteger('cancelled_by_account_id')->nullable();
            $table->unsignedBigInteger('cancelled_by_person_id')->nullable();
            $table->integer('cancelled_by_legacy_user_id')->nullable();
            $table->string('cancelled_by_username', 120)->nullable();
            $table->string('cancelled_by_display_name', 360)->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->char('source_fingerprint', 64);
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('source_updated_at')->nullable();
            $table->dateTime('imported_at')->useCurrent();
            $table->timestamps();

            $table->unique(
                ['sequence_owner_key', 'anio', 'numero'],
                'uq_constancias_owner_year_number'
            );
            $table->foreign('egreso_id', 'fk_constancias_egreso')
                ->references('id')->on('egresos.egresos')->nullOnDelete();
            $table->index('numhc', 'ix_constancias_numhc');
            $table->index('doc_iden', 'ix_constancias_documento');
            $table->index('fecegr', 'ix_constancias_fecha_egreso');
            $table->index('estado', 'ix_constancias_estado');
            $table->index('source_fingerprint', 'ix_constancias_fingerprint');
        });

        Schema::create('egresos.constancia_historial', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 40)->default('egresos_legacy');
            $table->integer('source_id')->nullable();
            $table->unsignedBigInteger('constancia_id');
            $table->string('accion', 20);
            $table->text('descripcion')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->unsignedBigInteger('actor_account_id')->nullable();
            $table->unsignedBigInteger('actor_person_id')->nullable();
            $table->integer('actor_legacy_user_id')->nullable();
            $table->string('actor_username', 120)->nullable();
            $table->string('actor_display_name', 360)->nullable();
            $table->string('ip', 80)->nullable();
            $table->char('source_fingerprint', 64);
            $table->dateTime('occurred_at');
            $table->dateTime('imported_at')->useCurrent();
            $table->timestamps();

            $table->foreign('constancia_id', 'fk_const_hist_constancia')
                ->references('id')->on('egresos.constancias')->cascadeOnDelete();
            $table->index(['constancia_id', 'occurred_at'], 'ix_const_hist_date');
            $table->index(['actor_account_id', 'occurred_at'], 'ix_const_hist_actor');
            $table->index('accion', 'ix_const_hist_action');
        });

        Schema::create('egresos.correlativos', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('sequence_owner_key', 160);
            $table->smallInteger('anio');
            $table->integer('ultimo_numero')->default(0);
            $table->timestamps();

            $table->unique(
                ['sequence_owner_key', 'anio'],
                'uq_correlativos_owner_year'
            );
        });

        Schema::create('egresos.configuracion_constancias', function (Blueprint $table): void {
            $table->tinyInteger('id')->primary();
            $table->string('iniciales_director', 20)->nullable();
            $table->string('iniciales_jefe', 20)->nullable();
            $table->string('iniciales_ccp', 20)->nullable();
            $table->string('nombre_director', 180)->nullable();
            $table->string('nombre_jefe', 180)->nullable();
            $table->string('cargo_director', 180)->nullable();
            $table->string('cargo_jefe', 180)->nullable();
            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('updated_by_account_id')->nullable();
            $table->string('updated_by_username', 120)->nullable();
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('source_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('auditoria.eventos', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('event_uuid')->unique('uq_audit_event_uuid');
            $table->string('application_code', 120)->default('intranet_hsj');
            $table->string('module', 80);
            $table->string('event_type', 120);
            $table->string('action', 120);
            $table->string('subject_type', 160)->nullable();
            $table->string('subject_id', 120)->nullable();
            $table->unsignedBigInteger('actor_account_id')->nullable();
            $table->unsignedBigInteger('actor_person_id')->nullable();
            $table->string('actor_username', 120)->nullable();
            $table->string('actor_display_name', 360)->nullable();
            $table->uuid('session_uuid')->nullable();
            $table->string('ip', 80)->nullable();
            $table->string('user_agent', 510)->nullable();
            $table->json('data_before')->nullable();
            $table->json('data_after')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['module', 'occurred_at'], 'ix_audit_module_date');
            $table->index(
                ['actor_account_id', 'occurred_at'],
                'ix_audit_actor_date'
            );
            $table->index(
                ['subject_type', 'subject_id'],
                'ix_audit_subject'
            );
        });

        $this->addCheckConstraints();
        $this->addFilteredSourceIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria.eventos');
        Schema::dropIfExists('egresos.configuracion_constancias');
        Schema::dropIfExists('egresos.correlativos');
        Schema::dropIfExists('egresos.constancia_historial');
        Schema::dropIfExists('egresos.constancias');
        Schema::dropIfExists('egresos.egresos');
        Schema::dropIfExists('egresos.importaciones');
        Schema::dropIfExists('catalogos.cie10');
    }

    private function addCheckConstraints(): void
    {
        DB::statement(
            "ALTER TABLE [egresos].[constancias]
             ADD CONSTRAINT [ck_constancias_estado]
             CHECK ([estado] IN (N'generada', N'editada', N'anulada'))"
        );

        DB::statement(
            "ALTER TABLE [egresos].[constancia_historial]
             ADD CONSTRAINT [ck_const_hist_accion]
             CHECK ([accion] IN (N'generar', N'editar', N'anular', N'reactivar'))"
        );

        DB::statement(
            "ALTER TABLE [egresos].[importaciones]
             ADD CONSTRAINT [ck_egr_import_estado]
             CHECK ([estado] IN (N'pending', N'running', N'completed', N'failed', N'rolled_back'))"
        );
    }

    private function addFilteredSourceIndexes(): void
    {
        $indexes = [
            ['catalogos', 'cie10', 'uq_cie10_source'],
            ['egresos', 'importaciones', 'uq_egr_import_source'],
            ['egresos', 'egresos', 'uq_egresos_source'],
            ['egresos', 'constancias', 'uq_constancias_source'],
            ['egresos', 'constancia_historial', 'uq_const_hist_source'],
        ];

        foreach ($indexes as [$schema, $table, $index]) {
            DB::statement(
                "CREATE UNIQUE INDEX [{$index}]
                 ON [{$schema}].[{$table}] ([source_system], [source_id])
                 WHERE [source_id] IS NOT NULL"
            );
        }
    }
};
