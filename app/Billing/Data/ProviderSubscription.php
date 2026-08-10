<?php

namespace App\Billing\Data;

use Carbon\CarbonImmutable;

final readonly class ProviderSubscription
{
    public function __construct(
        public string $id,
        public string $customerId,
        public string $priceId,
        public string $status,
        public ?CarbonImmutable $periodStart = null,
        public ?CarbonImmutable $periodEnd = null,
        public bool $cancelAtPeriodEnd = false,
    ) {}
}
