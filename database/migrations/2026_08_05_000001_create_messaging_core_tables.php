<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('product_key', 64);
            $table->string('provider', 64);
            $table->string('status', 40)->default('pending');
            $table->string('connection_mode', 64)->nullable();
            $table->string('meta_business_id', 100)->nullable();
            $table->string('waba_id', 100)->nullable();
            $table->string('external_account_id', 100)->nullable();
            $table->text('encrypted_access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'product_key', 'provider'], 'messaging_connection_tenant_product_unique');
            $table->unique(['provider', 'waba_id'], 'messaging_connection_provider_waba_unique');
            $table->index(['product_key', 'status'], 'messaging_connection_product_status_index');
        });

        Schema::create('messaging_phone_numbers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('messaging_connection_id')->constrained('messaging_connections')->cascadeOnDelete();
            $table->string('provider', 64)->default('meta_whatsapp');
            $table->string('phone_number_id', 100);
            $table->string('display_phone_number', 80)->nullable();
            $table->string('verified_name', 255)->nullable();
            $table->string('display_name_status', 64)->nullable();
            $table->string('quality_rating', 40)->nullable();
            $table->string('registration_status', 64)->nullable();
            $table->string('coexistence_status', 64)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamp('last_health_check_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'phone_number_id'], 'messaging_phone_provider_id_unique');
            $table->index(['messaging_connection_id', 'is_primary'], 'messaging_phone_connection_primary_index');
        });

        Schema::create('messaging_onboarding_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('product_key', 64);
            $table->string('provider', 64);
            $table->string('connection_mode', 64);
            $table->char('state_hash', 64)->unique();
            $table->char('nonce_hash', 64);
            $table->string('status', 40)->default('pending');
            $table->string('session_event', 100)->nullable();
            $table->foreignId('messaging_connection_id')->nullable()->constrained('messaging_connections')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'messaging_session_company_status_index');
        });

        Schema::create('messaging_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('messaging_connection_id')->nullable()->constrained('messaging_connections')->nullOnDelete();
            $table->foreignId('messaging_onboarding_session_id')->nullable()->constrained('messaging_onboarding_sessions')->nullOnDelete();
            $table->string('product_key', 64);
            $table->string('consent_version', 40);
            $table->foreignId('accepted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->json('enabled_capabilities');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'product_key', 'accepted_at'], 'messaging_consent_tenant_product_index');
        });

        Schema::create('messaging_connection_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('messaging_connection_id')->constrained('messaging_connections')->cascadeOnDelete();
            $table->string('check_key', 80);
            $table->string('status', 32);
            $table->string('summary', 255);
            $table->string('provider_error_code', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->unique(['messaging_connection_id', 'check_key'], 'messaging_connection_check_unique');
        });

        Schema::create('messaging_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('messaging_connection_id')->nullable()->constrained('messaging_connections')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_key', 64);
            $table->string('operation', 100);
            $table->string('result', 32);
            $table->string('provider_error_code', 100)->nullable();
            $table->unsignedSmallInteger('attempt_number')->nullable();
            $table->string('idempotency_key', 191)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'occurred_at'], 'messaging_audit_company_time_index');
            $table->index(['messaging_connection_id', 'operation'], 'messaging_audit_connection_operation_index');
        });

        Schema::create('messaging_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('messaging_connection_id')->nullable()->constrained('messaging_connections')->nullOnDelete();
            $table->string('product_key', 64)->nullable();
            $table->string('provider', 64);
            $table->char('event_key', 64)->unique();
            $table->string('field', 80);
            $table->string('event_type', 80)->nullable();
            $table->string('provider_event_id', 191)->nullable();
            $table->char('payload_hash', 64);
            $table->string('status', 32)->default('received');
            $table->string('error_code', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status', 'created_at'], 'messaging_webhook_provider_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_webhook_events');
        Schema::dropIfExists('messaging_audit_logs');
        Schema::dropIfExists('messaging_connection_checks');
        Schema::dropIfExists('messaging_consents');
        Schema::dropIfExists('messaging_onboarding_sessions');
        Schema::dropIfExists('messaging_phone_numbers');
        Schema::dropIfExists('messaging_connections');
    }
};
