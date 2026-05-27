<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Documents\Ingestion\UploadIngestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EmailInboundWebhookController extends Controller
{
    public function __construct(protected UploadIngestService $ingest) {}

    public function __invoke(Request $request)
    {
        return $this->handle($request);
    }

    public function handle(Request $request)
    {
        if (! $this->isVerified($request)) {
            Log::warning('email.inbound.rejected', [
                'reason' => 'invalid_or_missing_secret',
                'ip' => $request->ip(),
            ]);

            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        // Expected JSON:
        // {
        //   message_id,
        //   from,
        //   attachments: [
        //     {url, filename, content_type, size, base64?}
        //   ]
        // }
        $payload = $request->all();

        $from = (string) ($payload['from'] ?? '');
        $messageId = (string) ($payload['message_id'] ?? '');
        $attachments = (array) ($payload['attachments'] ?? []);

        if (empty($attachments)) {
            Log::info('email.inbound.no_attachments', [
                'message_id' => $this->safeLogValue($messageId),
                'from_hash' => $this->hashValue($from),
                'from_domain' => $this->emailDomain($from),
            ]);

            return response('OK', Response::HTTP_OK);
        }

        $companyId = $this->resolveCompanyId($from);

        if (! $companyId) {
            Log::warning('email.inbound.company_not_resolved', [
                'message_id' => $this->safeLogValue($messageId),
                'from_hash' => $this->hashValue($from),
                'from_domain' => $this->emailDomain($from),
            ]);

            return response('OK', Response::HTTP_OK);
        }

        $client = Http::timeout((int) config('document_ingest.http_timeout_seconds', 30))
            ->withHeaders([
                'User-Agent' => config('document_ingest.http_user_agent', 'GarageCRM/1.0'),
            ]);

        foreach ($attachments as $i => $att) {
            if (! is_array($att)) {
                continue;
            }

            $binary = null;

            try {
                if (! empty($att['base64'])) {
                    $binary = base64_decode((string) $att['base64'], true) ?: null;
                } elseif (! empty($att['url'])) {
                    $url = (string) $att['url'];

                    if (! $this->isAllowedAttachmentUrl($url)) {
                        Log::warning('email.inbound.url_rejected', [
                            'company_id' => $companyId,
                            'message_id' => $this->safeLogValue($messageId),
                            'host' => parse_url($url, PHP_URL_HOST),
                        ]);

                        continue;
                    }

                    $resp = $client->get($url);

                    if ($resp->successful()) {
                        $binary = $resp->body();
                    } else {
                        Log::warning('email.inbound.fetch_failed', [
                            'company_id' => $companyId,
                            'message_id' => $this->safeLogValue($messageId),
                            'host' => parse_url($url, PHP_URL_HOST),
                            'code' => $resp->status(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('email.inbound.fetch_exception', [
                    'company_id' => $companyId,
                    'message_id' => $this->safeLogValue($messageId),
                    'host' => ! empty($att['url']) ? parse_url((string) $att['url'], PHP_URL_HOST) : null,
                    'err' => $e->getMessage(),
                ]);
            }

            if (! $binary) {
                continue;
            }

            $filename = (string) ($att['filename'] ?? ("email-{$messageId}-{$i}"));
            $mime = (string) ($att['content_type'] ?? '');
            $size = isset($att['size']) ? (int) $att['size'] : strlen($binary);

            if (! $this->isAllowedMime($mime, $filename)) {
                Log::warning('email.inbound.mime_rejected', [
                    'company_id' => $companyId,
                    'message_id' => $this->safeLogValue($messageId),
                    'mime' => $mime,
                    'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                ]);

                continue;
            }

            if (! $this->isAllowedSize($size)) {
                Log::warning('email.inbound.size_rejected', [
                    'company_id' => $companyId,
                    'message_id' => $this->safeLogValue($messageId),
                    'size' => $size,
                ]);

                continue;
            }

            $this->ingest->ingestRawBinary($binary, [
                'company_id' => $companyId,
                'type' => 'other',
                'source' => 'email',
                'sender_email' => $from ?: null,
                'provider_message_id' => $messageId ?: null,
                'original_name' => $filename,
                'mime' => $mime ?: null,
                'size' => $size,
            ]);
        }

        return response('OK', Response::HTTP_OK);
    }

    protected function isVerified(Request $request): bool
    {
        $secret = (string) config('document_ingest.email_webhook_secret', '');

        // Fail closed. If secret is not configured, webhook must not work.
        if ($secret === '') {
            return false;
        }

        $provided = (string) (
            $request->header('X-SayaraForce-Webhook-Secret')
            ?: $request->header('X-GarageCRM-Webhook-Secret')
            ?: $request->header('X-Webhook-Secret')
            ?: $request->input('webhook_secret', '')
        );

        if ($provided === '') {
            return false;
        }

        return hash_equals($secret, $provided);
    }

    protected function resolveCompanyId(string $from): ?int
    {
        $email = strtolower(trim($from));

        if ($email === '') {
            return null;
        }

        $companyId = DB::table('companies')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orWhereRaw('LOWER(business_email) = ?', [$email])
            ->orWhereRaw('LOWER(manager_email) = ?', [$email])
            ->value('id');

        if ($companyId) {
            return (int) $companyId;
        }

        return DB::table('clients')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orWhereRaw('LOWER(email_norm) = ?', [$email])
            ->value('company_id');
    }

    protected function isAllowedAttachmentUrl(string $url): bool
    {
        if (! (bool) config('document_ingest.allow_remote_attachment_urls', false)) {
            return false;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        $allowedHosts = (array) config('document_ingest.allowed_attachment_hosts', []);

        if (! empty($allowedHosts) && ! in_array($host, $allowedHosts, true)) {
            return false;
        }

        $ip = gethostbyname($host);

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Block private/reserved/local IPs to reduce SSRF risk.
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }

    protected function isAllowedMime(string $mime, string $filename): bool
    {
        $allowed = array_filter(array_map(
            fn ($value) => strtolower(trim($value)),
            explode(',', (string) config('document_ingest.allowed_mimes', 'pdf,jpg,jpeg,png'))
        ));

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension !== '' && in_array($extension, $allowed, true)) {
            return true;
        }

        $mimeMap = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
        ];

        $mapped = $mimeMap[strtolower($mime)] ?? null;

        return $mapped && in_array($mapped, $allowed, true);
    }

    protected function isAllowedSize(int $size): bool
    {
        $maxMb = (int) config('document_ingest.max_size_mb', 20);

        return $size > 0 && $size <= ($maxMb * 1024 * 1024);
    }

    protected function hashValue(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return hash('sha256', strtolower($value));
    }

    protected function emailDomain(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        if (! str_contains($email, '@')) {
            return null;
        }

        return substr(strrchr($email, '@'), 1) ?: null;
    }

    protected function safeLogValue(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 80);
    }
}