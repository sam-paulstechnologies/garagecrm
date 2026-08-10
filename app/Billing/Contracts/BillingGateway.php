<?php

namespace App\Billing\Contracts;

use App\Billing\Data\CheckoutResult;
use App\Billing\Data\NormalizedBillingEvent;
use App\Billing\Data\ProviderCustomer;
use App\Billing\Data\ProviderSubscription;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\System\Company;

interface BillingGateway
{
    public function provider(): string;

    public function createCustomer(Company $company, string $idempotencyKey): ProviderCustomer;

    /** @param array<string, scalar|null> $metadata */
    public function createCheckout(
        ProviderCustomer $customer,
        PriceProviderMapping $mapping,
        string $successUrl,
        string $cancelUrl,
        string $idempotencyKey,
        array $metadata = [],
    ): CheckoutResult;

    public function createSubscription(
        ProviderCustomer $customer,
        PriceProviderMapping $mapping,
        string $idempotencyKey,
    ): ProviderSubscription;

    public function cancelSubscription(string $providerSubscriptionId, bool $atPeriodEnd = true): ProviderSubscription;

    public function changeSubscription(
        string $providerSubscriptionId,
        PriceProviderMapping $mapping,
        string $idempotencyKey,
        array $metadata = [],
    ): ProviderSubscription;

    public function retrieveSubscription(string $providerSubscriptionId): ProviderSubscription;

    public function createBillingPortal(string $providerCustomerId, string $returnUrl): string;

    public function verifyWebhook(string $payload, string $signature): void;

    public function normalizeEvent(string $payload): NormalizedBillingEvent;
}
