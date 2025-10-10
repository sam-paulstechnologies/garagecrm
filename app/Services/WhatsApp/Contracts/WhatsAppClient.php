<?php

namespace App\Services\WhatsApp\Contracts;

interface WhatsAppClient
{
    /**
     * Send a plain WhatsApp text message.
     * @return array Provider response (decoded JSON/array)
     */
    public function send(string $to, string $body, array $options = []): array;
}
