<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppClient;
use InvalidArgumentException;
use RuntimeException;

class ProviderFactory
{
    public static function make(?string $provider = null, ?int $companyId = null): WhatsAppClient
    {
        $provider = strtolower(trim((string) (
            $provider ?: config('services.whatsapp.provider', 'twilio')
        )));

        $provider = match ($provider) {
            'twilio', 'twilio_whatsapp' => 'twilio',
            'meta', 'meta_cloud', 'facebook', 'whatsapp_cloud' => 'meta',
            default => $provider,
        };

        return match ($provider) {
            'twilio' => self::resolveClient(TwilioWhatsAppClient::class),

            'meta' => self::resolveMetaClient($companyId),

            default => throw new InvalidArgumentException(
                "Unsupported WhatsApp provider: {$provider}"
            ),
        };
    }

    protected static function resolveClient(string $class): WhatsAppClient
    {
        if (! class_exists($class)) {
            throw new RuntimeException("WhatsApp client class not found: {$class}");
        }

        $client = app($class);

        if (! $client instanceof WhatsAppClient) {
            throw new RuntimeException("{$class} must implement WhatsAppClient.");
        }

        return $client;
    }

    protected static function resolveMetaClient(?int $companyId = null): WhatsAppClient
    {
        $class = __NAMESPACE__ . '\\MetaWhatsAppClient';

        if (! class_exists($class)) {
            throw new RuntimeException(
                'Meta WhatsApp provider is selected, but MetaWhatsAppClient is not available. ' .
                'Either create app/Services/WhatsApp/MetaWhatsAppClient.php or use WhatsAppService for Meta Cloud sending.'
            );
        }

        $client = $companyId
            ? app()->makeWith($class, ['companyId' => $companyId])
            : app($class);

        if (! $client instanceof WhatsAppClient) {
            throw new RuntimeException("{$class} must implement WhatsAppClient.");
        }

        return $client;
    }
}