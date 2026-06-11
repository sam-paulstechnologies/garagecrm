<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('retention_actions')) {
            return;
        }

        Schema::create('retention_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('vehicle_service_history_id')->nullable();
            $table->string('source_type')->default('client_import_row');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('segment_code');
            $table->string('segment_label')->nullable();
            $table->string('last_service_type')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('suggested_follow_up_date')->nullable();
            $table->text('suggested_message')->nullable();
            $table->string('status')->default('pending_review');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('message_log_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('company_id', 'retention_actions_company_id_index');
            $table->index('client_id', 'retention_actions_client_id_index');
            $table->index('vehicle_id', 'retention_actions_vehicle_id_index');
            $table->index('vehicle_service_history_id', 'retention_actions_history_id_index');
            $table->index(['source_type', 'source_id'], 'retention_actions_source_index');
            $table->index('segment_code', 'retention_actions_segment_code_index');
            $table->index('status', 'retention_actions_status_index');
            $table->index('suggested_follow_up_date', 'retention_actions_follow_up_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_actions');
    }
};
