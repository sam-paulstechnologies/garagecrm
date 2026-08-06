<?php

namespace App\SayaraForce\Messaging;

use App\Messaging\Data\NormalizedIncomingMessage;

class IncomingMessageHandler
{
    public function __construct(private readonly SayaraForceMessagingAdapter $adapter)
    {
    }

    public function handle(NormalizedIncomingMessage $message): void
    {
        $this->adapter->handleIncoming($message);
    }
}
