<?php

namespace App\Billing;

use App\Billing\Contracts\BillingGateway;
use App\Billing\Data\ProviderCustomer;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Commercial\ProductEventRecorder;
use App\Commercial\ProductEvents;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\Commercial\Subscription;
use App\Models\System\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingManager
{
    public function __construct(
        private readonly BillingGateway $gateway,
        private readonly ProductEventRecorder $productEvents,
    ) {}

    public function startCheckout(Company $company, Price $price, ?string $idempotencyKey = null): BillingCheckoutSession
    {
        if (! config('billing.checkout_enabled')) {
            throw new BillingConfigurationException('Self-service checkout is disabled in this environment.');
        }
        if ((float) $price->list_amount <= 0 || $price->status !== 'active') {
            throw new BillingConfigurationException('The selected price is not eligible for paid checkout.');
        }
        $subscription = $company->subscription()->firstOrFail();
        $provider = $this->gateway->provider();
        $mapping = PriceProviderMapping::query()
            ->where('price_id', $price->id)
            ->where('payment_provider', $provider)
            ->where('price_phase', 'launch')
            ->where('status', 'active')
            ->first();
        if (! $mapping) {
            throw new BillingConfigurationException('The selected price is not mapped to the configured billing provider.');
        }

        $key = $idempotencyKey ?: hash('sha256', Str::uuid()->toString());
        if (! preg_match('/^[A-Za-z0-9._:-]{16,64}$/', $key)) {
            throw new BillingConfigurationException('Checkout idempotency key has an invalid format.');
        }
        $existing = BillingCheckoutSession::query()
            ->where('payment_provider', $provider)
            ->where('idempotency_key', $key)
            ->first();
        if ($existing) {
            if ((int) $existing->company_id !== (int) $company->id || (int) $existing->requested_price_id !== (int) $price->id) {
                throw new BillingConfigurationException('Checkout idempotency key was already used for another request.');
            }

            return $existing;
        }

        $checkout = BillingCheckoutSession::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'requested_price_id' => $price->id,
            'payment_provider' => $provider,
            'operation' => $subscription->payment_provider === $provider && filled($subscription->provider_subscription_id)
                ? 'plan_change'
                : 'checkout',
            'idempotency_key' => $key,
            'status' => 'creating',
        ]);

        try {
            $customer = $subscription->payment_provider === $provider && filled($subscription->provider_customer_id)
                ? new ProviderCustomer((string) $subscription->provider_customer_id)
                : $this->gateway->createCustomer($company, 'customer:'.$key);
            $metadata = [
                'local_checkout_id' => $checkout->id,
                'company_id' => $company->id,
                'provider_price_id' => $mapping->provider_price_id,
            ];
            if ($checkout->operation === 'plan_change') {
                $this->gateway->changeSubscription(
                    (string) $subscription->provider_subscription_id,
                    $mapping,
                    'plan-change:'.$key,
                    $metadata,
                );
                $checkout->update([
                    'provider_customer_id' => $customer->id,
                    'checkout_url' => $provider === 'fake'
                        ? route('admin.billing.fake.show', ['billingCheckoutSession' => $checkout->id])
                        : null,
                    'status' => 'pending_provider',
                ]);
            } else {
                $result = $this->gateway->createCheckout(
                    $customer,
                    $mapping,
                    route('admin.billing.success', ['checkout' => $checkout->id]),
                    route('admin.billing.index'),
                    'checkout:'.$key,
                    $metadata,
                );
                $checkout->update([
                    'provider_customer_id' => $customer->id,
                    'provider_checkout_id' => $result->id,
                    'checkout_url' => $result->url,
                    'status' => 'open',
                    'expires_at' => $result->expiresAt,
                ]);
            }
        } catch (\Throwable $exception) {
            $checkout->update(['status' => 'failed']);
            throw $exception;
        }

        EntitlementAuditLog::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'actor_id' => auth()->id(),
            'event' => 'billing.checkout_created',
            'source' => $provider,
            'context' => ['price_code' => $price->code, 'checkout_id' => $checkout->id],
            'created_at' => now(),
        ]);
        $this->productEvents->recordSafely(
            ProductEvents::CHECKOUT_STARTED,
            $company,
            auth()->user(),
            [
                'current_plan' => (string) $subscription->planVersion?->plan?->code,
                'target_plan' => (string) $price->planVersion?->plan?->code,
                'provider' => $provider,
            ],
            'checkout-started:'.$checkout->id,
        );

        return $checkout->fresh();
    }

    public function requestCancellation(Company $company): Subscription
    {
        $subscription = $company->subscription()->firstOrFail();
        if ($subscription->payment_provider !== $this->gateway->provider() || blank($subscription->provider_subscription_id)) {
            throw new BillingConfigurationException('There is no cancellable provider subscription for this tenant.');
        }
        $this->gateway->cancelSubscription((string) $subscription->provider_subscription_id, true);
        $subscription->update(['cancellation_requested_at' => now()]);
        EntitlementAuditLog::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'actor_id' => auth()->id(),
            'event' => 'billing.cancellation_requested',
            'source' => $this->gateway->provider(),
            'context' => ['effective' => 'provider_webhook_required'],
            'created_at' => now(),
        ]);

        return $subscription->fresh();
    }

    public function portal(Company $company): string
    {
        $subscription = $company->subscription()->firstOrFail();
        if ($subscription->payment_provider !== $this->gateway->provider() || blank($subscription->provider_customer_id)) {
            throw new BillingConfigurationException('There is no provider billing portal for this tenant.');
        }

        return $this->gateway->createBillingPortal((string) $subscription->provider_customer_id, route('admin.billing.index'));
    }

    public function activateFromVerifiedEvent(
        BillingCheckoutSession $checkout,
        string $providerCustomerId,
        string $providerSubscriptionId,
        string $providerPriceId,
        string $paymentStatus,
        \DateTimeInterface $occurredAt,
        ?\DateTimeInterface $periodStart = null,
        ?\DateTimeInterface $periodEnd = null,
    ): Subscription {
        return DB::transaction(function () use ($checkout, $providerCustomerId, $providerSubscriptionId, $providerPriceId, $paymentStatus, $occurredAt, $periodStart, $periodEnd): Subscription {
            $checkout = BillingCheckoutSession::query()->with('requestedPrice.planVersion')->lockForUpdate()->findOrFail($checkout->id);
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($checkout->subscription_id);
            $previousPlan = (string) $subscription->planVersion?->plan?->code;
            $previousRank = (int) $subscription->planVersion?->plan?->rank;
            $mappingMatches = PriceProviderMapping::query()
                ->where('price_id', $checkout->requested_price_id)
                ->where('payment_provider', $checkout->payment_provider)
                ->where('provider_price_id', $providerPriceId)
                ->exists();
            if (! $mappingMatches) {
                throw new BillingConfigurationException('Verified checkout price does not match the requested internal price.');
            }

            $price = $checkout->requestedPrice;
            $samePrice = (int) $subscription->price_id === (int) $price->id;
            $promotionStartedAt = $samePrice
                ? $subscription->promotion_started_at
                : ($price->isPromotionAvailableAt(now()) ? now() : null);
            $subscription->forceFill([
                'plan_version_id' => $price->plan_version_id,
                'price_id' => $price->id,
                'status' => 'active',
                'started_at' => $samePrice ? $subscription->started_at : now(),
                'current_period_start' => $periodStart ?? now(),
                'current_period_end' => $periodEnd ?? now()->addMonth(),
                'promotion_started_at' => $promotionStartedAt,
                'promotion_ends_at' => $promotionStartedAt && $price->promotion_duration_months
                    ? $promotionStartedAt->copy()->addMonths($price->promotion_duration_months)
                    : null,
                'payment_provider' => $checkout->payment_provider,
                'provider_customer_id' => $providerCustomerId,
                'provider_subscription_id' => $providerSubscriptionId,
                'provider_price_id' => $providerPriceId,
                'payment_status' => $paymentStatus,
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'grace_ends_at' => null,
                'suspended_at' => null,
                'cancellation_requested_at' => null,
                'provider_state_updated_at' => $occurredAt,
                'introductory_cycles_completed' => $samePrice ? $subscription->introductory_cycles_completed : 0,
                'standard_price_transition_requested_at' => $samePrice ? $subscription->standard_price_transition_requested_at : null,
            ])->save();
            $checkout->update(['status' => 'completed', 'completed_at' => now()]);
            $checkout->company()->update(['plan_id' => $price->planVersion->plan_id]);
            EntitlementAuditLog::query()->create([
                'company_id' => $checkout->company_id,
                'subscription_id' => $subscription->id,
                'event' => 'billing.subscription_activated',
                'source' => $checkout->payment_provider,
                'context' => ['price_code' => $price->code, 'checkout_id' => $checkout->id],
                'created_at' => now(),
            ]);
            $nextPlan = (string) $price->planVersion?->plan?->code;
            $this->productEvents->recordSafely(
                ProductEvents::SUBSCRIPTION_ACTIVATED,
                $checkout->company,
                properties: ['plan_code' => $nextPlan, 'provider' => $checkout->payment_provider],
                dedupeKey: 'subscription-activated:'.$checkout->id,
            );
            if ($previousPlan !== '' && $nextPlan !== '' && $previousPlan !== $nextPlan) {
                $nextRank = (int) $price->planVersion?->plan?->rank;
                $this->productEvents->recordSafely(
                    $nextRank >= $previousRank ? ProductEvents::PLAN_UPGRADED : ProductEvents::PLAN_DOWNGRADED,
                    $checkout->company,
                    properties: [
                        'from_plan' => $previousPlan,
                        'to_plan' => $nextPlan,
                        'provider' => $checkout->payment_provider,
                    ],
                    dedupeKey: 'plan-transition:'.$checkout->id,
                );
            }

            return $subscription->fresh();
        });
    }
}
