<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_marketing_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_phone_number')->nullable();
            $table->string('phone_number_id')->nullable()->unique();
            $table->string('waba_id')->nullable();
            $table->string('meta_business_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('verify_token')->nullable();
            $table->string('connection_status')->default('not_connected')->index();
            $table->string('webhook_health')->default('unknown');
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->text('last_api_error')->nullable();
            $table->timestamp('template_synced_at')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('platform_marketing_prospect_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->default('manual');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_prospects', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('whatsapp_number');
            $table->string('normalized_phone')->unique();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->nullable()->index();
            $table->string('city')->nullable();
            $table->string('business_type')->nullable();
            $table->unsignedInteger('branches_count')->nullable();
            $table->unsignedInteger('employees_count')->nullable();
            $table->foreignId('source_id')->nullable()->index();
            $table->string('source')->nullable();
            $table->string('source_detail')->nullable();
            $table->string('interested_product')->default('SayaraForce')->index();
            $table->string('current_software')->nullable();
            $table->text('pain_points')->nullable();
            $table->unsignedTinyInteger('lead_score')->default(0);
            $table->foreignId('assigned_owner_id')->nullable()->index();
            $table->string('status')->default('new')->index();
            $table->string('consent_status')->default('unknown')->index();
            $table->string('consent_source')->nullable();
            $table->timestamp('consent_date')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('demo_requested_at')->nullable();
            $table->timestamp('demo_booked_at')->nullable();
            $table->string('won_lost_status')->nullable();
            $table->string('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'consent_status']);
            $table->index(['assigned_owner_id', 'next_follow_up_at']);
        });

        Schema::create('platform_marketing_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria')->nullable();
            $table->boolean('is_dynamic')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_segment_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->index();
            $table->foreignId('prospect_id')->index();
            $table->timestamps();
            $table->unique(['segment_id', 'prospect_id'], 'pm_segment_member_unique');
        });

        Schema::create('platform_marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('objective')->nullable();
            $table->string('product')->default('SayaraForce')->index();
            $table->foreignId('segment_id')->nullable()->index();
            $table->string('template_name')->nullable();
            $table->string('template_language')->default('en_US');
            $table->json('template_variables')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->unsignedInteger('batch_size')->default(25);
            $table->unsignedInteger('delay_between_batches')->default(300);
            $table->unsignedInteger('daily_cap')->default(100);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->json('safety_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->index();
            $table->foreignId('prospect_id')->index();
            $table->string('normalized_phone')->index();
            $table->string('status')->default('queued')->index();
            $table->string('idempotency_key')->unique();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'prospect_id'], 'pm_campaign_recipient_unique');
        });

        Schema::create('platform_marketing_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->index();
            $table->foreignId('campaign_id')->nullable()->index();
            $table->foreignId('channel_id')->nullable()->index();
            $table->string('state')->default('greeting')->index();
            $table->string('qualification_status')->default('unqualified')->index();
            $table->boolean('ai_enabled')->default(true);
            $table->boolean('human_takeover')->default(false)->index();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable()->index();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->index();
            $table->foreignId('prospect_id')->index();
            $table->foreignId('campaign_id')->nullable()->index();
            $table->string('direction')->index();
            $table->string('actor')->default('system')->index();
            $table->string('message_type')->default('text');
            $table->text('body');
            $table->string('provider_message_id')->nullable()->index();
            $table->string('provider_status')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->index();
            $table->foreignId('prospect_id')->index();
            $table->string('prompt_version')->default('platform-sales-v1');
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->string('status')->default('fallback')->index();
            $table->string('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->index();
            $table->foreignId('conversation_id')->nullable()->index();
            $table->string('status')->default('requested')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone')->default('Asia/Dubai');
            $table->string('meeting_mode')->default('online');
            $table->string('meeting_link')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('assigned_salesperson_id')->nullable()->index();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->nullable()->index();
            $table->string('normalized_phone')->unique();
            $table->string('reason')->nullable();
            $table->string('source')->default('whatsapp');
            $table->timestamp('opted_out_at')->index();
            $table->timestamps();
        });

        Schema::create('platform_marketing_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->nullable()->index();
            $table->foreignId('campaign_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('action')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_marketing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });

        Schema::create('platform_marketing_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->json('mapping')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'platform_marketing_imports',
            'platform_marketing_settings',
            'platform_marketing_activity_logs',
            'platform_marketing_opt_outs',
            'platform_marketing_appointments',
            'platform_marketing_ai_runs',
            'platform_marketing_conversation_messages',
            'platform_marketing_conversations',
            'platform_marketing_campaign_recipients',
            'platform_marketing_campaigns',
            'platform_marketing_segment_members',
            'platform_marketing_segments',
            'platform_marketing_prospects',
            'platform_marketing_prospect_sources',
            'platform_marketing_channels',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
