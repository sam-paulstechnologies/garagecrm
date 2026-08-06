<?php

namespace App\Messaging\Data;

final readonly class NormalizedIncomingMessage
{
    public function __construct(
        public int $companyId,
        public int $connectionId,
        public string $productKey,
        public string $provider,
        public string $providerMessageId,
        public string $from,
        public string $to,
        public string $type,
        public string $body,
        public ?string $profileName = null,
        public ?string $mediaId = null,
        public ?string $mediaMimeType = null,
        public ?int $providerTimestamp = null,
        public string $sourceKind = 'customer_inbound',
    ) {
    }
}
