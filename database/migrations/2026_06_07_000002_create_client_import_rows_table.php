<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_import_rows')) {
            return;
        }

        Schema::create('client_import_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('row_number');
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->unsignedBigInteger('client_match_id')->nullable();
            $table->unsignedBigInteger('vehicle_match_id')->nullable();
            $table->string('duplicate_status')->nullable();
            $table->string('validation_status')->default('valid');
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->string('suggested_segment_code')->nullable();
            $table->string('suggested_segment_label')->nullable();
            $table->date('suggested_next_action_date')->nullable();
            $table->text('suggested_message')->nullable();
            $table->string('review_status')->default('pending_review');
            $table->timestamps();

            $table->index('batch_id', 'client_import_rows_batch_id_index');
            $table->index('company_id', 'client_import_rows_company_id_index');
            $table->index('client_match_id', 'client_import_rows_client_match_id_index');
            $table->index('validation_status', 'client_import_rows_validation_status_index');
            $table->index('review_status', 'client_import_rows_review_status_index');
            $table->index('suggested_segment_code', 'client_import_rows_segment_code_index');
            $table->index('row_number', 'client_import_rows_row_number_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_import_rows');
    }
};
