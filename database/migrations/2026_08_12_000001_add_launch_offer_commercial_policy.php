<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->boolean('boolean_value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('billing_checkout_sessions', function (Blueprint $table): void {
            $table->string('price_phase', 24)->default('standard')->after('requested_price_id');
            $table->boolean('launch_offer_qualified')->default(false)->after('price_phase');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('launch_offer_qualified_at')->nullable()->after('promotion_ends_at');
            $table->timestamp('launch_offer_consumed_at')->nullable()->after('launch_offer_qualified_at');
        });

        DB::table('commercial_settings')->insert([
            'key' => 'launch_offer_enabled',
            'boolean_value' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prices')
            ->whereIn('code', collect(['free', 'service', 'growth', 'performance', 'ai_pro'])
                ->map(fn (string $plan): string => $plan.':2026-launch-v1:aed-monthly'))
            ->where('promotion_duration_months', 12)
            ->update(['promotion_duration_months' => 3]);

        $promotionalPriceIds = DB::table('prices')
            ->whereColumn('promotional_amount', '<', 'list_amount')
            ->pluck('id');
        DB::table('subscriptions')
            ->whereIn('price_id', $promotionalPriceIds)
            ->whereNotNull('promotion_started_at')
            ->whereNotNull('payment_provider')
            ->update(['launch_offer_qualified_at' => DB::raw('promotion_started_at')]);
    }

    public function down(): void
    {
        DB::table('prices')
            ->whereIn('code', collect(['free', 'service', 'growth', 'performance', 'ai_pro'])
                ->map(fn (string $plan): string => $plan.':2026-launch-v1:aed-monthly'))
            ->where('promotion_duration_months', 3)
            ->update(['promotion_duration_months' => 12]);

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['launch_offer_qualified_at', 'launch_offer_consumed_at']);
        });
        Schema::table('billing_checkout_sessions', function (Blueprint $table): void {
            $table->dropColumn(['price_phase', 'launch_offer_qualified']);
        });
        Schema::dropIfExists('commercial_settings');
    }
};
