<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'identity';

    public function up(): void
    {
        if (Schema::connection('identity')->hasTable('access_account_permission_overrides')) {
            return;
        }

        Schema::connection('identity')->create('access_account_permission_overrides', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('permission_id');
            $table->boolean('is_granted');
            $table->timestamp('assigned_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'permission_id'], 'uq_account_permission_override');
            $table->index(['permission_id', 'is_granted'], 'ix_permission_override_state');
            $table->foreign('account_id')->references('id')->on('access_accounts')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('access_permissions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('identity')->dropIfExists('access_account_permission_overrides');
    }
};
