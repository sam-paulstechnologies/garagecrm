<?php

namespace App\Jobs;

use App\Billing\BillingGatewayResolver;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\Commercial\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TransitionIntroductoryBillingPrice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 90;

    public function __construct(public readonly int $subscriptionId)
    {
        $this->onQueue('default');
    }

    public function handle(BillingGatewayResolver $gateways): void
    {
        $subscription = Subscription::query()->with('price')->findOrFail($this->subscriptionId);
        $duration = (int) ($subscription->price->promotion_duration_months ?? 0);
        if ($duration <= 0 || $subscription->introductory_cycles_completed < $duration
            || $subscription->standard_price_transition_requested_at
            || $subscription->price->renewal_behavior !== 'standard_after_promotion') {
            return;
        }
        $mapping = PriceProviderMapping::query()
            ->where('price_id', $subscription->price_id)
            ->where('payment_provider', $subscription->payment_provider)
            ->where('price_phase', 'standard')
            ->where('status', 'active')
            ->firstOrFail();
        $gateways->for((string) $subscription->payment_provider)->changeSubscription(
            (string) $subscription->provider_subscription_id,
            $mapping,
            'standard-price:'.$subscription->id.':'.$subscription->price_id,
            ['price_phase' => 'standard'],
        );

        DB::transaction(function () use ($subscription, $mapping): void {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            if ($locked->standard_price_transition_requested_at) {
                return;
            }
            $locked->update(['standard_price_transition_requested_at' => now()]);
            EntitlementAuditLog::query()->create([
                'company_id' => $locked->company_id,
                'subscription_id' => $locked->id,
                'event' => 'billing.standard_price_transition_requested',
                'source' => $locked->payment_provider,
                'context' => ['provider_price_phase' => $mapping->price_phase],
                'created_at' => now(),
            ]);
        });
    }
}
