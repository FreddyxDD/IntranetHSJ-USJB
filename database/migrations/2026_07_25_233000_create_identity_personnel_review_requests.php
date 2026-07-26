<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'identity';

    public function up(): void
    {
        if (Schema::connection('identity')->hasTable('personnel_review_requests')) {
            return;
        }

        Schema::connection('identity')->create('personnel_review_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('target_application_id');
            $table->string('document_number', 25);
            $table->string('request_type', 40)->default('employment_reactivation');
            $table->string('status', 20)->default('pending');
            $table->string('submitted_names', 180);
            $table->string('submitted_paternal_last_name', 80);
            $table->string('submitted_maternal_last_name', 80);
            $table->date('submitted_birth_date');
            $table->string('submitted_email', 255);
            $table->string('submitted_phone', 30);
            $table->text('request_reason');
            $table->text('identity_snapshot')->nullable();
            $table->dateTime('requested_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['target_application_id', 'status', 'requested_at'], 'ix_personnel_review_queue');
            $table->index(['person_id', 'status'], 'ix_personnel_review_person');
            $table->index(['document_number', 'status'], 'ix_personnel_review_document');
        });
    }

    public function down(): void
    {
        Schema::connection('identity')->dropIfExists('personnel_review_requests');
    }
};
