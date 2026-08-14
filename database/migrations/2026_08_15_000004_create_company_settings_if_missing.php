<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_settings')) {
            return;
        }

        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id');
            $table->string('key', 190);
            $table->text('value')->nullable();
            $table->string('group', 50)->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'key'], 'uniq_company_key');
            $table->index(['company_id', 'group'], 'idx_company_group');
        });
    }

    /**
     * This compatibility migration represents a canonical table that predates
     * the repository migration ledger. Rollback must not destroy it.
     */
    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
