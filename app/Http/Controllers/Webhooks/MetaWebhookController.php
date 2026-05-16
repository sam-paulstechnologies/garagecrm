<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\LeadSource;
use App\Models\MetaPage;
use App\Services\Meta\MetaLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function __construct(
        private MetaLeadService $meta
    ) {}

    private function metaConfig(string $key, mixed $default = null): mixed
    {
        return config("services.meta_leads.{$key}")
            ?? config("services.meta.{$key}")
            ?? $default;
    }

    /**
     * Meta Lead Ads webhook verification.
     *
     * GET /api/v1/webhooks/meta/leads
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        $expectedToken = (string) $this->metaConfig('verify_token', '');

        if (
            $mode === 'subscribe' &&
            $expectedToken !== '' &&
            hash_equals($expectedToken, (string) $token)
        ) {
            return response($challenge, 200);
        }

        Log::warning('[META_LEADS][VERIFY_FAILED]', [
            'mode' => $mode,
            'has_token' => ! empty($token),
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Meta Lead Ads webhook receiver.
     *
     * POST /api/v1/webhooks/meta/leads
     */
    public function handle(Request $request)
    {
        $secret = (string) $this->metaConfig('app_secret', '');

        if ($secret !== '') {
            $signature = (string) $request->header('X-Hub-Signature-256', '');

            if (! $this->validSignature($signature, $request->getContent(), $secret)) {
                Log::warning('[META_LEADS][INVALID_SIGNATURE]', [
                    'has_signature' => $signature !== '',
                ]);

                return response()->noContent(Response::HTTP_UNAUTHORIZED);
            }
        }

        $entries = (array) $request->json('entry', []);

        foreach ($entries as $entry) {
            $pageId = $entry['id'] ?? null;

            if (! $pageId) {
                Log::warning('[META_LEADS][MISSING_PAGE_ID]', [
                    'entry' => $entry,
                ]);

                continue;
            }

            $metaPage = MetaPage::where('page_id', (string) $pageId)->first();

            if (! $metaPage) {
                Log::warning('[META_LEADS][UNKNOWN_PAGE]', [
                    'page_id' => $pageId,
                ]);

                continue;
            }

            $companyId = (int) $metaPage->company_id;

            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);

                $leadgenId = $value['leadgen_id'] ?? null;
                $formId = $value['form_id'] ?? null;

                if (! $leadgenId) {
                    Log::warning('[META_LEADS][MISSING_LEADGEN_ID]', [
                        'company_id' => $companyId,
                        'page_id' => $pageId,
                        'change' => $change,
                    ]);

                    continue;
                }

                $leadSource = $this->resolveLeadSource(
                    companyId: $companyId,
                    pageId: (string) $pageId,
                    formId: $formId ? (string) $formId : null
                );

                if ($formId && ! $leadSource) {
                    Log::warning('[META_LEADS][UNKNOWN_OR_INACTIVE_FORM]', [
                        'company_id' => $companyId,
                        'page_id' => $pageId,
                        'form_id' => $formId,
                        'leadgen_id' => $leadgenId,
                    ]);

                    continue;
                }

                try {
                    $row = $this->meta->fetchLeadById(
                        accessToken: (string) $metaPage->page_access_token,
                        leadgenId: (string) $leadgenId
                    );
                } catch (\Throwable $e) {
                    Log::error('[META_LEADS][FETCH_FAILED]', [
                        'company_id' => $companyId,
                        'page_id' => $pageId,
                        'form_id' => $formId,
                        'leadgen_id' => $leadgenId,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }

                if (! is_array($row)) {
                    Log::warning('[META_LEADS][EMPTY_FETCH_RESPONSE]', [
                        'company_id' => $companyId,
                        'page_id' => $pageId,
                        'form_id' => $formId,
                        'leadgen_id' => $leadgenId,
                    ]);

                    continue;
                }

                $this->ingestLead(
                    companyId: $companyId,
                    pageId: (string) $pageId,
                    metaPage: $metaPage,
                    leadSource: $leadSource,
                    formId: $formId ? (string) $formId : ($row['form_id'] ?? null),
                    leadgenId: (string) $leadgenId,
                    row: $row,
                    rawChange: $change
                );
            }
        }

        return response()->noContent();
    }

    private function ingestLead(
        int $companyId,
        string $pageId,
        MetaPage $metaPage,
        ?LeadSource $leadSource,
        ?string $formId,
        string $leadgenId,
        array $row,
        array $rawChange
    ): void {
        $email = $this->normalizeEmail($row['email'] ?? null);
        $phone = $this->normalizePhone($row['phone'] ?? null);
        $phoneNorm = $this->digitsOnly($phone);
        $name = trim((string) ($row['name'] ?? '')) ?: 'Meta Lead';

        $client = $this->resolveClient(
            companyId: $companyId,
            email: $email,
            phone: $phone,
            phoneNorm: $phoneNorm
        );

        if (! $client) {
            $client = $this->createClientSafely([
                'company_id' => $companyId,
                'name' => $name,
                'email' => $email,
                'email_norm' => $email,
                'phone' => $phone,
                'phone_norm' => $phoneNorm,
                'source' => 'meta',
                'status' => 'active',
            ]);
        } else {
            $this->updateClientSafely($client, [
                'name' => $client->name ?: $name,
                'email' => $client->email ?: $email,
                'email_norm' => $email,
                'phone' => $client->phone ?: $phone,
                'phone_norm' => $phoneNorm,
                'source' => $client->source ?: 'meta',
                'status' => $client->status ?: 'active',
            ]);
        }

        $sourceName = $leadSource?->name ?? 'Meta Lead Ads';
        $leadSourceConfig = $this->leadSourceConfig($leadSource);

        $payload = [
            'client_id' => $client?->id,
            'name' => $name,
            'email' => $email,
            'email_norm' => $email,
            'phone' => $phone,
            'phone_norm' => $phoneNorm,
            'status' => 'new',
            'source' => $sourceName,
            'preferred_channel' => 'whatsapp',
            'external_source' => 'meta',
            'external_id' => $leadgenId,
            'external_form_id' => $formId,
            'external_payload' => array_merge($row, [
                '_webhook' => [
                    'page_id' => $pageId,
                    'page_name' => (string) $metaPage->page_name,
                    'form_id' => $formId,
                    'form_name' => data_get($leadSourceConfig, 'form_name'),
                    'lead_source_id' => $leadSource?->id,
                    'leadgen_id' => $leadgenId,
                    'raw_change' => $rawChange,
                ],
            ]),
            'external_received_at' => now(),
            'lead_source_id' => $leadSource?->id,
            'campaign_name' => $row['campaign_name'] ?? null,
        ];

        $payload = $this->filterForModel(new Lead(), $payload);

        /*
        |--------------------------------------------------------------------------
        | Dedupe Rule
        |--------------------------------------------------------------------------
        | 1. external_id first
        | 2. then phone/email inside same company
        |
        | This avoids creating duplicate CRM leads when Meta sends the same person
        | again or when the same lead already exists from WhatsApp / Website.
        |--------------------------------------------------------------------------
        */
        $lead = $this->findLeadByExternalId($companyId, $leadgenId);

        $matchedExistingBy = null;

        if (! $lead) {
            $lead = $this->findLeadByPhoneOrEmail(
                companyId: $companyId,
                email: $email,
                phone: $phone,
                phoneNorm: $phoneNorm
            );

            if ($lead) {
                $matchedExistingBy = $this->matchedOn($lead, $email, $phone, $phoneNorm);

                $this->recordDuplicateIfPossible(
                    companyId: $companyId,
                    primaryLeadId: (int) $lead->id,
                    externalId: $leadgenId,
                    externalFormId: $formId,
                    name: $name,
                    email: $email,
                    phone: $phone,
                    phoneNorm: $phoneNorm,
                    matchedOn: $matchedExistingBy,
                    payload: $row
                );
            }
        }

        if ($lead) {
            $wasNew = false;

            $lead->fill($payload);
            $lead->save();
        } else {
            $wasNew = true;

            $lead = Lead::create(array_merge([
                'company_id' => $companyId,
            ], $payload));
        }

        if ($leadSource) {
            $leadSource->update([
                'last_received_at' => now(),
            ]);
        }

        Log::info('[META_LEADS][LEAD_CAPTURED]', [
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'client_id' => $client?->id,
            'lead_source_id' => $leadSource?->id,
            'page_id' => $pageId,
            'form_id' => $formId,
            'leadgen_id' => $leadgenId,
            'was_new_lead' => $wasNew,
            'matched_existing_by' => $matchedExistingBy,
            'ack_owner' => 'LeadCreated listener / HandleLeadCreatedOutbound',
            'direct_ack_sent' => false,
        ]);
    }

    private function resolveLeadSource(int $companyId, string $pageId, ?string $formId): ?LeadSource
    {
        if (! $formId) {
            return null;
        }

        return LeadSource::query()
            ->where('company_id', $companyId)
            ->where('type', 'meta')
            ->whereIn('status', ['active', 'connected'])
            ->where('config->page_id', (string) $pageId)
            ->where('config->form_id', (string) $formId)
            ->first();
    }

    private function resolveClient(int $companyId, ?string $email, ?string $phone, ?string $phoneNorm): ?Client
    {
        if (! $email && ! $phone && ! $phoneNorm) {
            return null;
        }

        return Client::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($email, $phone, $phoneNorm) {
                if ($email) {
                    $query->orWhere('email', $email);

                    if (Schema::hasColumn('clients', 'email_norm')) {
                        $query->orWhere('email_norm', $email);
                    }
                }

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }

                if ($phoneNorm) {
                    if (Schema::hasColumn('clients', 'phone_norm')) {
                        $query->orWhere('phone_norm', $phoneNorm);
                    }

                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') = ?", [
                        $phoneNorm,
                    ]);
                }
            })
            ->first();
    }

    private function createClientSafely(array $values): ?Client
    {
        $data = $this->filterForModel(new Client(), $values);

        if (empty($data['company_id']) || empty($data['name'])) {
            return null;
        }

        return Client::create($data);
    }

    private function updateClientSafely(Client $client, array $values): void
    {
        $updates = [];

        foreach ($values as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (! $this->modelAllows($client, $field)) {
                continue;
            }

            if ((string) ($client->{$field} ?? '') === '') {
                $updates[$field] = $value;
            }
        }

        if (! empty($updates)) {
            $client->update($updates);
        }
    }

    private function findLeadByExternalId(int $companyId, string $externalId): ?Lead
    {
        $query = Lead::query()
            ->where('company_id', $companyId)
            ->where('external_id', $externalId);

        if (Schema::hasColumn('leads', 'external_source')) {
            $query->where('external_source', 'meta');
        }

        return $query->first();
    }

    private function findLeadByPhoneOrEmail(int $companyId, ?string $email, ?string $phone, ?string $phoneNorm): ?Lead
    {
        if (! $email && ! $phone && ! $phoneNorm) {
            return null;
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($email, $phone, $phoneNorm) {
                if ($email) {
                    $query->orWhere('email', $email);

                    if (Schema::hasColumn('leads', 'email_norm')) {
                        $query->orWhere('email_norm', $email);
                    }
                }

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }

                if ($phoneNorm) {
                    if (Schema::hasColumn('leads', 'phone_norm')) {
                        $query->orWhere('phone_norm', $phoneNorm);
                    }

                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') = ?", [
                        $phoneNorm,
                    ]);
                }
            })
            ->latest('id')
            ->first();
    }

    private function matchedOn(Lead $lead, ?string $email, ?string $phone, ?string $phoneNorm): string
    {
        $emailMatched = false;
        $phoneMatched = false;

        if ($email) {
            $emailMatched = strtolower((string) $lead->email) === $email
                || strtolower((string) ($lead->email_norm ?? '')) === $email;
        }

        if ($phone || $phoneNorm) {
            $leadPhoneNorm = $this->digitsOnly((string) ($lead->phone_norm ?? $lead->phone ?? ''));

            $phoneMatched = ($phone && (string) $lead->phone === $phone)
                || ($phoneNorm && $leadPhoneNorm === $phoneNorm);
        }

        if ($emailMatched && $phoneMatched) {
            return 'both';
        }

        if ($emailMatched) {
            return 'email';
        }

        return 'phone';
    }

    private function recordDuplicateIfPossible(
        int $companyId,
        int $primaryLeadId,
        string $externalId,
        ?string $externalFormId,
        string $name,
        ?string $email,
        ?string $phone,
        ?string $phoneNorm,
        string $matchedOn,
        array $payload
    ): void {
        if (! Schema::hasTable('lead_duplicates')) {
            return;
        }

        try {
            $exists = DB::table('lead_duplicates')
                ->where('company_id', $companyId)
                ->where('external_source', 'meta')
                ->where('external_id', $externalId)
                ->exists();

            if ($exists) {
                return;
            }

            DB::table('lead_duplicates')->insert([
                'company_id' => $companyId,
                'primary_lead_id' => $primaryLeadId,
                'external_source' => 'meta',
                'external_id' => $externalId,
                'external_form_id' => $externalFormId,
                'name' => $name,
                'email' => $email,
                'email_norm' => $email,
                'phone' => $phone,
                'phone_norm' => $phoneNorm,
                'matched_on' => in_array($matchedOn, ['email', 'phone', 'both'], true) ? $matchedOn : 'phone',
                'window_days' => 30,
                'reason' => 'Meta lead matched existing CRM lead by ' . $matchedOn,
                'payload' => json_encode($payload),
                'detected_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[META_LEADS][DUPLICATE_RECORD_FAILED]', [
                'company_id' => $companyId,
                'primary_lead_id' => $primaryLeadId,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function leadSourceConfig(?LeadSource $leadSource): array
    {
        if (! $leadSource) {
            return [];
        }

        $config = $leadSource->config ?? [];

        if (is_string($config)) {
            return json_decode($config, true) ?: [];
        }

        return is_array($config) ? $config : [];
    }

    private function filterForModel(object $model, array $values): array
    {
        $data = [];

        foreach ($values as $field => $value) {
            if ($value === null) {
                continue;
            }

            if ($this->modelAllows($model, $field)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    private function modelAllows(object $model, string $field): bool
    {
        if (! method_exists($model, 'getTable') || ! method_exists($model, 'getFillable')) {
            return false;
        }

        try {
            $table = $model->getTable();

            if (! Schema::hasColumn($table, $field)) {
                return false;
            }

            $fillable = $model->getFillable();

            return empty($fillable) || in_array($field, $fillable, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (! $phone) {
            return null;
        }

        return $phone;
    }

    private function digitsOnly(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? $digits : null;
    }

    private function validSignature(string $sigHeader, string $raw, string $secret): bool
    {
        if (! Str::startsWith($sigHeader, 'sha256=')) {
            return false;
        }

        $expected = hash_hmac('sha256', $raw, $secret);
        $actual = Str::after($sigHeader, 'sha256=');

        return hash_equals($expected, $actual);
    }
}