<?php

namespace App\Billing;

use App\Billing\Data\NormalizedBillingEvent;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Billing\Exceptions\InvalidBillingWebhook;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\BillingInvoice;
use App\Models\Commercial\BillingProviderEvent;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Jobs\TransitionIntroductoryBillingPrice;

class BillingWebhookProcessor
{
    public function __construct(
        private readonly BillingGatewayResolver $gateways,
        private readonly BillingManager $billing,
    ) {}

    /** @return array{duplicate: bool, status: string} */
    public function process(string $provider, string $payload, string $signature): array
    {
        $gateway = $this->gateways->for($provider);
        $gateway->verifyWebhook($payload, $signature);
        $event = $gateway->normalizeEvent($payload);
        $hash = hash('sha256', $payload);

        try {
            $record = BillingProviderEvent::query()->create([
                'payment_provider' => $provider,
                'provider_event_id' => $event->id,
                'event_type' => $event->type,
                'event_created_at' => $event->occurredAt,
                'object_type' => $event->objectType,
                'object_id' => $event->objectId,
                'payload_hash' => $hash,
                'normalized_payload' => $event->data,
                'status' => 'received',
            ]);
        } catch (QueryException $exception) {
            $record = BillingProviderEvent::query()
                ->where('payment_provider', $provider)
                ->where('provider_event_id', $event->id)
                ->first();
            if (! $record) {
                throw $exception;
            }
            if (! hash_equals((string) $record->payload_hash, $hash)) {
                throw new InvalidBillingWebhook('A billing event ID was replayed with a different payload.');
            }
            if (in_array($record->status, ['processed', 'ignored_out_of_order', 'ignored_unsupported'], true)) {
                return ['duplicate' => true, 'status' => $record->status];
            }
            $record->increment('attempt_count');
        }

        try {
            $status = DB::transaction(fn (): string => $this->apply($provider, $event));
            $record->update(['status' => $status, 'processed_at' => now(), 'failure_reason' => null]);

            return ['duplicate' => false, 'status' => $status];
        } catch (\Throwable $exception) {
            $record->update([
                'status' => 'failed',
                'failure_reason' => substr(class_basename($exception), 0, 120),
            ]);
            throw $exception;
        }
    }

