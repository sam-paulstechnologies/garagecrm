<?php

namespace App\Console\Commands;

use App\Billing\Gateways\StripeBillingGateway;
use App\Billing\ProviderSubscriptionReconciler;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\BillingInvoice;
use App\Models\Commercial\BillingProviderEvent;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\Commercial\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReconcileStripeSandboxSubscription extends Command
{
    protected $signature = 'billing:reconcile-stripe-sandbox-subscription
        {subscription : Local subscription ID}
        {--dry-run : Verify provider truth and report the proposed local convergence}
        {--confirm : Apply the verified local reconciliation}';

    protected $description = 'Reconcile one staging subscription from verified Stripe Sandbox state without provider writes';

    public function handle(
        StripeBillingGateway $stripe,
        ProviderSubscriptionReconciler $reconciler,
    ): int {
        if (! app()->environment('staging') || getenv('WEBSITE_SITE_NAME') !== 'app-sayaraforce-staging') {
            $this->error('Refused: Stripe reconciliation is restricted to the exact staging runtime.');

            return self::FAILURE;
        }
        $dryRun = (bool) $this->option('dry-run');
        $confirm = (bool) $this->option('confirm');
        if ($dryRun === $confirm) {
            $this->error('Refused: specify exactly one of --dry-run or --confirm.');

            return self::FAILURE;
        }

        try {
            $subscription = Subscription::query()
                ->with(['planVersion.plan', 'price'])
                ->findOrFail((int) $this->argument('subscription'));
            if ($subscription->payment_provider !== 'stripe' || blank($subscription->provider_subscription_id)) {
                throw new RuntimeException('The local subscription is not mapped to Stripe.');
            }
            $stripe->assertSandboxConfiguration();
            $snapshot = $stripe->retrieveReconciliationSnapshot((string) $subscription->provider_subscription_id);
            $this->assertSnapshotMatches($subscription, $snapshot);
            $targetMapping = $this->mapping((string) $snapshot['provider_price_id']);
            $paidInvoices = collect((array) $snapshot['invoices'])
                ->filter(fn (array $invoice): bool => $invoice['status'] === 'paid')
                ->values();
            if ($paidInvoices->isEmpty()) {
                throw new RuntimeException('Stripe returned no verified paid invoice for this subscription.');
            }

            $this->line(sprintf(
                '%s: %s -> %s, %d verified paid invoice(s), provider writes: none.',
                $dryRun ? 'DRY RUN' : 'CONFIRMED',
                $subscription->planVersion?->plan?->code,
                $targetMapping->price?->planVersion?->plan?->code,
                $paidInvoices->count(),
            ));
            if ($dryRun) {
                return self::SUCCESS;
            }

            DB::transaction(function () use ($subscription, $snapshot, $paidInvoices, $targetMapping, $reconciler): void {
                $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
                $targetCheckout = null;
                foreach ($paidInvoices as $providerInvoice) {
                    $mapping = $this->mapping((string) $providerInvoice['provider_price_id']);
                    $localInvoice = BillingInvoice::query()
                        ->where('payment_provider', 'stripe')
                        ->where('provider_invoice_id', $providerInvoice['provider_invoice_id'])
                        ->lockForUpdate()
                        ->first();
                    if (! $localInvoice) {
                        throw new RuntimeException('A verified provider invoice is absent from local billing history.');
                    }
                    $checkout = BillingCheckoutSession::query()
                        ->where('subscription_id', $subscription->id)
                        ->where('payment_provider', 'stripe')
                        ->where('provider_checkout_id', $providerInvoice['provider_invoice_id'])
                        ->latest('id')
                        ->first();
                    if (! $checkout && $localInvoice->billing_checkout_session_id) {
                        $checkout = BillingCheckoutSession::query()
                            ->whereKey($localInvoice->billing_checkout_session_id)
                            ->where('subscription_id', $subscription->id)
                            ->where('requested_price_id', $mapping->price_id)
                            ->first();
                    }
                    $countsAsFullCycle = in_array(
                        $providerInvoice['billing_reason'] ?? null,
                        ['subscription_create', 'subscription_cycle'],
                        true,
                    );
                    $localInvoice->update([
                        'billing_checkout_session_id' => $checkout?->id ?? $localInvoice->billing_checkout_session_id,
                        'price_id' => $mapping->price_id,
                        'billing_reason' => $providerInvoice['billing_reason'] ?? null,
                        'introductory_cycle_counted' => $countsAsFullCycle,
                        'provider_line_snapshot' => $providerInvoice['invoice_lines'] ?? null,
                    ]);
                    if ((int) $mapping->price_id === (int) $targetMapping->price_id) {
                        $targetCheckout = $checkout;
                    }
                }
                if (! $targetCheckout) {
                    throw new RuntimeException('The verified provider target has no authorized local plan-change context.');
                }

                $result = $reconciler->reconcile(
                    $subscription,
                    'stripe',
                    $snapshot,
                    CarbonImmutable::now('UTC'),
                    $targetCheckout,
                );
                if (! in_array($result, ['completed', 'same_price'], true)) {
                    throw new RuntimeException('Verified provider state could not complete local reconciliation.');
                }
                $subscription->refresh();
                $subscription->update([
                    'introductory_cycles_completed' => BillingInvoice::query()
                        ->where('subscription_id', $subscription->id)
                        ->where('price_id', $subscription->price_id)
                        ->where('status', 'paid')
                        ->where('introductory_cycle_counted', true)
                        ->count(),
                ]);
                BillingProviderEvent::query()
                    ->where('payment_provider', 'stripe')
                    ->where('event_type', 'customer.subscription.updated')
                    ->where('status', 'failed')
                    ->get()
                    ->filter(fn (BillingProviderEvent $event): bool => ($event->normalized_payload['provider_subscription_id'] ?? null) === $snapshot['provider_subscription_id']
                        && ($event->normalized_payload['provider_price_id'] ?? null) === $snapshot['provider_price_id'])
                    ->each->update([
                        'status' => 'processed_reconciled',
                        'processed_at' => now(),
                        'failure_reason' => null,
                    ]);
                $auditExists = EntitlementAuditLog::query()
                    ->where('company_id', $subscription->company_id)
                    ->where('subscription_id', $subscription->id)
                    ->where('event', 'billing.provider_subscription_reconciled')
                    ->where('source', 'stripe_api_read_only')
                    ->where('context->price_code', $targetMapping->price->code)
                    ->exists();
                if (! $auditExists) {
                    EntitlementAuditLog::query()->create([
                        'company_id' => $subscription->company_id,
                        'subscription_id' => $subscription->id,
                        'event' => 'billing.provider_subscription_reconciled',
                        'source' => 'stripe_api_read_only',
                        'context' => [
                            'price_code' => $targetMapping->price->code,
                            'provider_writes' => 'none',
                        ],
                        'created_at' => now(),
                    ]);
                }
            });

            $this->info('Stripe Sandbox subscription reconciliation completed idempotently.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Stripe Sandbox reconciliation failed safely: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function assertSnapshotMatches(Subscription $subscription, array $snapshot): void
    {
        if (($snapshot['livemode'] ?? true) !== false
            || ($snapshot['provider_subscription_id'] ?? null) !== $subscription->provider_subscription_id
            || ($snapshot['provider_customer_id'] ?? null) !== $subscription->provider_customer_id
            || ($snapshot['subscription_status'] ?? null) !== 'active'
            || ! array_key_exists('pending_update', $snapshot)
            || $snapshot['pending_update'] !== null) {
            throw new RuntimeException('Stripe Sandbox state is not an applied active subscription matching the tenant.');
        }
    }

    private function mapping(string $providerPriceId): PriceProviderMapping
    {
        $mapping = PriceProviderMapping::query()
            ->with('price.planVersion.plan')
            ->where('payment_provider', 'stripe')
            ->where('provider_price_id', $providerPriceId)
            ->where('status', 'active')
            ->first();
        if (! $mapping) {
            throw new RuntimeException('Stripe returned an unmapped provider Price.');
        }

        return $mapping;
    }
}
