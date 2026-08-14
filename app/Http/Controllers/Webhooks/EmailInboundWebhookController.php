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
            ->withOptions([
                'allow_redirects' => false,
                'stream' => true,
            ])
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
                    $encoded = (string) $att['base64'];
                    if (strlen($encoded) > $this->maximumBytes() * 2) {
                        throw new \LengthException('Encoded attachment exceeds the approved limit.');
                    }
                    $binary = base64_decode($encoded, true) ?: null;
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
                        $declaredLength = (int) $resp->header('Content-Length');
                        if ($declaredLength > $this->maximumBytes()) {
                            throw new \LengthException('Remote attachment exceeds the approved limit.');
                        }

                        $stream = $resp->toPsrResponse()->getBody();
                        $binary = '';
                        while (! $stream->eof()) {
                            $binary .= $stream->read(65536);
                            if (strlen($binary) > $this->maximumBytes()) {
                                throw new \LengthException('Remote attachment exceeds the approved limit.');
                            }
                        }
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
                    'exception' => $e::class,
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

        if (empty($allowedHosts) || ! in_array($host, $allowedHosts, true)) {
            return false;
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records) || $records === []) {
            return false;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (! is_string($ip)
                || ! filter_var($ip, FILTER_VALIDATE_IP)
                || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
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
        return $size > 0 && $size <= $this->maximumBytes();
    }

    protected function maximumBytes(): int
    {
        return max(1, (int) config('document_ingest.max_size_mb', 20)) * 1024 * 1024;
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
