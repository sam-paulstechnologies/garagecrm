<?php

namespace App\Billing;

use App\Billing\Contracts\BillingGateway;
use App\Billing\Data\ProviderCustomer;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Commercial\LaunchOfferService;
use App\Commercial\ProductEventRecorder;
use App\Commercial\ProductEvents;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\Commercial\Subscription;
use App\Models\System\Company;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingManager
{
    public function __construct(
        private readonly BillingGateway $gateway,
        private readonly ProductEventRecorder $productEvents,
        private readonly LaunchOfferService $launchOffer,
    ) {}

    public function startCheckout(Company $company, Price $price, ?string $idempotencyKey = null): BillingCheckoutSession
    {
        if (! config('billing.checkout_enabled')) {
            throw new BillingConfigurationException('Self-service checkout is disabled in this environment.');
        }
        if ((float) $price->list_amount <= 0 || $price->status !== 'active') {
            throw new BillingConfigurationException('The selected price is not eligible for paid checkout.');
        }
        $provider = $this->gateway->provider();
        $key = $idempotencyKey ?: hash('sha256', Str::uuid()->toString());
        if (! preg_match('/^[A-Za-z0-9._:-]{16,64}$/', $key)) {
            throw new BillingConfigurationException('Checkout idempotency key has an invalid format.');
        }

        [$checkout, $subscription, $mapping, $phase, $created] = DB::transaction(function () use ($company, $price, $provider, $key): array {
            $subscription = Subscription::query()
                ->with(['planVersion.plan', 'price'])
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->firstOrFail();
            $phase = $this->launchOffer->phaseForCheckout($subscription, $price);
            $mapping = PriceProviderMapping::query()
                ->where('price_id', $price->id)
                ->where('payment_provider', $provider)
                ->where('price_phase', $phase)
                ->where('status', 'active')
                ->first();
            if (! $mapping) {
                throw new BillingConfigurationException('The selected price is not mapped to the configured billing provider.');
            }

            $this->closeStaleCheckouts($subscription);
            $existing = BillingCheckoutSession::query()
                ->where('payment_provider', $provider)
                ->where('idempotency_key', $key)
                ->first();
            if ($existing) {
                if ((int) $existing->company_id !== (int) $company->id || (int) $existing->requested_price_id !== (int) $price->id) {
                    throw new BillingConfigurationException('Checkout idempotency key was already used for another request.');
                }

                return [$existing, $subscription, $mapping, $phase, false];
            }

            $operation = $subscription->payment_provider === $provider && filled($subscription->provider_subscription_id)
                ? 'plan_change'
                : 'checkout';
            if ($operation === 'plan_change') {
                $pending = BillingCheckoutSession::query()
                    ->where('subscription_id', $subscription->id)
                    ->where('payment_provider', $provider)
                    ->where('operation', 'plan_change')
                    ->whereIn('status', BillingCheckoutSession::ACTIVE_STATUSES)
                    ->orderByDesc('id')
                    ->first();
                if ($pending) {
                    if ((int) $pending->requested_price_id === (int) $price->id && $pending->price_phase === $phase) {
                        return [$pending, $subscription, $mapping, $phase, false];
                    }

                    throw new BillingConfigurationException('Another plan change is already pending. Resume or finish it before selecting a different plan.');
                }
            }

            $checkout = BillingCheckoutSession::query()->create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'requested_price_id' => $price->id,
                'price_phase' => $phase,
                'launch_offer_qualified' => $this->launchOffer->isNewQualification($subscription, $phase),
                'payment_provider' => $provider,
                'operation' => $operation,
                'idempotency_key' => $key,
                'provider_customer_id' => $operation === 'plan_change' ? $subscription->provider_customer_id : null,
                'provider_subscription_id' => $operation === 'plan_change' ? $subscription->provider_subscription_id : null,
                'provider_price_id' => $operation === 'plan_change' ? $mapping->provider_price_id : null,
                'status' => 'creating',
            ]);

            return [$checkout, $subscription, $mapping, $phase, true];
        });

        if (! $created) {
            return $checkout->fresh();
        }

        try {
            $customer = $subscription->payment_provider === $provider && filled($subscription->provider_customer_id)
                ? new ProviderCustomer((string) $subscription->provider_customer_id)
                : $this->gateway->createCustomer($company, 'customer:'.$key);
            $metadata = [
                'local_checkout_id' => $checkout->id,
                'company_id' => $company->id,
                'provider_price_id' => $mapping->provider_price_id,
                'price_phase' => $phase,
            ];
            if ($checkout->operation === 'plan_change') {
                $result = $this->gateway->createPlanChangeCheckout(
                    $customer,
                    (string) $subscription->provider_subscription_id,
                    $mapping,
                    route('admin.billing.success', ['checkout' => $checkout->id]),
                    route('admin.billing.index'),
                    'plan-change:'.$key,
                    $metadata,
                );
                $checkout->refresh();
                $attributes = [
                    'provider_customer_id' => $customer->id,
                    'provider_checkout_id' => $result->id,
                    'provider_subscription_id' => $subscription->provider_subscription_id,
                    'provider_price_id' => $mapping->provider_price_id,
                    'checkout_url' => $result->url,
                    'expires_at' => $result->expiresAt,
                ];
                if ($checkout->status === 'creating') {
                    $attributes['status'] = 'open';
                }
                $checkout->update($attributes);
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
            BillingCheckoutSession::query()
                ->whereKey($checkout->id)
                ->where('status', 'creating')
                ->update(['status' => 'failed', 'updated_at' => now()]);
            Log::warning('Billing checkout preparation failed safely.', [
                'environment' => app()->environment(),
                'provider' => $provider,
                'operation' => $checkout->operation,
                'checkout_id' => $checkout->id,
                'exception' => class_basename($exception),
                'provider_status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'provider_error_type' => $exception instanceof RequestException ? $exception->response->json('error.type') : null,
                'provider_error_code' => $exception instanceof RequestException ? $exception->response->json('error.code') : null,
                'provider_error_param' => $exception instanceof RequestException ? $exception->response->json('error.param') : null,
            ]);
            if ($exception instanceof BillingConfigurationException) {
                throw $exception;
            }

            throw new BillingConfigurationException(
                'The billing provider could not prepare this plan change. Your current plan remains active; please retry shortly.',
                previous: $exception,
            );
        }

        EntitlementAuditLog::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'actor_id' => auth()->id(),
            'event' => 'billing.checkout_created',
            'source' => $provider,
            'context' => [
                'price_code' => $price->code,
                'price_phase' => $phase,
                'checkout_id' => $checkout->id,
            ],
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

    public function pendingPlanChange(Company $company): ?BillingCheckoutSession
    {
        $subscription = $company->subscription()->firstOrFail();
        $this->closeStaleCheckouts($subscription);

        return BillingCheckoutSession::query()
            ->with('requestedPrice.planVersion.plan')
            ->where('subscription_id', $subscription->id)
            ->where('operation', 'plan_change')
            ->whereIn('status', BillingCheckoutSession::ACTIVE_STATUSES)
            ->latest('id')
            ->first();
    }

    private function closeStaleCheckouts(Subscription $subscription): void
    {
        BillingCheckoutSession::query()
            ->where('subscription_id', $subscription->id)
            ->whereIn('status', BillingCheckoutSession::ACTIVE_STATUSES)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);
        BillingCheckoutSession::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', 'creating')
            ->where('created_at', '<=', now()->subMinutes(5))
            ->update(['status' => 'failed', 'updated_at' => now()]);
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

    public function recordProviderCheckoutCompleted(
        BillingCheckoutSession $checkout,
        string $providerCustomerId,
        string $providerSubscriptionId,
        string $providerPriceId,
    ): BillingCheckoutSession {
        return DB::transaction(function () use ($checkout, $providerCustomerId, $providerSubscriptionId, $providerPriceId): BillingCheckoutSession {
            $checkout = BillingCheckoutSession::query()->lockForUpdate()->findOrFail($checkout->id);
            if ($checkout->status === 'completed') {
                return $checkout;
            }

            $mappingMatches = PriceProviderMapping::query()
                ->where('price_id', $checkout->requested_price_id)
                ->where('payment_provider', $checkout->payment_provider)
                ->where('provider_price_id', $providerPriceId)
                ->where('price_phase', $checkout->price_phase)
                ->where('status', 'active')
                ->exists();
            if (! $mappingMatches) {
                throw new BillingConfigurationException('Verified checkout price does not match the requested internal price.');
            }

            $checkout->update([
                'provider_customer_id' => $providerCustomerId,
                'provider_subscription_id' => $providerSubscriptionId,
                'provider_price_id' => $providerPriceId,
                'status' => 'payment_pending',
            ]);
            EntitlementAuditLog::query()->create([
                'company_id' => $checkout->company_id,
                'subscription_id' => $checkout->subscription_id,
                'event' => 'billing.checkout_provider_confirmed',
                'source' => $checkout->payment_provider,
                'context' => ['checkout_id' => $checkout->id, 'activation' => 'verified_invoice_required'],
                'created_at' => now(),
            ]);

            return $checkout->fresh();
        });
    }

    public function activateFromVerifiedPaymentEvent(
        BillingCheckoutSession $checkout,
        string $providerCustomerId,
        string $providerSubscriptionId,
        string $providerPriceId,
        \DateTimeInterface $occurredAt,
        ?\DateTimeInterface $periodStart = null,
        ?\DateTimeInterface $periodEnd = null,
    ): Subscription {
        return DB::transaction(function () use ($checkout, $providerCustomerId, $providerSubscriptionId, $providerPriceId, $occurredAt, $periodStart, $periodEnd): Subscription {
            $checkout = BillingCheckoutSession::query()->with('requestedPrice.planVersion')->lockForUpdate()->findOrFail($checkout->id);
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($checkout->subscription_id);
            $previousPlan = (string) $subscription->planVersion?->plan?->code;
            $previousRank = (int) $subscription->planVersion?->plan?->rank;
            $mappingMatches = PriceProviderMapping::query()
                ->where('price_id', $checkout->requested_price_id)
                ->where('payment_provider', $checkout->payment_provider)
                ->where('provider_price_id', $providerPriceId)
                ->where('price_phase', $checkout->price_phase)
                ->exists();
            if (! $mappingMatches) {
                throw new BillingConfigurationException('Verified checkout price does not match the requested internal price.');
            }

            $price = $checkout->requestedPrice;
            $samePrice = (int) $subscription->price_id === (int) $price->id;
            $continuingPromotion = $checkout->price_phase === 'launch'
                && $subscription->launch_offer_qualified_at
                && ! $subscription->launch_offer_consumed_at;
            $promotionStartedAt = $checkout->price_phase === 'launch'
                ? ($subscription->launch_offer_qualified_at
                    ? ($subscription->promotion_started_at ?? $subscription->launch_offer_qualified_at)
                    : now())
                : null;
            $qualifiedAt = $checkout->price_phase === 'launch'
                ? ($subscription->launch_offer_qualified_at ?? now())
                : $subscription->launch_offer_qualified_at;
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
                'launch_offer_qualified_at' => $qualifiedAt,
                'launch_offer_consumed_at' => $subscription->launch_offer_consumed_at,
                'payment_provider' => $checkout->payment_provider,
                'provider_customer_id' => $providerCustomerId,
                'provider_subscription_id' => $providerSubscriptionId,
                'provider_price_id' => $providerPriceId,
                'payment_status' => 'paid',
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'grace_ends_at' => null,
                'suspended_at' => null,
                'cancellation_requested_at' => null,
                'provider_state_updated_at' => $occurredAt,
                'introductory_cycles_completed' => ($samePrice || $continuingPromotion)
                    ? $subscription->introductory_cycles_completed
                    : 0,
                'standard_price_transition_requested_at' => ($samePrice || $continuingPromotion)
                    ? $subscription->standard_price_transition_requested_at
                    : null,
            ])->save();
            $checkout->update(['status' => 'completed', 'completed_at' => now()]);
            $checkout->company()->update(['plan_id' => $price->planVersion->plan_id]);
            EntitlementAuditLog::query()->create([
                'company_id' => $checkout->company_id,
                'subscription_id' => $subscription->id,
                'event' => 'billing.subscription_activated',
                'source' => $checkout->payment_provider,
                'context' => [
                    'price_code' => $price->code,
                    'price_phase' => $checkout->price_phase,
                    'checkout_id' => $checkout->id,
                ],
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
