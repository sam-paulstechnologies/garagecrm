<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_provider_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_id')->constrained('prices')->cascadeOnDelete();
            $table->string('payment_provider', 40);
            $table->string('price_phase', 24)->default('launch');
            $table->string('provider_price_id', 191);
            $table->string('status', 24)->default('active');
            $table->timestamps();
            $table->unique(['payment_provider', 'provider_price_id'], 'price_provider_external_unique');
            $table->unique(['price_id', 'payment_provider', 'price_phase'], 'price_provider_phase_unique');
        });

        Schema::create('billing_checkout_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('requested_price_id')->constrained('prices')->restrictOnDelete();
            $table->string('payment_provider', 40);
            $table->string('operation', 24)->default('checkout');
            $table->string('idempotency_key', 64);
            $table->string('provider_customer_id', 191)->nullable();
            $table->string('provider_checkout_id', 191)->nullable();
            $table->string('checkout_url', 1000)->nullable();
            $table->string('status', 32)->default('creating');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['payment_provider', 'idempotency_key'], 'billing_checkout_idempotency_unique');
            $table->unique(['payment_provider', 'provider_checkout_id'], 'billing_checkout_provider_unique');
            $table->index(['company_id', 'status', 'created_at'], 'billing_checkout_company_status_index');
        });

        Schema::create('billing_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('payment_provider', 40);
            $table->string('provider_invoice_id', 191);
            $table->string('status', 32);
            $table->string('currency', 3)->default('AED');
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('hosted_invoice_url', 500)->nullable();
            $table->timestamps();
            $table->unique(['payment_provider', 'provider_invoice_id'], 'billing_invoice_provider_unique');
            $table->index(['company_id', 'created_at'], 'billing_invoice_company_index');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('grace_ends_at')->nullable()->after('payment_status');
            $table->timestamp('suspended_at')->nullable()->after('grace_ends_at');
            $table->timestamp('cancellation_requested_at')->nullable()->after('suspended_at');
            $table->timestamp('provider_state_updated_at')->nullable()->after('cancellation_requested_at');
            $table->unsignedSmallInteger('introductory_cycles_completed')->default(0)->after('provider_state_updated_at');
            $table->timestamp('standard_price_transition_requested_at')->nullable()->after('introductory_cycles_completed');
        });

        Schema::table('billing_provider_events', function (Blueprint $table): void {
            $table->timestamp('event_created_at')->nullable()->after('event_type');
            $table->string('object_type', 80)->nullable()->after('event_created_at');
            $table->string('object_id', 191)->nullable()->after('object_type');
            $table->json('normalized_payload')->nullable()->after('payload_hash');
            $table->unsignedSmallInteger('attempt_count')->default(1)->after('status');
            $table->string('failure_reason', 120)->nullable()->after('attempt_count');
            $table->index(['payment_provider', 'event_created_at'], 'billing_provider_event_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('billing_provider_events', function (Blueprint $table): void {
            $table->dropIndex('billing_provider_event_order_index');
            $table->dropColumn([
                'event_created_at', 'object_type', 'object_id', 'normalized_payload',
                'attempt_count', 'failure_reason',
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'grace_ends_at', 'suspended_at', 'cancellation_requested_at',
                'provider_state_updated_at', 'introductory_cycles_completed',
                'standard_price_transition_requested_at',
            ]);
        });

        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_checkout_sessions');
        Schema::dropIfExists('price_provider_mappings');
    }
};
