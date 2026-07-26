<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'identity';

    public function up(): void
    {
        if (! Schema::connection('identity')->hasColumn('access_accounts', 'registration_instructions_acknowledged_at')) {
            Schema::connection('identity')->table('access_accounts', function (Blueprint $table): void {
                $table->dateTime('registration_instructions_acknowledged_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('identity')->hasColumn('access_accounts', 'registration_instructions_acknowledged_at')) {
            Schema::connection('identity')->table('access_accounts', function (Blueprint $table): void {
                $table->dropColumn('registration_instructions_acknowledged_at');
            });
        }
    }
};
