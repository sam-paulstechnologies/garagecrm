<?php

namespace App\Messaging\Data;

use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Models\System\Company;

final readonly class ResolvedMessagingContext
{
    public function __construct(
        public Company $company,
        public ?MessagingConnection $connection,
        public ?MessagingPhoneNumber $phoneNumber,
        public string $productKey,
        public bool $legacy = false,
    ) {
    }
}
