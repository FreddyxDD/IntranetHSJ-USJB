<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointment_program_states')) {
            Schema::create('appointment_program_states', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('programacion_id')->unique();
                $table->date('fecha')->nullable();
                $table->string('estado', 30)->default('PROGRAMADO');
                $table->string('observacion', 500)->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('appointment_audits')) {
            Schema::create('appointment_audits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 30);
                $table->string('entity_type', 60);
                $table->string('entity_id', 80);
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_audits');
        Schema::dropIfExists('appointment_program_states');
    }
};
