<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_documents')) {
            return;
        }

        Schema::create('client_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('document_name');
            $table->string('document_path', 500);
            $table->string('storage_disk', 64)->nullable();
            $table->string('document_type', 50)->nullable()->default('other');
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // The table predates this compatibility migration in canonical MySQL
        // installations; never drop it from an existing environment.
    }
};
