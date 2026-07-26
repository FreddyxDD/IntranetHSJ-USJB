<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'identity';

    public function up(): void
    {
        Schema::connection('identity')->table('access_accounts', function (Blueprint $table): void {
            if (! Schema::connection('identity')->hasColumn('access_accounts', 'approved_at')) {
                $table->dateTime('approved_at')->nullable();
            }
            if (! Schema::connection('identity')->hasColumn('access_accounts', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('identity')->table('access_accounts', function (Blueprint $table): void {
            if (Schema::connection('identity')->hasColumn('access_accounts', 'approved_by')) {
                $table->dropIndex(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::connection('identity')->hasColumn('access_accounts', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
