<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\LeadSource;
use App\Models\MetaPage;
use App\Services\Meta\MetaLeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function __construct(
        private MetaLeadService $meta
    ) {}

    /**
     * Meta Lead Ads webhook verification.
     *
     * GET /api/v1/webhooks/meta/leads
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub.mode');
        $token     = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        $expectedToken = (string) config('services.meta_leads.verify_token');

        if (
            $mode === 'subscribe' &&
            $expectedToken !== '' &&
            hash_equals($expectedToken, (string) $token)
        ) {
            return response($challenge, 200);
        }

        Log::warning('[META_LEADS][VERIFY_FAILED]', [
            'mode'      => $mode,
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
        $secret = (string) config('services.meta_leads.app_secret', '');

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
                $formId    = $value['form_id'] ?? null;

                if (! $leadgenId) {
                    Log::warning('[META_LEADS][MISSING_LEADGEN_ID]', [
                        'company_id' => $companyId,
                        'page_id'    => $pageId,
                        'change'     => $change,
                    ]);

                    continue;
                }

                if (! $formId) {
                    Log::warning('[META_LEADS][MISSING_FORM_ID]', [
                        'company_id' => $companyId,
                        'page_id'    => $pageId,
                        'leadgen_id' => $leadgenId,
                        'change'     => $change,
                    ]);
                }

                $leadSource = $this->resolveLeadSource(
                    companyId: $companyId,
                    pageId: (string) $pageId,
                    formId: $formId ? (string) $formId : null
                );

                if ($formId && ! $leadSource) {
                    Log::warning('[META_LEADS][UNKNOWN_OR_INACTIVE_FORM]', [
                        'company_id' => $companyId,
                        'page_id'    => $pageId,
                        'form_id'    => $formId,
                        'leadgen_id' => $leadgenId,
                    ]);

                    continue;
                }

                try {
                    $row = $this->meta->fetchLeadById(
                        $metaPage->page_access_token,
                        (string) $leadgenId
                    );
                } catch (\Throwable $e) {
                    Log::error('[META_LEADS][FETCH_FAILED]', [
                        'company_id' => $companyId,
                        'page_id'    => $pageId,
                        'form_id'    => $formId,
                        'leadgen_id' => $leadgenId,
                        'error'      => $e->getMessage(),
                    ]);

                    continue;
                }

                if (! is_array($row)) {
                    Log::warning('[META_LEADS][EMPTY_FETCH_RESPONSE]', [
                        'company_id' => $companyId,
                        'page_id'    => $pageId,
                        'form_id'    => $formId,
                        'leadgen_id' => $leadgenId,
                    ]);

                    continue;
                }

                $email = $this->normalizeEmail($row['email'] ?? null);
                $phone = $this->normalizePhone($row['phone'] ?? null);
                $phoneNorm = $this->digitsOnly($phone);
                $name = trim((string) ($row['name'] ?? '')) ?: 'Meta Lead';

                /*
                |--------------------------------------------------------------------------
                | Client Resolution
                |--------------------------------------------------------------------------
                */

                $client = $this->resolveClient(
                    companyId: $companyId,
                    email: $email,
                    phone: $phone,
                    phoneNorm: $phoneNorm
                );

                if (! $client) {
                    $clientData = [
                        'company_id' => $companyId,
                        'name'       => $name,
                        'email'      => $email,
                        'phone'      => $phone,
                        'source'     => 'meta',
                        'status'     => 'active',
                    ];

                    if ($this->modelAllows(new Client(), 'phone_norm')) {
                        $clientData['phone_norm'] = $phoneNorm ?: null;
                    }

                    if ($this->modelAllows(new Client(), 'email_norm')) {
                        $clientData['email_norm'] = $email ?: null;
                    }

                    $client = Client::create($clientData);
                } else {
                    $this->updateClientSafely($client, [
                        'name'       => $client->name ?: $name,
                        'email'      => $client->email ?: $email,
                        'phone'      => $client->phone ?: $phone,
                        'source'     => $client->source ?: 'meta',
                        'status'     => $client->status ?: 'active',
                        'phone_norm' => $phoneNorm ?: null,
                        'email_norm' => $email ?: null,
                    ]);
                }

                $sourceName = $leadSource?->name ?? 'Meta Lead Ads';

                /*
                |--------------------------------------------------------------------------
                | Lead Creation / Update
                |--------------------------------------------------------------------------
                */

                $updateData = [
                    'client_id'            => $client->id,
                    'name'                 => $name,
                    'email'                => $email,
                    'phone'                => $phone,
                    'status'               => 'new',
                    'source'               => $sourceName,
                    'preferred_channel'    => 'whatsapp',
                    'external_form_id'     => $formId ? (string) $formId : ($row['form_id'] ?? null),
                    'external_payload'     => array_merge($row, [
                        '_webhook' => [
                            'page_id'        => (string) $pageId,
                            'page_name'      => (string) $metaPage->page_name,
                            'form_id'        => $formId ? (string) $formId : null,
                            'form_name'      => $leadSource?->configValue('form_name'),
                            'lead_source_id' => $leadSource?->id,
                            'leadgen_id'     => (string) $leadgenId,
                            'raw_change'     => $change,
                        ],
                    ]),
                    'external_received_at' => now(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Optional column/fillable support
                |--------------------------------------------------------------------------
                */

                if ($leadSource && $this->modelAllows(new Lead(), 'lead_source_id')) {
                    $updateData['lead_source_id'] = $leadSource->id;
                }

                if ($this->modelAllows(new Lead(), 'phone_norm')) {
                    $updateData['phone_norm'] = $phoneNorm ?: null;
                }

                if ($this->modelAllows(new Lead(), 'email_norm')) {
                    $updateData['email_norm'] = $email ?: null;
                }

                if ($this->modelAllows(new Lead(), 'external_source')) {
                    $identity['external_source'] = 'meta';
                }

                /*
                |--------------------------------------------------------------------------
                | Important: One ACK Owner
                |--------------------------------------------------------------------------
                |
                | We no longer fire lead.created directly from this webhook.
                |
                | Why:
                | - Lead::updateOrCreate() triggers the Lead model created event
                |   only when a new lead is actually created.
                | - LeadCreated -> HandleLeadCreatedOutbound should own the first ACK.
                | - Direct fireEvent() here caused duplicate Meta lead ACK risk.
                |
                | Result:
                | - Brand new Meta lead: ACK is handled by LeadCreated listener.
                | - Existing Meta lead update: no duplicate first ACK is sent.
                |
                */

                $identity = [
                    'company_id'  => $companyId,
                    'external_id' => (string) $leadgenId,
                ];

                if ($this->modelAllows(new Lead(), 'external_source')) {
                    $identity['external_source'] = 'meta';
                }

                $lead = Lead::updateOrCreate($identity, $updateData);

                if ($leadSource) {
                    $leadSource->update([
                        'last_received_at' => now(),
                    ]);
                }

                Log::info('[META_LEADS][LEAD_CAPTURED]', [
                    'company_id'      => $lead->company_id,
                    'lead_id'         => $lead->id,
                    'client_id'       => $client->id,
                    'lead_source_id'  => $leadSource?->id,
                    'page_id'         => $pageId,
                    'form_id'         => $formId,
                    'leadgen_id'      => $leadgenId,
                    'was_new_lead'    => (bool) $lead->wasRecentlyCreated,
                    'ack_owner'       => 'LeadCreated listener / HandleLeadCreatedOutbound',
                    'direct_ack_sent' => false,
                ]);
            }
        }

        return response()->noContent();
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
            ->where(function ($query) use ($pageId, $formId) {
                $query
                    ->where('config->page_id', (string) $pageId)
                    ->where('config->form_id', (string) $formId);
            })
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
        $actual   = Str::after($sigHeader, 'sha256=');

        return hash_equals($expected, $actual);
    }
}