<?php

namespace App\Notifications\Push;

final readonly class PushDeliveryResult
{
    public function __construct(
        public bool $delivered,
        public string $provider,
        public ?string $providerReference = null,
        public ?string $failureReason = null,
    ) {}
}
