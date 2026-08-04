<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $this->addCompanyColumn($table, 'meta_phone_number_id', fn (Blueprint $table) => $table->string('meta_phone_number_id', 100)->nullable());
            $this->addCompanyColumn($table, 'meta_access_token', fn (Blueprint $table) => $table->text('meta_access_token')->nullable());
            $this->addCompanyColumn($table, 'meta_verify_token', fn (Blueprint $table) => $table->string('meta_verify_token', 191)->nullable());
            $this->addCompanyColumn($table, 'meta_waba_id', fn (Blueprint $table) => $table->string('meta_waba_id', 100)->nullable());
            $this->addCompanyColumn($table, 'meta_business_id', fn (Blueprint $table) => $table->string('meta_business_id', 100)->nullable());
            $this->addCompanyColumn($table, 'meta_display_phone_number', fn (Blueprint $table) => $table->string('meta_display_phone_number', 50)->nullable());
            $this->addCompanyColumn($table, 'meta_token_expires_at', fn (Blueprint $table) => $table->timestamp('meta_token_expires_at')->nullable());
            $this->addCompanyColumn($table, 'is_whatsapp_active', fn (Blueprint $table) => $table->boolean('is_whatsapp_active')->default(false));
            $this->addCompanyColumn($table, 'whatsapp_connection_mode', fn (Blueprint $table) => $table->string('whatsapp_connection_mode', 50)->default('manual'));
            $this->addCompanyColumn($table, 'whatsapp_coexistence_enabled', fn (Blueprint $table) => $table->boolean('whatsapp_coexistence_enabled')->default(false));
            $this->addCompanyColumn($table, 'whatsapp_coexistence_status', fn (Blueprint $table) => $table->string('whatsapp_coexistence_status', 50)->nullable());
            $this->addCompanyColumn($table, 'whatsapp_onboarding_source', fn (Blueprint $table) => $table->string('whatsapp_onboarding_source', 80)->nullable());
            $this->addCompanyColumn($table, 'whatsapp_connected_at', fn (Blueprint $table) => $table->timestamp('whatsapp_connected_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_last_webhook_at', fn (Blueprint $table) => $table->timestamp('whatsapp_last_webhook_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_last_inbound_at', fn (Blueprint $table) => $table->timestamp('whatsapp_last_inbound_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_last_echo_at', fn (Blueprint $table) => $table->timestamp('whatsapp_last_echo_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_webhook_subscription_status', fn (Blueprint $table) => $table->string('whatsapp_webhook_subscription_status', 32)->nullable());
            $this->addCompanyColumn($table, 'whatsapp_webhook_subscription_checked_at', fn (Blueprint $table) => $table->timestamp('whatsapp_webhook_subscription_checked_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_contact_sync_status', fn (Blueprint $table) => $table->string('whatsapp_contact_sync_status', 32)->nullable());
            $this->addCompanyColumn($table, 'whatsapp_contact_sync_requested_at', fn (Blueprint $table) => $table->timestamp('whatsapp_contact_sync_requested_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_contact_sync_completed_at', fn (Blueprint $table) => $table->timestamp('whatsapp_contact_sync_completed_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_history_sync_status', fn (Blueprint $table) => $table->string('whatsapp_history_sync_status', 32)->nullable());
            $this->addCompanyColumn($table, 'whatsapp_history_sync_requested_at', fn (Blueprint $table) => $table->timestamp('whatsapp_history_sync_requested_at')->nullable());
            $this->addCompanyColumn($table, 'whatsapp_history_sync_completed_at', fn (Blueprint $table) => $table->timestamp('whatsapp_history_sync_completed_at')->nullable());
        });

        if (Schema::hasTable('message_logs')) {
            Schema::table('message_logs', function (Blueprint $table) {
                if (Schema::hasColumn('message_logs', 'provider_message_id')) {
                    $table->string('provider_message_id', 191)->nullable()->change();
                }
                if (! Schema::hasColumn('message_logs', 'conversation_id')) {
                    $table->unsignedBigInteger('conversation_id')->nullable()->index();
                }
                if (! Schema::hasColumn('message_logs', 'source')) {
                    $table->string('source', 50)->nullable()->index();
                }
            });
        }

        if (! Schema::hasTable('whatsapp_connect_sessions')) {
            Schema::create('whatsapp_connect_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('state', 191)->unique();
                $table->string('connection_mode', 50);
                $table->string('status', 32)->default('started')->index();
                $table->string('session_event', 80)->nullable();
                $table->string('meta_business_id', 100)->nullable();
                $table->string('waba_id', 100)->nullable();
                $table->string('phone_number_id', 100)->nullable();
                $table->string('display_phone_number', 50)->nullable();
                $table->string('error_code', 80)->nullable();
                $table->text('error_message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('processing_started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        } else {
            Schema::table('whatsapp_connect_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_connect_sessions', 'connection_mode')) {
                    $table->string('connection_mode', 50)->nullable()->after('state');
                }
                if (! Schema::hasColumn('whatsapp_connect_sessions', 'session_event')) {
                    $table->string('session_event', 80)->nullable()->after('status');
                }
                if (! Schema::hasColumn('whatsapp_connect_sessions', 'error_code')) {
                    $table->string('error_code', 80)->nullable()->after('display_phone_number');
                }
                if (! Schema::hasColumn('whatsapp_connect_sessions', 'processing_started_at')) {
                    $table->timestamp('processing_started_at')->nullable()->after('started_at');
                }
                if (! Schema::hasColumn('whatsapp_connect_sessions', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('completed_at');
                }
            });
        }

        if (! Schema::hasTable('whatsapp_connection_audits')) {
            Schema::create('whatsapp_connection_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event', 80)->index();
                $table->string('status', 32)->index();
                $table->string('connection_mode', 50)->nullable();
                $table->string('waba_id', 100)->nullable();
                $table->string('phone_number_id', 100)->nullable();
                $table->json('context')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('whatsapp_webhook_events')) {
            Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('event_key', 64)->unique();
                $table->string('field', 80)->index();
                $table->string('event_type', 80)->nullable()->index();
                $table->string('provider_event_id', 191)->nullable()->index();
                $table->string('payload_hash', 64);
                $table->longText('payload')->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->string('error_code', 80)->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('whatsapp_synced_contacts')) {
            Schema::create('whatsapp_synced_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('phone_number_id', 100)->index();
                $table->string('contact_hash', 64);
                $table->text('contact_phone')->nullable();
                $table->text('full_name')->nullable();
                $table->text('first_name')->nullable();
                $table->string('sync_action', 16);
                $table->string('status', 24)->default('active');
                $table->timestamp('provider_timestamp')->nullable();
                $table->timestamp('last_synced_at');
                $table->timestamps();
                $table->unique(['company_id', 'phone_number_id', 'contact_hash'], 'wa_synced_contacts_tenant_unique');
            });
        }

        if (! Schema::hasTable('whatsapp_history_messages')) {
            Schema::create('whatsapp_history_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->string('phone_number_id', 100)->index();
                $table->string('source_fingerprint', 64)->unique();
                $table->string('provider_message_id', 191)->nullable()->index();
                $table->string('direction', 8);
                $table->string('message_type', 40)->nullable();
                $table->text('customer_identifier')->nullable();
                $table->longText('body')->nullable();
                $table->longText('metadata')->nullable();
                $table->timestamp('message_timestamp')->nullable()->index();
                $table->timestamps();
                $table->index(['company_id', 'phone_number_id', 'message_timestamp'], 'wa_history_tenant_phone_time');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_history_messages');
        Schema::dropIfExists('whatsapp_synced_contacts');
        Schema::dropIfExists('whatsapp_webhook_events');
        Schema::dropIfExists('whatsapp_connection_audits');
    }

    private function addCompanyColumn(Blueprint $table, string $column, callable $definition): void
    {
        if (! Schema::hasColumn('companies', $column)) {
            $definition($table);
        }
    }
};
