<?php

namespace App\Billing;

use App\Billing\Contracts\BillingGateway;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Billing\Gateways\DisabledBillingGateway;
use App\Billing\Gateways\FakeBillingGateway;
use App\Billing\Gateways\StripeBillingGateway;

class BillingGatewayResolver
{
    public function configured(): BillingGateway
    {
        return $this->for((string) config('billing.provider', 'disabled'));
    }

    public function for(string $provider): BillingGateway
    {
        return match ($provider) {
            'stripe' => app(StripeBillingGateway::class),
            'fake' => app(FakeBillingGateway::class),
            'disabled' => app(DisabledBillingGateway::class),
            default => throw new BillingConfigurationException('Unsupported billing provider.'),
        };
    }
}
