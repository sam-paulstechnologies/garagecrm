<?php

namespace App\Billing\Gateways;

use App\Billing\Contracts\BillingGateway;
use App\Billing\Data\CheckoutResult;
use App\Billing\Data\NormalizedBillingEvent;
use App\Billing\Data\ProviderCustomer;
use App\Billing\Data\ProviderSubscription;
use App\Billing\Exceptions\InvalidBillingWebhook;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\System\Company;
use Carbon\CarbonImmutable;

class FakeBillingGateway implements BillingGateway
{
    public function provider(): string { return 'fake'; }

    public function createCustomer(Company $company, string $idempotencyKey): ProviderCustomer
    {
        return new ProviderCustomer('cus_fake_'.substr(hash('sha256', (string) $company->id), 0, 16));
    }

    public function createCheckout(ProviderCustomer $customer, PriceProviderMapping $mapping, string $successUrl, string $cancelUrl, string $idempotencyKey, array $metadata = []): CheckoutResult
    {
        $localId = (int) ($metadata['local_checkout_id'] ?? 0);

        return new CheckoutResult(
            'cs_fake_'.$localId.'_'.substr($idempotencyKey, 0, 12),
            route('admin.billing.fake.show', ['billingCheckoutSession' => $localId]),
            CarbonImmutable::now()->addMinutes(30),
        );
    }

    public function createSubscription(ProviderCustomer $customer, PriceProviderMapping $mapping, string $idempotencyKey): ProviderSubscription
    {
        return $this->subscription('sub_fake_'.substr($idempotencyKey, 0, 16), $customer->id, $mapping->provider_price_id);
    }

    public function cancelSubscription(string $providerSubscriptionId, bool $atPeriodEnd = true): ProviderSubscription
    {
        return new ProviderSubscription($providerSubscriptionId, 'cus_fake', 'price_fake', $atPeriodEnd ? 'active' : 'cancelled', cancelAtPeriodEnd: $atPeriodEnd);
    }

    public function changeSubscription(string $providerSubscriptionId, PriceProviderMapping $mapping, string $idempotencyKey, array $metadata = []): ProviderSubscription
    {
        return $this->subscription($providerSubscriptionId, 'cus_fake', $mapping->provider_price_id);
    }

    public function retrieveSubscription(string $providerSubscriptionId): ProviderSubscription
    {
        return $this->subscription($providerSubscriptionId, 'cus_fake', 'price_fake');
    }

    public function createBillingPortal(string $providerCustomerId, string $returnUrl): string
    {
        return $returnUrl;
    }

    public function verifyWebhook(string $payload, string $signature): void
    {
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret());
        if (! hash_equals($expected, $signature)) {
            throw new InvalidBillingWebhook('The fake billing webhook signature is invalid.');
        }
    }

    public function normalizeEvent(string $payload): NormalizedBillingEvent
    {
        $event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        foreach (['id', 'type', 'created', 'object_type', 'object_id', 'data'] as $required) {
            if (! array_key_exists($required, $event)) {
                throw new InvalidBillingWebhook('The fake billing event is malformed.');
            }
        }

        return new NormalizedBillingEvent(
            (string) $event['id'],
            (string) $event['type'],
            CarbonImmutable::createFromTimestampUTC((int) $event['created']),
            (string) $event['object_type'],
            (string) $event['object_id'],
            (array) $event['data'],
        );
    }

    /** @param array<string, scalar|null> $data */
    public function signedEvent(string $type, string $objectType, string $objectId, array $data, ?string $eventId = null, ?int $created = null): array
    {
        $payload = json_encode([
            'id' => $eventId ?? 'evt_fake_'.bin2hex(random_bytes(12)),
            'type' => $type,
            'created' => $created ?? now()->timestamp,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'data' => $data,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return [$payload, hash_hmac('sha256', $payload, $this->webhookSecret())];
    }

    private function subscription(string $id, string $customerId, string $priceId): ProviderSubscription
    {
        $start = CarbonImmutable::now();

        return new ProviderSubscription($id, $customerId, $priceId, 'active', $start, $start->addMonth());
    }

    private function webhookSecret(): string
    {
        $secret = (string) config('billing.fake.webhook_secret');
        if ($secret === '') {
            throw new InvalidBillingWebhook('The fake billing webhook secret is not configured.');
        }

        return $secret;
    }
}