    private function apply(string $provider, NormalizedBillingEvent $event): string
    {
        $checkout = $this->checkout($provider, $event);
        $subscription = $this->subscription($provider, $event, $checkout);
        if ($subscription?->provider_state_updated_at?->greaterThan($event->occurredAt)) {
            return 'ignored_out_of_order';
        }

        if (in_array($event->type, ['checkout.session.completed', 'customer.subscription.created'], true)) {
            if (! $checkout) {
                throw new BillingConfigurationException('Verified billing activation has no matching local checkout.');
            }
            $this->activate($checkout, $event);

            return 'processed';
        }

        if ($event->type === 'customer.subscription.updated') {
            if ($checkout && (! $subscription || (int) $checkout->requested_price_id !== (int) $subscription->price_id)) {
                $subscription = $this->activate($checkout, $event);
            }
            if (! $subscription) {
                throw new BillingConfigurationException('Provider subscription does not map to a tenant subscription.');
            }
            $this->updateSubscriptionState($subscription, $event);

            return 'processed';
        }

        if ($event->type === 'customer.subscription.deleted') {
            if (! $subscription) {
                throw new BillingConfigurationException('Deleted provider subscription does not map to a tenant subscription.');
            }
            $completedCycles = min(65535, ((int) $subscription->introductory_cycles_completed) + 1);
            $subscription->update([
                'status' => 'cancelled', 'payment_status' => 'cancelled',
                'cancelled_at' => now(), 'cancel_at_period_end' => false,
                'provider_state_updated_at' => $event->occurredAt,
            ]);
            $this->audit($subscription, 'billing.subscription_cancelled', $provider);

            return 'processed';
        }

        if (in_array($event->type, ['invoice.paid', 'invoice.payment_succeeded'], true)) {
            if (! $subscription) {
                throw new BillingConfigurationException('Paid invoice does not map to a tenant subscription.');
            }
            $this->upsertInvoice($subscription, $event, 'paid');
            $subscription->update([
                'status' => $subscription->cancel_at_period_end ? 'cancel_at_period_end' : 'active',
                'payment_status' => 'paid',
                'current_period_start' => $this->date($event->data['period_start'] ?? null) ?? $subscription->current_period_start,
                'current_period_end' => $this->date($event->data['period_end'] ?? null) ?? $subscription->current_period_end,
                'grace_ends_at' => null,
                'suspended_at' => null,
                'provider_state_updated_at' => $event->occurredAt,
                'introductory_cycles_completed' => $completedCycles,
            ]);
            $this->audit($subscription, 'billing.invoice_paid', $provider, ['introductory_cycles_completed' => $subscription->introductory_cycles_completed]);
            $duration = (int) ($subscription->price?->promotion_duration_months ?? 0);
            if ($duration > 0 && $completedCycles >= $duration && ! $subscription->standard_price_transition_requested_at) {
                DB::afterCommit(fn () => TransitionIntroductoryBillingPrice::dispatch($subscription->id));
            }

            return 'processed';
        }

        if ($event->type === 'invoice.payment_failed') {
            if (! $subscription) {
                throw new BillingConfigurationException('Failed invoice does not map to a tenant subscription.');
            }
            $this->upsertInvoice($subscription, $event, 'payment_failed');
            $subscription->update([
                'status' => 'grace',
                'payment_status' => 'past_due',
                'grace_ends_at' => now()->addDays((int) config('billing.failed_payment.grace_days', 7)),
                'provider_state_updated_at' => $event->occurredAt,
            ]);
            $this->audit($subscription, 'billing.payment_failed_grace_started', $provider, ['grace_days' => (int) config('billing.failed_payment.grace_days', 7)]);

            return 'processed';
        }

        return 'ignored_unsupported';
    }

    private function checkout(string $provider, NormalizedBillingEvent $event): ?BillingCheckoutSession
    {
        $localId = (int) ($event->data['local_checkout_id'] ?? 0);
        $query = BillingCheckoutSession::query()->where('payment_provider', $provider);

        return $localId > 0
            ? $query->whereKey($localId)->first()
            : $query->where('provider_checkout_id', $event->objectId)->first();
    }

    private function subscription(string $provider, NormalizedBillingEvent $event, ?BillingCheckoutSession $checkout): ?Subscription
    {
        $providerId = (string) ($event->data['provider_subscription_id'] ?? '');
        if ($providerId !== '') {
            $subscription = Subscription::query()
                ->where('payment_provider', $provider)
                ->where('provider_subscription_id', $providerId)
                ->lockForUpdate()
                ->first();
            if ($subscription) {
                return $subscription;
            }
        }

        return $checkout ? Subscription::query()->lockForUpdate()->find($checkout->subscription_id) : null;
    }

    private function activate(BillingCheckoutSession $checkout, NormalizedBillingEvent $event): Subscription
    {
        foreach (['provider_customer_id', 'provider_subscription_id', 'provider_price_id'] as $required) {
            if (blank($event->data[$required] ?? null)) {
                throw new BillingConfigurationException("Verified activation is missing {$required}.");
            }
        }

        return $this->billing->activateFromVerifiedEvent(
            $checkout,
            (string) $event->data['provider_customer_id'],
            (string) $event->data['provider_subscription_id'],
            (string) $event->data['provider_price_id'],
            (string) ($event->data['payment_status'] ?? 'paid'),
            $event->occurredAt,
            $this->date($event->data['period_start'] ?? null),
            $this->date($event->data['period_end'] ?? null),
        );
    }

