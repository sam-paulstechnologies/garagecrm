<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_upload_rows')) {
            return;
        }

        Schema::create('lead_upload_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('row_number');
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->unsignedBigInteger('client_match_id')->nullable();
            $table->unsignedBigInteger('lead_match_id')->nullable();
            $table->unsignedBigInteger('vehicle_match_id')->nullable();
            $table->string('duplicate_client_status')->nullable();
            $table->string('duplicate_lead_status')->nullable();
            $table->string('validation_status')->default('valid');
            $table->string('ack_readiness')->nullable();
            $table->string('suggested_ack_event_key')->nullable();
            $table->string('suggested_ack_template_key')->nullable();
            $table->text('suggested_ack_message')->nullable();
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->string('review_status')->default('pending_review');
            $table->timestamps();

            $table->index('batch_id', 'lead_upload_rows_batch_id_index');
            $table->index('company_id', 'lead_upload_rows_company_id_index');
            $table->index('client_match_id', 'lead_upload_rows_client_match_id_index');
            $table->index('lead_match_id', 'lead_upload_rows_lead_match_id_index');
            $table->index('validation_status', 'lead_upload_rows_validation_status_index');
            $table->index('ack_readiness', 'lead_upload_rows_ack_readiness_index');
            $table->index('review_status', 'lead_upload_rows_review_status_index');
            $table->index('row_number', 'lead_upload_rows_row_number_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_upload_rows');
    }
};
