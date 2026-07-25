<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cirugias.importaciones', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 40);
            $table->integer('source_id')->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('hoja', 100)->nullable();
            $table->integer('total_registros')->default(0);
            $table->integer('registros_validos')->default(0);
            $table->integer('registros_observados')->default(0);
            $table->unsignedBigInteger('actor_account_id')->nullable();
            $table->unsignedBigInteger('actor_person_id')->nullable();
            $table->string('actor_username', 120)->nullable();
            $table->char('source_fingerprint', 64);
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('imported_at')->useCurrent();
            $table->timestamps();

            $table->index('source_fingerprint', 'ix_cir_import_fingerprint');
        });

        Schema::create('cirugias.especialidades', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('nombre', 120);
            $table->string('nombre_normalizado', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('nombre_normalizado', 'uq_cir_especialidad_nombre');
        });

        Schema::create('cirugias.cirugias', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 40)->default('hospital_ueei');
            $table->integer('source_id');
            $table->unsignedBigInteger('importacion_id')->nullable();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('historia_clinica', 50)->nullable();
            $table->string('dni', 20)->nullable();
            $table->string('nombres_apellidos', 200)->nullable();
            $table->string('tipo_orden', 50)->nullable();
            $table->string('especialidad', 120)->nullable();
            $table->integer('edad')->nullable();
            $table->string('sexo', 30)->nullable();
            $table->string('tipo_seguro', 100)->nullable();
            $table->string('prueba_covid', 100)->nullable();
            $table->string('suspension', 50)->nullable();
            $table->text('motivo_suspension')->nullable();
            $table->text('diagnostico_preoperatorio')->nullable();
            $table->string('codigo_cie10', 30)->nullable();
            $table->text('operacion_realizada')->nullable();
            $table->string('comorbilidad', 255)->nullable();
            $table->string('reintervencion', 50)->nullable();
            $table->string('ram_medicamentos', 255)->nullable();
            $table->string('discrepancia_diagnostica', 50)->nullable();
            $table->string('tiempo_total', 50)->nullable();
            $table->string('tiempo_anestesia', 50)->nullable();
            $table->string('tiempo_operacion', 50)->nullable();
            $table->text('complicaciones_intraoperatorias')->nullable();
            $table->string('cirujano_1', 180)->nullable();
            $table->string('cirujano_2', 180)->nullable();
            $table->string('anestesiologo', 180)->nullable();
            $table->string('enfermera_instrumentista', 180)->nullable();
            $table->string('anestesiologo_recuperacion', 180)->nullable();
            $table->string('enfermera_recuperacion', 180)->nullable();
            $table->string('tecnico_enfermeria_1', 180)->nullable();
            $table->string('tecnico_enfermeria_2', 180)->nullable();
            $table->string('tipo_anestesia', 100)->nullable();
            $table->string('cirugia_mayor', 50)->nullable();
            $table->string('cirugia_menor', 50)->nullable();
            $table->string('sop', 50)->nullable();
            $table->string('destino', 120)->nullable();
            $table->string('tiempo_urpa', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('hoja_origen', 100)->nullable();
            $table->string('origen_registro', 30)->nullable();
            $table->char('source_fingerprint', 64);
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('source_updated_at')->nullable();
            $table->dateTime('imported_at')->useCurrent();
            $table->timestamps();

            $table->foreign('importacion_id', 'fk_cirugias_importacion')
                ->references('id')->on('cirugias.importaciones')->nullOnDelete();
            $table->index('fecha', 'ix_cirugias_fecha');
            $table->index('historia_clinica', 'ix_cirugias_hc');
            $table->index('dni', 'ix_cirugias_dni');
            $table->index('especialidad', 'ix_cirugias_especialidad');
            $table->index('codigo_cie10', 'ix_cirugias_cie10');
            $table->index(['fecha', 'historia_clinica'], 'ix_cirugias_fecha_hc');
            $table->index('source_fingerprint', 'ix_cirugias_fingerprint');
        });

        Schema::create('cirugias.participantes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cirugia_id');
            $table->string('rol', 60);
            $table->tinyInteger('orden')->default(1);
            $table->string('source_display_name', 180);
            $table->string('source_personnel_id', 120)->nullable();
            $table->unsignedBigInteger('identity_person_id')->nullable();
            $table->unsignedBigInteger('identity_personnel_record_id')->nullable();
            $table->unsignedBigInteger('identity_assignment_id')->nullable();
            $table->string('match_status', 30)->default('pending');
            $table->timestamps();

            $table->foreign('cirugia_id', 'fk_cir_part_cirugia')
                ->references('id')->on('cirugias.cirugias')->cascadeOnDelete();
            $table->unique(
                ['cirugia_id', 'rol', 'orden'],
                'uq_cir_part_role_order'
            );
            $table->index('identity_person_id', 'ix_cir_part_person');
            $table->index('match_status', 'ix_cir_part_status');
        });

        DB::statement(
            'CREATE UNIQUE INDEX [uq_cir_import_source]
             ON [cirugias].[importaciones] ([source_system], [source_id])
             WHERE [source_id] IS NOT NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX [uq_cirugias_source]
             ON [cirugias].[cirugias] ([source_system], [source_id])'
        );
        DB::statement(
            "ALTER TABLE [cirugias].[participantes]
             ADD CONSTRAINT [ck_cir_part_status]
             CHECK ([match_status] IN (N'pending', N'matched', N'ambiguous', N'rejected'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cirugias.participantes');
        Schema::dropIfExists('cirugias.cirugias');
        Schema::dropIfExists('cirugias.especialidades');
        Schema::dropIfExists('cirugias.importaciones');
    }
};
