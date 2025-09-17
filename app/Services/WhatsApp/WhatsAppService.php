<?php

namespace App\Services\WhatsApp;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\Shared\WhatsappMessage;

class WhatsAppService
{
    protected string $provider;
    protected Client $http;

    public function __construct()
    {
        $this->provider = config('services.whatsapp.provider', 'meta');
        $this->http = new Client(['timeout' => 10]);
    }

    public function sendTemplate(string $toE164, string $templateName, array $params = [], array $links = [], array $context = []): array
    {
        return match ($this->provider) {
            'meta'   => $this->sendMetaTemplate($toE164, $templateName, $params, $links, $context),
            'twilio' => $this->sendTwilioTemplate($toE164, $templateName, $params, $links, $context),
            'gupshup'=> $this->sendGupshupTemplate($toE164, $templateName, $params, $links, $context),
            default  => ['error' => 'Unknown provider'],
        };
    }

    protected function sendMetaTemplate(string $to, string $template, array $params, array $links, array $context): array
    {
        $phoneId = config('services.whatsapp.meta.phone_id');
        $token   = config('services.whatsapp.meta.token');

        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($p) => ['type'=>'text','text'=> (string)$p], $params)
            ];
        }
        if (!empty($links)) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [['type'=>'text','text'=> $links[0] ?? '']]
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template,          // Must be pre-approved in Meta
                'language' => ['code' => 'en'],
                'components' => $components
            ]
        ];

        $url = "https://graph.facebook.com/v20.0/{$phoneId}/messages";

        try {
            $res = $this->http->post($url, [
                'headers' => ['Authorization' => "Bearer {$token}", 'Content-Type' => 'application/json'],
                'json' => $payload
            ]);
            $body = json_decode((string)$res->getBody(), true);

            WhatsappMessage::create([
                'provider' => 'meta',
                'direction' => 'outbound',
                'to_number' => $to,
                'template' => $template,
                'payload' => json_encode($payload),
                'status' => 'sent',
                'lead_id' => $context['lead_id'] ?? null,
                'opportunity_id' => $context['opportunity_id'] ?? null,
                'job_id' => $context['job_id'] ?? null,
            ]);

            return $body ?? ['ok' => true];
        } catch (\Throwable $e) {
            Log::error('WA send error: '.$e->getMessage(), ['payload'=>$payload]);
            WhatsappMessage::create([
                'provider' => 'meta',
                'direction' => 'outbound',
                'to_number' => $to,
                'template' => $template,
                'payload' => json_encode($payload),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'lead_id' => $context['lead_id'] ?? null,
                'opportunity_id' => $context['opportunity_id'] ?? null,
                'job_id' => $context['job_id'] ?? null,
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    protected function sendTwilioTemplate(string $to, string $template, array $params, array $links, array $context): array
    {
        // Twilio uses pre-registered templates too; for brevity, send body as simple text demo
        return ['todo'=>'Implement Twilio if you switch provider'];
    }

    protected function sendGupshupTemplate(string $to, string $template, array $params, array $links, array $context): array
    {
        return ['todo'=>'Implement Gupshup if you switch provider'];
    }
}
