<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppClient;
use GuzzleHttp\Client;

class TwilioWhatsAppClient implements WhatsAppClient
{
    public function __construct(
        private ?string $sid = null,
        private ?string $token = null,
        private ?string $from = null,
        private ?string $baseUri = null,
    ) {
        $cfg = config('services.whatsapp.twilio');
        $this->sid     ??= $cfg['sid'];
        $this->token   ??= $cfg['token'];
        $this->from    ??= $cfg['from'];
        $this->baseUri ??= $cfg['base_uri'];
    }

    public function send(string $to, string $body, array $options = []): array
    {
        $client = new Client(['base_uri' => $this->baseUri]);
        $res = $client->post("/2010-04-01/Accounts/{$this->sid}/Messages.json", [
            'auth' => [$this->sid, $this->token],
            'form_params' => [
                'From' => $this->from,
                'To'   => $to,
                'Body' => $body,
            ],
        ]);
        return json_decode((string) $res->getBody(), true) ?? [];
    }
}
