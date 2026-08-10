<?php

namespace App\Console\Commands;

use App\Billing\BillingWebhookProcessor;
use App\Billing\Gateways\FakeBillingGateway;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\BillingInvoice;
use App\Models\Commercial\PriceProviderMapping;
use Illuminate\Console\Command;

class ReconcileFakeTestBillingHistory extends Command
{
    protected $signature = 'billing:reconcile-fake-test-history {--confirm}';

    protected $description = 'Create verified fake invoice events for legacy staging checkouts activated before invoice simulation';

    public function handle(FakeBillingGateway $gateway, BillingWebhookProcessor $processor): int
    {
        if (! app()->environment(['staging', 'testing'])
            || config('billing.provider') !== 'fake'
            || config('billing.mode') !== 'test'
            || ! $this->option('confirm')) {
            $this->error('Refused: fake billing reconciliation requires staging/test identity and --confirm.');

            return self::FAILURE;
        }

        $reconciled = 0;
        BillingCheckoutSession::query()
            ->where('payment_provider', 'fake')
            ->where('status', 'completed')
            ->with(['subscription', 'requestedPrice'])
            ->orderBy('id')
            ->each(function (BillingCheckoutSession $checkout) use ($gateway, $processor, &$reconciled): void {
                $subscription = $checkout->subscription;
                $price = $checkout->requestedPrice;
                if (! $subscription || ! $price
                    || $subscription->payment_provider !== 'fake'
                    || $subscription->payment_status !== 'paid'
                    || (int) $subscription->price_id !== (int) $checkout->requested_price_id
                    || BillingInvoice::query()->where('billing_checkout_session_id', $checkout->id)->exists()) {
                    return;
                }

                $mapping = PriceProviderMapping::query()
                    ->where('price_id', $price->id)
                    ->where('payment_provider', 'fake')
                    ->where('status', 'active')
                    ->first();
                $providerCustomerId = (string) ($checkout->provider_customer_id ?: $subscription->provider_customer_id);
                $providerSubscriptionId = (string) ($checkout->provider_subscription_id ?: $subscription->provider_subscription_id);
                $providerPriceId = (string) ($checkout->provider_price_id ?: $subscription->provider_price_id ?: $mapping?->provider_price_id);
                if ($providerCustomerId === '' || $providerSubscriptionId === '' || $providerPriceId === '') {
                    return;
                }

                $paidAt = ($checkout->completed_at ?? now())->copy()->startOfSecond();
                $occurredAt = now()->startOfSecond();
                if ($subscription->provider_state_updated_at && ! $occurredAt->greaterThan($subscription->provider_state_updated_at)) {
                    $occurredAt = $subscription->provider_state_updated_at->copy()->addSecond();
                }
                $periodStart = ($subscription->current_period_start ?? $paidAt)->copy();
                $periodEnd = ($subscription->current_period_end ?? $periodStart->copy()->addMonth())->copy();
                $amountMinor = (int) round(((float) $price->amountAt($paidAt, $subscription->promotion_started_at)) * 100);
                $invoiceId = 'in_fake_test_legacy_'.$checkout->id;
                [$payload, $signature] = $gateway->signedEvent(
                    'invoice.paid',
                    'invoice',
                    $invoiceId,
                    [
                        'local_checkout_id' => $checkout->id,
                        'provider_customer_id' => $providerCustomerId,
                        'provider_subscription_id' => $providerSubscriptionId,
                        'provider_price_id' => $providerPriceId,
                        'provider_invoice_id' => $invoiceId,
                        'currency' => strtolower((string) $price->currency),
                        'amount_due_minor' => $amountMinor,
                        'amount_paid_minor' => $amountMinor,
                        'period_start' => $periodStart->timestamp,
                        'period_end' => $periodEnd->timestamp,
                        'paid_at' => $paidAt->timestamp,
                    ],
                    'evt_fake_test_legacy_invoice_paid_'.$checkout->id,
                    $occurredAt->timestamp,
                );
                $result = $processor->process('fake', $payload, $signature);
                if ($result['status'] === 'processed') {
                    $reconciled++;
                }
            });

        $this->info("Verified fake billing history reconciliation completed: {$reconciled} invoice event(s).");

        return self::SUCCESS;
    }
}
