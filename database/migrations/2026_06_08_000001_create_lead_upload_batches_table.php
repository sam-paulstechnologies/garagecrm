<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_upload_batches')) {
            return;
        }

        Schema::create('lead_upload_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('mode')->nullable();
            $table->string('status')->default('parsed');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('duplicate_client_rows')->default(0);
            $table->unsignedInteger('duplicate_lead_rows')->default(0);
            $table->unsignedInteger('ready_ack_rows')->default(0);
            $table->unsignedInteger('blocked_ack_rows')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('company_id', 'lead_upload_batches_company_id_index');
            $table->index('uploaded_by', 'lead_upload_batches_uploaded_by_index');
            $table->index('status', 'lead_upload_batches_status_index');
            $table->index('mode', 'lead_upload_batches_mode_index');
            $table->index('created_at', 'lead_upload_batches_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_upload_batches');
    }
};
