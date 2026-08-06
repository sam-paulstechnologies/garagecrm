<?php

namespace App\Messaging\Exceptions;

use RuntimeException;

class MessagingProvisioningException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $safeMessage,
        public readonly array $safeContext = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, 0, $previous);
    }
}
