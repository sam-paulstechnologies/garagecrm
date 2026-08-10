<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('platform', 32)->nullable();
            $table->string('device_name')->nullable();
            $table->char('token_hash', 64);
            $table->text('encrypted_token');
            $table->string('status', 24)->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider', 'token_hash'], 'push_device_tenant_provider_token_uq');
            $table->index(['company_id', 'user_id', 'status'], 'push_device_tenant_user_status_idx');
        });

        Schema::create('notification_intents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 64);
            $table->string('channel', 24)->default('push');
            $table->string('state', 24)->default('pending');
            $table->string('title');
            $table->text('body');
            $table->string('action_url')->nullable();
            $table->json('payload')->nullable();
            $table->string('idempotency_key', 160);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key'], 'notification_intent_tenant_idempotency_uq');
            $table->index(['company_id', 'user_id', 'state'], 'notification_intent_tenant_user_state_idx');
            $table->index(['state', 'available_at'], 'notification_intent_dispatch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_intents');
        Schema::dropIfExists('push_devices');
    }
};