    private function updateSubscriptionState(Subscription $subscription, NormalizedBillingEvent $event): void
    {
        $providerStatus = (string) ($event->data['subscription_status'] ?? '');
        $cancelAtEnd = filter_var($event->data['cancel_at_period_end'] ?? false, FILTER_VALIDATE_BOOL);
        $status = match ($providerStatus) {
            'active' => $cancelAtEnd ? 'cancel_at_period_end' : 'active',
            'trialing' => 'trialing',
            'past_due' => 'grace',
            'unpaid', 'paused' => 'suspended',
            'canceled' => 'cancelled',
            'incomplete', 'incomplete_expired' => 'pending',
            default => throw new BillingConfigurationException('Unsupported provider subscription state.'),
        };
        $providerPriceId = (string) ($event->data['provider_price_id'] ?? '');
        if ($providerPriceId !== '') {
            $mappingMatches = \App\Models\Commercial\PriceProviderMapping::query()
                ->where('price_id', $subscription->price_id)
                ->where('payment_provider', $subscription->payment_provider)
                ->where('provider_price_id', $providerPriceId)
                ->exists();
            if (! $mappingMatches) {
                throw new BillingConfigurationException('Provider subscription update references an unapproved price mapping.');
            }
        }
        $subscription->update([
            'status' => $status,
            'payment_status' => $providerStatus,
            'cancel_at_period_end' => $cancelAtEnd,
            'current_period_start' => $this->date($event->data['period_start'] ?? null) ?? $subscription->current_period_start,
            'current_period_end' => $this->date($event->data['period_end'] ?? null) ?? $subscription->current_period_end,
            'grace_ends_at' => $status === 'grace'
                ? ($subscription->grace_ends_at ?? now()->addDays((int) config('billing.failed_payment.grace_days', 7)))
                : null,
            'suspended_at' => $status === 'suspended' ? now() : null,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
            'provider_state_updated_at' => $event->occurredAt,
            'provider_price_id' => $providerPriceId !== '' ? $providerPriceId : $subscription->provider_price_id,
        ]);
        $this->audit($subscription, 'billing.subscription_state_changed', $subscription->payment_provider, ['status' => $status]);
    }

    private function upsertInvoice(Subscription $subscription, NormalizedBillingEvent $event, string $status): void
    {
        $invoiceId = (string) ($event->data['provider_invoice_id'] ?? $event->objectId);
        $url = (string) ($event->data['hosted_invoice_url'] ?? '');
        BillingInvoice::query()->updateOrCreate(
            ['payment_provider' => $subscription->payment_provider, 'provider_invoice_id' => $invoiceId],
            [
                'company_id' => $subscription->company_id,
                'subscription_id' => $subscription->id,
                'status' => $status,
                'currency' => strtoupper((string) ($event->data['currency'] ?? 'AED')),
                'amount_due' => $this->major($event->data['amount_due_minor'] ?? 0),
                'amount_paid' => $this->major($event->data['amount_paid_minor'] ?? 0),
                'period_start' => $this->date($event->data['period_start'] ?? null),
                'period_end' => $this->date($event->data['period_end'] ?? null),
                'due_at' => $this->date($event->data['due_at'] ?? null),
                'paid_at' => $this->date($event->data['paid_at'] ?? null),
                'hosted_invoice_url' => str_starts_with($url, 'https://') ? $url : null,
            ],
        );
    }

    /** @param array<string, mixed> $context */
    private function audit(Subscription $subscription, string $event, ?string $source, array $context = []): void
    {
        EntitlementAuditLog::query()->create([
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'event' => $event,
            'source' => $source,
            'context' => $context,
            'created_at' => now(),
        ]);
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return is_numeric($value) && (int) $value > 0 ? CarbonImmutable::createFromTimestampUTC((int) $value) : null;
    }

    private function major(mixed $minor): string
    {
        return number_format(((int) $minor) / 100, 2, '.', '');
    }
}
