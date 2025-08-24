<?php

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\WhatsAppNotifierInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MetaCloudWhatsApp implements WhatsAppNotifierInterface
{
    protected string $phoneNumberId;
    protected string $accessToken;

    public function __construct()
    {
        $this->phoneNumberId = config('whatsapp.meta.phone_number_id');
        $this->accessToken   = config('whatsapp.meta.access_token');
    }

    public function sendText(string $toE164, string $message): bool
    {
        if (!$this->phoneNumberId || !$this->accessToken) {
            Log::warning('WA not configured; skipping sendText', ['to'=>$toE164]);
            return true; // non-blocking in dev
        }

        $url = "https://graph.facebook.com/v20.0/{$this->phoneNumberId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toE164,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $resp = Http::withToken($this->accessToken)->post($url, $payload);
        Log::info('WA sendText', ['to'=>$toE164, 'status'=>$resp->status(), 'body'=>$resp->json()]);
        return $resp->successful();
    }

    public function sendTemplate(string $toE164, string $template, array $variables = []): bool
    {
        if (!$this->phoneNumberId || !$this->accessToken) {
            Log::warning('WA not configured; skipping sendTemplate', ['to'=>$toE164, 'template'=>$template]);
            return true;
        }

        $components = [];
        if (!empty($variables)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($v) => ['type'=>'text','text'=>(string)$v], $variables),
            ];
        }

        $url = "https://graph.facebook.com/v20.0/{$this->phoneNumberId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toE164,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => 'en'],
                'components' => $components,
            ],
        ];

        $resp = Http::withToken($this->accessToken)->post($url, $payload);
        Log::info('WA sendTemplate', ['to'=>$toE164, 'status'=>$resp->status(), 'body'=>$resp->json()]);
        return $resp->successful();
    }
}
