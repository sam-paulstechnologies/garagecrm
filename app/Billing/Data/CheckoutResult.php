<?php

namespace App\Billing\Data;

use Carbon\CarbonImmutable;

final readonly class CheckoutResult
{
    public function __construct(
        public string $id,
        public string $url,
        public ?CarbonImmutable $expiresAt = null,
    ) {}
}
