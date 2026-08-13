<?php

namespace App\Billing;

use App\Billing\Exceptions\BillingConfigurationException;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\BillingInvoice;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\Commercial\Subscription;
use Carbon\CarbonImmutable;

class ProviderSubscriptionReconciler
{
    public function __construct(private readonly BillingManager $billing) {}

    /**
     * Converge an authorized local plan change only after both provider facts
     * exist: a verified paid target invoice and a verified subscription state
     * on the same mapped target Price.
     *
     * @param  array<string, mixed>  $providerState
     */
    public function reconcile(
        Subscription $subscription,
        string $provider,
        array $providerState,
        CarbonImmutable $occurredAt,
        ?BillingCheckoutSession $checkout = null,
    ): string {
        $providerSubscriptionId = (string) ($providerState['provider_subscription_id'] ?? '');
        $providerCustomerId = (string) ($providerState['provider_customer_id'] ?? $subscription->provider_customer_id ?? '');
        $providerPriceId = (string) ($providerState['provider_price_id'] ?? '');
        if ($providerSubscriptionId === '' || $providerPriceId === '') {
            throw new BillingConfigurationException('Verified provider subscription state is incomplete.');
        }
        if ($subscription->payment_provider !== $provider
            || ! hash_equals((string) $subscription->provider_subscription_id, $providerSubscriptionId)) {
            throw new BillingConfigurationException('Verified provider subscription does not match the tenant subscription.');
        }
        if (filled($subscription->provider_customer_id)
            && $providerCustomerId !== ''
            && ! hash_equals((string) $subscription->provider_customer_id, $providerCustomerId)) {
            throw new BillingConfigurationException('Verified provider customer does not match the tenant subscription.');
        }

        $mapping = PriceProviderMapping::query()
            ->with('price.planVersion.plan')
            ->where('payment_provider', $provider)
            ->where('provider_price_id', $providerPriceId)
            ->where('status', 'active')
            ->first();
        if (! $mapping) {
            throw new BillingConfigurationException('Verified provider subscription references an unapproved price mapping.');
        }
        if ((int) $subscription->price_id === (int) $mapping->price_id) {
            return 'same_price';
        }

        $checkout = $this->authorizedPlanChange(
            $subscription,
            $mapping->price_id,
            $providerSubscriptionId,
            $checkout,
        );
        $paidInvoice = BillingInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('payment_provider', $provider)
            ->where('price_id', $mapping->price_id)
            ->where('status', 'paid')
            ->when(
                filled($checkout->provider_checkout_id),
                fn ($query) => $query->where(function ($query) use ($checkout): void {
                    $query->where('provider_invoice_id', $checkout->provider_checkout_id)
                        ->orWhere('billing_checkout_session_id', $checkout->id);
                }),
            )
            ->latest('id')
            ->first();

        if (! $paidInvoice) {
            if (in_array($checkout->status, BillingCheckoutSession::ACTIVE_STATUSES, true)
                && $checkout->status !== 'payment_pending') {
                $checkout->update(['status' => 'payment_pending']);
            }

            return 'pending_payment';
        }

        if ((int) $paidInvoice->billing_checkout_session_id !== (int) $checkout->id) {
            $paidInvoice->update(['billing_checkout_session_id' => $checkout->id]);
        }
        $this->billing->activateFromVerifiedPaymentEvent(
            $checkout,
            $providerCustomerId,
            $providerSubscriptionId,
            $providerPriceId,
            $occurredAt,
            $this->date($providerState['period_start'] ?? null),
            $this->date($providerState['period_end'] ?? null),
        );

        return 'completed';
    }

    private function authorizedPlanChange(
        Subscription $subscription,
        int $targetPriceId,
        string $providerSubscriptionId,
        ?BillingCheckoutSession $checkout,
    ): BillingCheckoutSession {
        if ($checkout
            && $checkout->operation === 'plan_change'
            && (int) $checkout->subscription_id === (int) $subscription->id
            && (int) $checkout->requested_price_id === $targetPriceId
            && hash_equals((string) $checkout->provider_subscription_id, $providerSubscriptionId)
            && ! in_array($checkout->status, ['cancelled', 'payment_failed'], true)) {
            return $checkout;
        }

        $checkout = BillingCheckoutSession::query()
            ->where('subscription_id', $subscription->id)
            ->where('payment_provider', $subscription->payment_provider)
            ->where('operation', 'plan_change')
            ->where('requested_price_id', $targetPriceId)
            ->where('provider_subscription_id', $providerSubscriptionId)
            ->whereNotIn('status', ['cancelled', 'payment_failed'])
            ->whereNotNull('provider_checkout_id')
            ->latest('id')
            ->first();
        if (! $checkout) {
            throw new BillingConfigurationException('Provider plan change has no authorized local target context.');
        }

        return $checkout;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        return is_numeric($value) ? CarbonImmutable::createFromTimestampUTC((int) $value) : null;
    }
}
