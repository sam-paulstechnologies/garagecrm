<?php

namespace App\Messaging\Services;

use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingConnection;

class TokenService
{
    public function store(MessagingConnection $connection, string $token): void
    {
        if (trim($token) === '') {
            throw new MessagingProvisioningException('missing_token', 'Meta did not return usable access. Restart the connection.');
        }

        // The model's encrypted cast performs encryption before persistence.
        $connection->encrypted_access_token = $token;
    }

    public function retrieve(MessagingConnection $connection): string
    {
        try {
            $token = (string) $connection->encrypted_access_token;
        } catch (\Throwable $exception) {
            throw new MessagingProvisioningException(
                'token_decryption_failed',
                'The saved WhatsApp authorization cannot be read. Reconnect WhatsApp.',
                previous: $exception,
            );
        }

        if (trim($token) === '') {
            throw new MessagingProvisioningException('missing_token', 'WhatsApp authorization is missing. Reconnect WhatsApp.');
        }

        return $token;
    }
}
