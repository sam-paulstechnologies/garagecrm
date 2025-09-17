<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Documents\Ingestion\UploadIngestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EmailInboundWebhookController extends Controller
{
    public function __construct(protected UploadIngestService $ingest) {}

    public function __invoke(Request $request) { return $this->handle($request); }

    public function handle(Request $request)
    {
        // Expecting JSON: { message_id, from, attachments: [{url, filename, content_type, size, base64?}, ...] }
        $payload     = $request->all();
        $from        = (string) ($payload['from'] ?? '');
        $messageId   = (string) ($payload['message_id'] ?? '');
        $attachments = (array)  ($payload['attachments'] ?? []);

        if (empty($attachments)) {
            Log::info('email.inbound.no_attachments', ['message_id' => $messageId, 'from' => $from]);
            return response('OK', Response::HTTP_OK);
        }

        $client = Http::timeout((int) config('document_ingest.http_timeout_seconds', 30))
            ->withHeaders(['User-Agent' => config('document_ingest.http_user_agent', 'GarageCRM/1.0')]);

        foreach ($attachments as $i => $att) {
            $binary = null;

            try {
                if (!empty($att['base64'])) {
                    $binary = base64_decode((string) $att['base64'], true) ?: null;
                } elseif (!empty($att['url'])) {
                    $resp = $client->get((string) $att['url']);
                    if ($resp->successful()) {
                        $binary = $resp->body();
                    } else {
                        Log::warning('email.inbound.fetch_failed', [
                            'message_id' => $messageId,
                            'from'       => $from,
                            'url'        => $att['url'],
                            'code'       => $resp->status(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('email.inbound.fetch_exception', [
                    'message_id' => $messageId,
                    'from'       => $from,
                    'url'        => $att['url'] ?? null,
                    'err'        => $e->getMessage(),
                ]);
            }

            if (!$binary) {
                continue;
            }

            $filename = (string) ($att['filename'] ?? ("email-{$messageId}-{$i}"));
            $mime     = (string) ($att['content_type'] ?? '');
            $size     = isset($att['size']) ? (int) $att['size'] : strlen($binary);

            $this->ingest->ingestRawBinary($binary, [
                'type'                => 'other',
                'source'              => 'email',
                'sender_email'        => $from ?: null,
                'provider_message_id' => $messageId ?: null,
                'original_name'       => $filename,
                'mime'                => $mime ?: null,
                'size'                => $size,
            ]);
        }

        return response('OK', Response::HTTP_OK);
    }
}
