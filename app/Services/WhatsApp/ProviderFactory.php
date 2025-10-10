<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppClient;
use InvalidArgumentException;

class ProviderFactory
{
    public static function make(?string $provider = null): WhatsAppClient
    {
        $provider = $provider ?: config('services.whatsapp.provider', 'twilio');

        return match ($provider) {
            'twilio' => app(TwilioWhatsAppClient::class),
            // 'meta'   => app(MetaWhatsAppClient::class),
            // 'gupshup'=> app(GupshupWhatsAppClient::class),
            default  => throw new InvalidArgumentException("Unsupported WhatsApp provider: {$provider}")
        };
    }
}
