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
            $table->integer('doc_tipo_id')->nullable();
            $table->string('doc_numero', 20)->nullable();
            $table->string('doc_iden_original', 50)->nullable();
            $table->string('doc_source', 40)->nullable();
            $table->integer('patient_source_id')->nullable();
            $table->dateTime('document_verified_at')->nullable();

            $table->index('doc_numero', 'ix_egresos_doc_numero');
            $table->index('patient_source_id', 'ix_egresos_patient_source');
        });

        DB::statement(
            "UPDATE [egresos].[egresos]
             SET [doc_iden_original] = NULLIF(LTRIM(RTRIM([doc_iden])), ''),
                 [doc_tipo_id] = CASE
                    WHEN LEN(LTRIM(RTRIM([doc_iden]))) > 1
                     AND LEFT(LTRIM(RTRIM([doc_iden])), 1) IN ('1', '2', '3')
                    THEN TRY_CONVERT(INT, LEFT(LTRIM(RTRIM([doc_iden])), 1))
                    ELSE NULL
                 END,
                 [doc_numero] = CASE
                    WHEN LEN(LTRIM(RTRIM([doc_iden]))) > 1
                     AND LEFT(LTRIM(RTRIM([doc_iden])), 1) IN ('1', '2', '3')
                    THEN NULLIF(SUBSTRING(
                        LTRIM(RTRIM([doc_iden])),
                        2,
                        LEN(LTRIM(RTRIM([doc_iden]))) - 1
                    ), '')
                    ELSE NULL
                 END,
                 [doc_source] = CASE
                    WHEN LEN(LTRIM(RTRIM([doc_iden]))) > 1
                     AND LEFT(LTRIM(RTRIM([doc_iden])), 1) IN ('1', '2', '3')
                    THEN N'egresos_legacy_normalized'
                    ELSE N'egresos_legacy_unresolved'
                 END"
        );
    }

    public function down(): void
    {
        Schema::table('egresos.egresos', function (Blueprint $table): void {
            $table->dropIndex('ix_egresos_doc_numero');
            $table->dropIndex('ix_egresos_patient_source');
            $table->dropColumn([
                'doc_tipo_id',
                'doc_numero',
                'doc_iden_original',
                'doc_source',
                'patient_source_id',
                'document_verified_at',
            ]);
        });
    }
};
