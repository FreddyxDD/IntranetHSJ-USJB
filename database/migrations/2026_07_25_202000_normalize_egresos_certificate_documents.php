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
            $table->integer('doc_tipo_id')->nullable();
            $table->string('doc_iden_original', 50)->nullable();
        });

        DB::statement(
            "UPDATE [egresos].[constancias]
             SET [doc_iden_original] = NULLIF(LTRIM(RTRIM([doc_iden])), ''),
                 [doc_tipo_id] = CASE
                    WHEN LEN(LTRIM(RTRIM([doc_iden]))) > 1
                     AND LEFT(LTRIM(RTRIM([doc_iden])), 1) IN ('1', '2', '3')
                    THEN TRY_CONVERT(INT, LEFT(LTRIM(RTRIM([doc_iden])), 1))
                    ELSE NULL
                 END,
                 [doc_iden] = CASE
                    WHEN LEN(LTRIM(RTRIM([doc_iden]))) > 1
                     AND LEFT(LTRIM(RTRIM([doc_iden])), 1) IN ('1', '2', '3')
                    THEN NULLIF(SUBSTRING(
                        LTRIM(RTRIM([doc_iden])),
                        2,
                        LEN(LTRIM(RTRIM([doc_iden]))) - 1
                    ), '')
                    WHEN LTRIM(RTRIM([doc_iden])) IN ('0', '9') THEN NULL
                    ELSE NULLIF(LTRIM(RTRIM([doc_iden])), '')
                 END"
        );
    }

    public function down(): void
    {
        DB::statement(
            'UPDATE [egresos].[constancias]
             SET [doc_iden] = COALESCE([doc_iden_original], [doc_iden])'
        );

        Schema::table('egresos.constancias', function (Blueprint $table): void {
            $table->dropColumn(['doc_tipo_id', 'doc_iden_original']);
        });
    }
};
