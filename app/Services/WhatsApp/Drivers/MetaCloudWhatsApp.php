<?php

namespace App\Services\WhatsApp\Drivers;

use App\Models\System\Company;
use App\Services\WhatsApp\WhatsAppNotifierInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudWhatsApp implements WhatsAppNotifierInterface
{
    protected Company $company;

    protected string $graphBase;
    protected string $apiVersion;

    protected ?string $phoneNumberId;
    protected ?string $accessToken;

    public function __construct(int $companyId)
    {
        $this->company = Company::findOrFail($companyId);

        $this->graphBase  = config('services.whatsapp.meta.graph_base', 'https://graph.facebook.com');
        $this->apiVersion = config('services.whatsapp.meta.api_version', 'v20.0');

        $this->phoneNumberId = $this->resolvePhoneNumberId($companyId);
        $this->accessToken   = $this->resolveAccessToken($companyId);
    }

    public function sendText(string $toE164, string $message): array
    {
        $this->assertConfigured();

        $url = rtrim($this->graphBase, '/') . "/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => ltrim($this->normalizeNumber($toE164), '+'),
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $resp = Http::withToken($this->accessToken)->post($url, $payload);
        $body = $resp->json() ?? [];

        Log::info('[WA][META] sendText', [
            'company_id'      => $this->company->id,
            'phone_number_id' => $this->phoneNumberId,
            'to'              => $toE164,
            'status'          => $resp->status(),
            'body'            => $body,
        ]);

        if (!$resp->successful()) {
            throw new \Exception('[META] sendText failed: ' . json_encode($body));
        }

        return $this->normalizeMetaResponse($body, $resp->status());
    }

    public function sendTemplate(string $toE164, string $template, array $variables = []): array
    {
        $this->assertConfigured();

        $url = rtrim($this->graphBase, '/') . "/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        $components = [];

        if (!empty($variables)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn ($v) => ['type' => 'text', 'text' => (string) $v],
                    $variables
                ),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'   => ltrim($this->normalizeNumber($toE164), '+'),
            'type' => 'template',
            'template' => [
                'name'     => $template,
                'language' => ['code' => 'en'],
                'components' => $components,
            ],
        ];

        $resp = Http::withToken($this->accessToken)->post($url, $payload);
        $body = $resp->json() ?? [];

        Log::info('[WA][META] sendTemplate', [
            'company_id'      => $this->company->id,
            'phone_number_id' => $this->phoneNumberId,
            'to'              => $toE164,
            'template'        => $template,
            'status'          => $resp->status(),
            'body'            => $body,
        ]);

        if (!$resp->successful()) {
            throw new \Exception('[META] sendTemplate failed: ' . json_encode($body));
        }

        return $this->normalizeMetaResponse($body, $resp->status());
    }

    protected function assertConfigured(): void
    {
        if (!$this->phoneNumberId || !$this->accessToken) {
            throw new \Exception("Meta WhatsApp not configured for company_id={$this->company->id}");
        }
    }

    protected function resolvePhoneNumberId(int $companyId): ?string
    {
        $fromCompany = $this->company->meta_phone_number_id ?: null;

        if ($fromCompany) {
            return trim((string) $fromCompany);
        }

        $fromSettings = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', [
                'meta.phone_number_id',
                'meta_phone_number_id',
                'whatsapp.phone_number_id',
                'whatsapp_phone_number_id',
            ])
            ->value('value');

        return $fromSettings ? trim((string) $fromSettings) : null;
    }

    protected function resolveAccessToken(int $companyId): ?string
    {
        if (!empty($this->company->meta_access_token)) {
            return $this->decryptIfNeeded($this->company->meta_access_token);
        }

        $row = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', [
                'meta.access_token',
                'meta_access_token',
                'whatsapp.access_token',
                'whatsapp_access_token',
            ])
            ->select('value', 'is_encrypted')
            ->first();

        if (!$row || empty($row->value)) {
            return null;
        }

        return ((int) ($row->is_encrypted ?? 0) === 1)
            ? $this->decryptIfNeeded($row->value)
            : trim((string) $row->value);
    }

    protected function normalizeMetaResponse(array $body, int $httpStatus): array
    {
        $message = $body['messages'][0] ?? [];
        $contact = $body['contacts'][0] ?? [];

        return [
            'ok' => true,
            'http_status' => $httpStatus,
            'id' => $message['id'] ?? null,
            'sid' => $message['id'] ?? null,
            'message_id' => $message['id'] ?? null,
            'status' => $message['message_status'] ?? 'accepted',
            'wa_id' => $contact['wa_id'] ?? null,
            'raw' => $body,
        ];
    }

    protected function normalizeNumber(?string $number): string
    {
        $number = trim((string) $number);
        $number = preg_replace('/^whatsapp:/i', '', $number);
        $number = preg_replace('/\D+/', '', $number);

        if (str_starts_with($number, '05')) {
            $number = '971' . substr($number, 1);
        }

        if (str_starts_with($number, '9710')) {
            $number = '971' . substr($number, 3);
        }

        return $number;
    }

    protected function decryptIfNeeded(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }
}