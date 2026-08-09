<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->reconcileLegacyPlans();

        Schema::create('plan_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('code', 100)->unique();
            $table->unsignedInteger('version');
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->unique(['plan_id', 'version']);
        });

        Schema::create('prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_version_id')->constrained('plan_versions')->cascadeOnDelete();
            $table->string('code', 120)->unique();
            $table->string('currency', 3)->default('AED');
            $table->string('interval', 20)->default('month');
            $table->decimal('list_amount', 12, 2);
            $table->decimal('promotional_amount', 12, 2)->nullable();
            $table->timestamp('promotion_effective_from')->nullable();
            $table->timestamp('promotion_effective_to')->nullable();
            $table->unsignedSmallInteger('promotion_duration_months')->nullable();
            $table->string('renewal_behavior', 64)->default('same_price');
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->index(['plan_version_id', 'status']);
        });

        Schema::create('plan_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_version_id')->constrained('plan_versions')->cascadeOnDelete();
            $table->string('capability', 120);
            $table->string('value_type', 32)->default('boolean');
            $table->json('value')->nullable();
            $table->bigInteger('allowance')->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('mode', 40)->nullable();
            $table->timestamps();
            $table->unique(['plan_version_id', 'capability'], 'plan_entitlements_version_cap_unique');
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('plan_version_id')->constrained('plan_versions')->restrictOnDelete();
            $table->foreignId('price_id')->constrained('prices')->restrictOnDelete();
            $table->string('status', 40)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('promotion_started_at')->nullable();
            $table->timestamp('promotion_ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('grandfathered')->default(false);
            $table->json('grandfathered_snapshot')->nullable();
            $table->string('payment_provider', 40)->nullable();
            $table->string('provider_customer_id', 191)->nullable();
            $table->string('provider_subscription_id', 191)->nullable();
            $table->string('provider_price_id', 191)->nullable();
            $table->string('payment_status', 40)->nullable();
            $table->timestamps();
            $table->index(['status', 'current_period_end']);
            $table->unique(['payment_provider', 'provider_subscription_id'], 'subscriptions_provider_unique');
        });

        Schema::create('company_entitlement_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('capability', 120);
            $table->string('value_type', 32)->default('boolean');
            $table->json('value')->nullable();
            $table->bigInteger('allowance')->nullable();
            $table->boolean('enabled')->nullable();
            $table->string('mode', 40)->nullable();
            $table->text('reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'capability', 'expires_at'], 'company_overrides_lookup_index');
        });

        Schema::create('entitlement_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('capability', 120);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedBigInteger('used')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'capability', 'period_start'], 'entitlement_usage_period_unique');
        });

        Schema::create('entitlement_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->string('capability', 120)->nullable();
            $table->boolean('decision')->nullable();
            $table->string('source', 40)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['company_id', 'event', 'created_at']);
        });

        Schema::create('billing_provider_events', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_provider', 40);
            $table->string('provider_event_id', 191);
            $table->string('event_type', 120)->nullable();
            $table->string('payload_hash', 64);
            $table->string('status', 40)->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['payment_provider', 'provider_event_id'], 'billing_provider_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_provider_events');
        Schema::dropIfExists('entitlement_audit_logs');
        Schema::dropIfExists('entitlement_usages');
        Schema::dropIfExists('company_entitlement_overrides');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_entitlements');
        Schema::dropIfExists('prices');
        Schema::dropIfExists('plan_versions');

        // Reconciled legacy plan columns are intentionally retained on rollback.
        // They may have existed before this migration in production-derived schemas.
    }

    private function reconcileLegacyPlans(): void
    {
        $columns = [
            'price' => fn (Blueprint $table) => $table->decimal('price', 12, 2)->default(0),
            'currency' => fn (Blueprint $table) => $table->string('currency', 3)->default('AED'),
            'whatsapp_limit' => fn (Blueprint $table) => $table->unsignedInteger('whatsapp_limit')->nullable(),
            'user_limit' => fn (Blueprint $table) => $table->unsignedInteger('user_limit')->nullable(),
            'status' => fn (Blueprint $table) => $table->boolean('status')->default(true),
            'code' => fn (Blueprint $table) => $table->string('code', 100)->nullable(),
            'rank' => fn (Blueprint $table) => $table->unsignedInteger('rank')->default(0),
            'description' => fn (Blueprint $table) => $table->text('description')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('plans', $column)) {
                Schema::table('plans', $definition);
            }
        }

        if (Schema::hasColumn('plans', 'price_monthly')) {
            DB::table('plans')->where('price', 0)->update(['price' => DB::raw('price_monthly')]);
        }
        if (Schema::hasColumn('plans', 'max_users')) {
            DB::table('plans')->whereNull('user_limit')->update(['user_limit' => DB::raw('max_users')]);
        }

        DB::table('plans')->whereNull('code')->orWhere('code', '')->orderBy('id')->get(['id', 'name'])
            ->each(function (object $plan): void {
                DB::table('plans')->where('id', $plan->id)->update([
                    'code' => 'legacy_'.$plan->id.'_'.(Str::slug((string) $plan->name, '_') ?: 'plan'),
                ]);
            });

        if (! Schema::hasIndex('plans', 'plans_code_unique')) {
            Schema::table('plans', fn (Blueprint $table) => $table->unique('code', 'plans_code_unique'));
        }
    }
};
