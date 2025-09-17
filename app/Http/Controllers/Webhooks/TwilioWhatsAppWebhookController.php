<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Documents\Ingestion\UploadIngestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TwilioWhatsAppWebhookController extends Controller
{
    public function __construct(protected UploadIngestService $ingest) {}

    public function __invoke(Request $request) { return $this->handle($request); }

    public function handle(Request $request)
    {
        $from = (string) $request->input('From'); // e.g., whatsapp:+9715xxxx
        $providerMessageId = (string) ($request->input('SmsMessageSid') ?? $request->input('MessageSid'));
        $numMedia = (int) $request->input('NumMedia', 0);

        if ($numMedia <= 0) {
            Log::info('twilio.wa.no_media', ['sid' => $providerMessageId, 'from' => $from]);
            return response('OK', Response::HTTP_OK);
        }

        // HTTP client with timeout + UA
        $client = Http::timeout((int) config('document_ingest.http_timeout_seconds', 30))
            ->withHeaders(['User-Agent' => config('document_ingest.http_user_agent', 'GarageCRM/1.0')]);

        // Optional: Twilio media often requires Basic Auth
        if (config('document_ingest.twilio_require_auth', false)) {
            $sid   = (string) env('TWILIO_SID');
            $token = (string) env('TWILIO_TOKEN');
            if ($sid && $token) {
                $client = $client->withBasicAuth($sid, $token);
            }
        }

        for ($i = 0; $i < $numMedia; $i++) {
            $mediaUrl = (string) $request->input("MediaUrl{$i}");
            $mime     = (string) $request->input("MediaContentType{$i}");
            if (!$mediaUrl) continue;

            try {
                $resp = $client->get($mediaUrl);
                if (!$resp->successful()) {
                    Log::warning('twilio.wa.fetch_failed', [
                        'sid'   => $providerMessageId,
                        'from'  => $from,
                        'url'   => $mediaUrl,
                        'code'  => $resp->status(),
                    ]);
                    continue;
                }

                $binary = $resp->body();

                $this->ingest->ingestRawBinary($binary, [
                    'type'                => 'other',
                    'source'              => 'whatsapp',
                    'sender_phone'        => $from ? Str::replace('whatsapp:', '', $from) : null,
                    'provider_message_id' => $providerMessageId,
                    'original_name'       => "twilio-{$providerMessageId}-{$i}",
                    'mime'                => $mime ?: ($resp->header('Content-Type') ?? null),
                    'size'                => strlen($binary),
                ]);

            } catch (\Throwable $e) {
                Log::warning('twilio.wa.ingest_exception', [
                    'sid'  => $providerMessageId,
                    'from' => $from,
                    'url'  => $mediaUrl,
                    'err'  => $e->getMessage(),
                ]);
            }
        }

        return response('OK', Response::HTTP_OK);
    }
}
