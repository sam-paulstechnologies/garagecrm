<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppClient;
use GuzzleHttp\Client;
use RuntimeException;

class TwilioWhatsAppClient implements WhatsAppClient
{
    public function __construct(
        private ?string $sid = null,
        private ?string $token = null,
        private ?string $from = null,
        private ?string $baseUri = null,
    ) {
        $cfg = config('services.whatsapp.twilio', []);

        $this->sid     ??= $cfg['sid'] ?? null;
        $this->token   ??= $cfg['token'] ?? null;
        $this->from    ??= $cfg['from'] ?? null;
        $this->baseUri ??= $cfg['base_uri'] ?? 'https://api.twilio.com';
    }

    public function send(string $to, string $body, array $options = []): array
    {
        $sid = $options['sid'] ?? $this->sid;
        $token = $options['token'] ?? $this->token;
        $from = $options['from'] ?? $this->from;
        $baseUri = $options['base_uri'] ?? $this->baseUri;

        if (! $sid || ! $token || ! $from) {
            throw new RuntimeException('Twilio WhatsApp credentials are missing.');
        }

        $client = new Client([
            'base_uri' => $baseUri,
            'timeout' => 20,
        ]);

        $response = $client->post("/2010-04-01/Accounts/{$sid}/Messages.json", [
            'auth' => [$sid, $token],
            'form_params' => [
                'From' => $this->formatWhatsAppNumber($from),
                'To'   => $this->formatWhatsAppNumber($to),
                'Body' => $body,
            ],
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    protected function formatWhatsAppNumber(string $number): string
    {
        $number = trim($number);

        if (str_starts_with($number, 'whatsapp:')) {
            return $number;
        }

        $number = preg_replace('/\D+/', '', $number);

        if (str_starts_with($number, '05')) {
            $number = '971' . substr($number, 1);
        }

        if (str_starts_with($number, '9710')) {
            $number = '971' . substr($number, 3);
        }

        return 'whatsapp:+' . $number;
    }
}