<?php

namespace App\Exceptions;

use RuntimeException;

class WhatsAppOnboardingException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $safeMessage,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, $code, $previous);
    }
}
