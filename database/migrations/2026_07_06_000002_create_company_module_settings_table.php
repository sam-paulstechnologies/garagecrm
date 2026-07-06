<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_module_settings')) {
            return;
        }

        Schema::create('company_module_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('module_key', 80);
            $table->boolean('enabled')->default(true);
            $table->boolean('locked')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'module_key'], 'company_module_unique');
            $table->index(['module_key', 'enabled'], 'company_module_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_module_settings');
    }
};
