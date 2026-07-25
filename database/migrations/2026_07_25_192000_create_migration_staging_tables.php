<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staging.identity_user_map', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 80);
            $table->string('source_table', 120);
            $table->string('source_user_id', 120);
            $table->string('source_username', 180)->nullable();
            $table->unsignedBigInteger('identity_account_id')->nullable();
            $table->unsignedBigInteger('identity_person_id')->nullable();
            $table->string('match_method', 40)->nullable();
            $table->string('review_status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('reviewed_by_account_id')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_system', 'source_table', 'source_user_id'],
                'uq_stage_identity_source'
            );
            $table->index('identity_account_id', 'ix_stage_identity_account');
            $table->index('review_status', 'ix_stage_identity_status');
        });

        Schema::create('staging.personnel_map', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_system', 80);
            $table->string('source_table', 120);
            $table->string('source_personnel_id', 120);
            $table->string('source_document_number', 50)->nullable();
            $table->string('source_display_name', 360)->nullable();
            $table->unsignedBigInteger('identity_person_id')->nullable();
            $table->unsignedBigInteger('identity_personnel_record_id')->nullable();
            $table->unsignedBigInteger('identity_assignment_id')->nullable();
            $table->string('match_method', 40)->nullable();
            $table->string('review_status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('reviewed_by_account_id')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_system', 'source_table', 'source_personnel_id'],
                'uq_stage_personnel_source'
            );
            $table->index('source_document_number', 'ix_stage_personnel_doc');
            $table->index('identity_person_id', 'ix_stage_personnel_person');
            $table->index('review_status', 'ix_stage_personnel_status');
        });

        Schema::create('staging.import_runs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('run_uuid')->unique('uq_stage_run_uuid');
            $table->string('source_system', 80);
            $table->string('source_file_name', 255);
            $table->char('source_file_sha256', 64);
            $table->string('entity', 120);
            $table->string('status', 30)->default('pending');
            $table->boolean('dry_run')->default(true);
            $table->integer('source_count')->nullable();
            $table->integer('inserted_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->json('validation_summary')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->unsignedBigInteger('executed_by_account_id')->nullable();
            $table->timestamps();

            $table->index(['entity', 'status'], 'ix_stage_runs_entity_status');
            $table->index('source_file_sha256', 'ix_stage_runs_sha');
        });

        DB::statement(
            "ALTER TABLE [staging].[identity_user_map]
             ADD CONSTRAINT [ck_stage_identity_status]
             CHECK ([review_status] IN (N'pending', N'matched', N'ambiguous', N'rejected'))"
        );

        DB::statement(
            "ALTER TABLE [staging].[personnel_map]
             ADD CONSTRAINT [ck_stage_personnel_status]
             CHECK ([review_status] IN (N'pending', N'matched', N'ambiguous', N'rejected'))"
        );

        DB::statement(
            "ALTER TABLE [staging].[import_runs]
             ADD CONSTRAINT [ck_stage_runs_status]
             CHECK ([status] IN (N'pending', N'running', N'completed', N'failed', N'rolled_back'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('staging.import_runs');
        Schema::dropIfExists('staging.personnel_map');
        Schema::dropIfExists('staging.identity_user_map');
    }
};
