<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_scan_workspaces', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->longText('garage_name');
            $table->longText('garage_contact_name')->nullable();
            $table->longText('garage_phone')->nullable();
            $table->longText('garage_email')->nullable();
            $table->longText('notes')->nullable();
            $table->char('garage_identity_hash', 64)->nullable()->index();
            $table->string('source', 80)->nullable();
            $table->date('follow_up_at')->nullable();
            $table->string('outcome', 40)->nullable();
            $table->string('status', 32)->default('consent_pending')->index();
            $table->char('access_token_hash', 64)->unique();
            $table->longText('access_token_encrypted');
            $table->timestamp('link_expires_at')->index();
            $table->timestamp('report_expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_policy_version', 40)->nullable();
            $table->string('connection_mode', 48)->nullable();
            $table->longText('claimed_whatsapp_number')->nullable();
            $table->char('claimed_whatsapp_hash', 64)->nullable()->index();
            $table->json('staff_number_hashes')->nullable();
            $table->unsignedInteger('analysis_limit')->default(0);
            $table->unsignedInteger('contacts_discovered')->default(0);
            $table->unsignedInteger('contacts_deterministic_excluded')->default(0);
            $table->unsignedInteger('contacts_analysed')->default(0);
            $table->unsignedInteger('ai_calls')->default(0);
            $table->unsignedInteger('analysis_duration_ms')->default(0);
            $table->json('report_metrics')->nullable();
            $table->timestamp('connection_started_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('history_sync_started_at')->nullable();
            $table->timestamp('history_sync_completed_at')->nullable();
            $table->timestamp('analysis_started_at')->nullable();
            $table->timestamp('analysis_completed_at')->nullable();
            $table->timestamp('report_ready_at')->nullable();
            $table->timestamp('report_viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('purge_scheduled_at')->nullable()->index();
            $table->timestamp('purged_at')->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->longText('failure_detail')->nullable();
            $table->timestamps();
        });

        Schema::create('quick_scan_provider_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('quick_scan_workspace_id')->constrained('quick_scan_workspaces')->cascadeOnDelete();
            $table->char('state_hash', 64)->unique();
            $table->longText('state_encrypted');
            $table->string('connection_mode', 48);
            $table->string('status', 32)->default('started')->index();
            $table->longText('access_token')->nullable();
            $table->longText('waba_id')->nullable();
            $table->longText('phone_number_id')->nullable();
            $table->longText('business_id')->nullable();
            $table->longText('display_phone_number')->nullable();
            $table->char('waba_hash', 64)->nullable()->index();
            $table->char('phone_number_hash', 64)->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('sync_requested_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('quick_scan_candidates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('quick_scan_workspace_id')->constrained('quick_scan_workspaces')->cascadeOnDelete();
            $table->char('external_identity_hash', 64);
            $table->longText('customer_identifier')->nullable();
            $table->longText('display_name')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedInteger('inbound_count')->default(0);
            $table->unsignedInteger('outbound_count')->default(0);
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('deterministic_excluded')->default(false);
            $table->string('intelligence_status', 24)->default('discovered')->index();
            $table->char('analysis_fingerprint', 64)->nullable();
            $table->string('classification', 32)->default('unknown')->index();
            $table->decimal('classification_confidence', 5, 4)->nullable();
            $table->longText('classification_reason')->nullable();
            $table->string('retention_level', 24)->default('none')->index();
            $table->decimal('retention_confidence', 5, 4)->nullable();
            $table->longText('retention_reason')->nullable();
            $table->boolean('potential_missed')->default(false)->index();
            $table->boolean('quote_unresolved')->default(false)->index();
            $table->boolean('service_related')->default(false)->index();
            $table->timestamp('analysis_counted_at')->nullable();
            $table->timestamp('analysed_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
            $table->unique(['quick_scan_workspace_id', 'external_identity_hash'], 'quick_scan_candidate_identity_unique');
        });

        Schema::create('quick_scan_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quick_scan_workspace_id')->constrained('quick_scan_workspaces')->cascadeOnDelete();
            $table->foreignId('quick_scan_candidate_id')->constrained('quick_scan_candidates')->cascadeOnDelete();
            $table->char('source_fingerprint', 64)->unique();
            $table->char('provider_message_hash', 64)->nullable()->index();
            $table->string('direction', 8);
            $table->string('message_type', 40)->nullable();
            $table->longText('body')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('message_timestamp')->nullable()->index();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quick_scan_provider_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quick_scan_workspace_id')->constrained('quick_scan_workspaces')->cascadeOnDelete();
            $table->foreignId('quick_scan_provider_session_id')->nullable();
            $table->foreign('quick_scan_provider_session_id', 'qs_provider_event_session_fk')
                ->references('id')->on('quick_scan_provider_sessions')->nullOnDelete();
            $table->char('event_key', 64)->unique();
            $table->string('field', 80)->index();
            $table->char('provider_event_hash', 64)->nullable()->index();
            $table->char('payload_hash', 64);
            $table->longText('payload')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('error_code', 80)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quick_scan_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quick_scan_workspace_id')->constrained('quick_scan_workspaces')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80)->index();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['quick_scan_workspace_id', 'event', 'created_at'], 'quick_scan_event_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_scan_events');
        Schema::dropIfExists('quick_scan_provider_events');
        Schema::dropIfExists('quick_scan_messages');
        Schema::dropIfExists('quick_scan_candidates');
        Schema::dropIfExists('quick_scan_provider_sessions');
        Schema::dropIfExists('quick_scan_workspaces');
    }
};
