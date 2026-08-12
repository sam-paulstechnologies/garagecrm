<?php

namespace App\Billing\Gateways;

use App\Billing\Contracts\BillingGateway;
use App\Billing\Data\CheckoutResult;
use App\Billing\Data\NormalizedBillingEvent;
use App\Billing\Data\ProviderCustomer;
use App\Billing\Data\ProviderSubscription;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\System\Company;

class DisabledBillingGateway implements BillingGateway
{
    public function provider(): string
    {
        return 'disabled';
    }

    public function createCustomer(Company $company, string $idempotencyKey): ProviderCustomer
    {
        return $this->unavailable();
    }

    public function createCheckout(ProviderCustomer $customer, PriceProviderMapping $mapping, string $successUrl, string $cancelUrl, string $idempotencyKey, array $metadata = []): CheckoutResult
    {
        return $this->unavailable();
    }

    public function createPlanChangeCheckout(ProviderCustomer $customer, string $providerSubscriptionId, PriceProviderMapping $mapping, string $successUrl, string $cancelUrl, string $idempotencyKey, array $metadata = []): CheckoutResult
    {
        return $this->unavailable();
    }

    public function createSubscription(ProviderCustomer $customer, PriceProviderMapping $mapping, string $idempotencyKey): ProviderSubscription
    {
        return $this->unavailable();
    }

    public function cancelSubscription(string $providerSubscriptionId, bool $atPeriodEnd = true): ProviderSubscription
    {
        return $this->unavailable();
    }

    public function changeSubscription(string $providerSubscriptionId, PriceProviderMapping $mapping, string $idempotencyKey, array $metadata = []): ProviderSubscription
    {
        return $this->unavailable();
    }

    public function retrieveSubscription(string $providerSubscriptionId): ProviderSubscription
    {
        return $this->unavailable();
    }

    public function createBillingPortal(string $providerCustomerId, string $returnUrl): string
    {
        return $this->unavailable();
    }

    public function verifyWebhook(string $payload, string $signature): void
    {
        $this->unavailable();
    }

    public function normalizeEvent(string $payload): NormalizedBillingEvent
    {
        return $this->unavailable();
    }

    private function unavailable(): never
    {
        throw new BillingConfigurationException('Self-service billing is not configured for this environment.');
    }
}
