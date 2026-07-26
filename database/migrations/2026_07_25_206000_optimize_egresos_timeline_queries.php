<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egresos.egresos', function (Blueprint $table): void {
            $table->index(['imported_at', 'id'], 'ix_egresos_recent_imports');
            $table->index(['numhc', 'fecing', 'id'], 'ix_egresos_timeline_hc');
            $table->index(['doc_numero', 'fecing', 'id'], 'ix_egresos_timeline_doc');
        });
    }

    public function down(): void
    {
        Schema::table('egresos.egresos', function (Blueprint $table): void {
            $table->dropIndex('ix_egresos_recent_imports');
            $table->dropIndex('ix_egresos_timeline_hc');
            $table->dropIndex('ix_egresos_timeline_doc');
        });
    }
};
