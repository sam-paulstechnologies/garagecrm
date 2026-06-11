<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_import_batches')) {
            return;
        }

        Schema::create('client_import_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('status')->default('parsed');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('suggested_retention_actions')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('company_id', 'client_import_batches_company_id_index');
            $table->index('uploaded_by', 'client_import_batches_uploaded_by_index');
            $table->index('status', 'client_import_batches_status_index');
            $table->index('created_at', 'client_import_batches_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_import_batches');
    }
};
