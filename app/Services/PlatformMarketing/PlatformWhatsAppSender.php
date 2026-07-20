<?php

namespace App\Services\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PlatformWhatsAppSender
{
    public function sendText(PlatformMarketingChannel $channel, string $to, string $body): array
    {
        $this->assertReady($channel);

        $url = rtrim((string) config('services.whatsapp.meta.graph_base', 'https://graph.facebook.com'), '/')
            .'/'.config('services.whatsapp.meta.api_version', 'v20.0')
            .'/'.$channel->phone_number_id.'/messages';

        $response = Http::withToken($channel->decrypted_access_token)
            ->timeout(20)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => preg_replace('/\D+/', '', $to),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $body],
            ]);

        if ($response->failed()) {
            $channel->forceFill([
                'last_api_error' => 'Meta send failed with HTTP '.$response->status(),
            ])->save();

            Log::warning('[PlatformMarketing] WhatsApp send failed', [
                'channel_id' => $channel->id,
                'status' => $response->status(),
                'body_length' => strlen($body),
            ]);

            throw new RuntimeException('Platform WhatsApp send failed.');
        }

        $channel->forceFill(['last_outbound_at' => now(), 'last_api_error' => null])->save();

        return $response->json() ?: [];
    }

    private function assertReady(PlatformMarketingChannel $channel): void
    {
        if (! $channel->is_active || $channel->connection_status !== 'connected') {
            throw new RuntimeException('Platform WhatsApp channel is not connected.');
        }

        if (blank($channel->phone_number_id) || blank($channel->decrypted_access_token)) {
            throw new RuntimeException('Platform WhatsApp channel credentials are incomplete.');
        }
    }
}
