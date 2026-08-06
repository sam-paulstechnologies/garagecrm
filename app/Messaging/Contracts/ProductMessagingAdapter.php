<?php

namespace App\Messaging\Contracts;

use App\Messaging\Data\NormalizedIncomingMessage;

interface ProductMessagingAdapter
{
    public function productKey(): string;

    public function handleIncoming(NormalizedIncomingMessage $message): void;
}
