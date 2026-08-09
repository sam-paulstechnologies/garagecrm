<?php

namespace App\Commercial;

final readonly class EntitlementDecision
{
    public function __construct(
        public bool $allowed,
        public string $capability,
        public string $source,
        public string $reason,
        public string $subscriptionState,
        public ?int $limit = null,
        public ?int $remaining = null,
        public ?string $mode = null,
    ) {}

    /** @return array<string, bool|int|string|null> */
    public function auditContext(): array
    {
        return [
            'allowed' => $this->allowed,
            'capability' => $this->capability,
            'source' => $this->source,
            'reason' => $this->reason,
            'subscription_state' => $this->subscriptionState,
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'mode' => $this->mode,
        ];
    }
}
