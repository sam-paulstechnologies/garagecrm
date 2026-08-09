<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_customer_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('messaging_connection_id')->nullable()->constrained('messaging_connections')->nullOnDelete();
            $table->char('connection_scope_hash', 64);
            $table->char('external_identity_hash', 64);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->foreignId('first_message_log_id')->nullable()->constrained('message_logs')->nullOnDelete();
            $table->foreignId('last_message_log_id')->nullable()->constrained('message_logs')->nullOnDelete();
            $table->unsignedInteger('analysis_runs')->default(0);
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('estimated_cost_micros')->default(0);
            $table->string('latest_model', 120)->nullable();
            $table->string('last_status', 40)->default('claimed');
            $table->string('last_skipped_reason', 80)->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'connection_scope_hash', 'external_identity_hash', 'period_start'],
                'ai_customer_usage_identity_period_unique'
            );
            $table->index(['company_id', 'period_start', 'period_end'], 'ai_customer_usage_period_index');
        });

        Schema::create('ai_analysis_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('message_log_id')->nullable()->constrained('message_logs')->nullOnDelete();
            $table->foreignId('ai_customer_usage_id')->nullable()->constrained('ai_customer_usages')->nullOnDelete();
            $table->string('capability', 120)->default('ai_observational');
            $table->string('status', 40);
            $table->string('provider', 80)->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedBigInteger('estimated_cost_micros')->nullable();
            $table->string('skipped_reason', 80)->nullable();
            $table->string('error_code', 80)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['message_log_id', 'capability'], 'ai_analysis_message_capability_unique');
            $table->index(['company_id', 'status', 'created_at'], 'ai_analysis_company_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_runs');
        Schema::dropIfExists('ai_customer_usages');
    }
};
