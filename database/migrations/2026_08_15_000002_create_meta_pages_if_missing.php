<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meta_pages')) {
            return;
        }

        Schema::create('meta_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('page_id', 50)->unique();
            $table->string('page_name')->nullable();
            $table->text('page_access_token');
            $table->json('forms_json')->nullable();
            $table->timestamps();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        // The table predates this compatibility migration in canonical MySQL
        // installations; never drop it from an existing environment.
    }
};
