<?php

namespace App\Billing\Data;

use Carbon\CarbonImmutable;

final readonly class NormalizedBillingEvent
{
    /**
     * @param array<string, scalar|null> $data
     */
    public function __construct(
        public string $id,
        public string $type,
        public CarbonImmutable $occurredAt,
        public string $objectType,
        public string $objectId,
        public array $data,
    ) {}
}
