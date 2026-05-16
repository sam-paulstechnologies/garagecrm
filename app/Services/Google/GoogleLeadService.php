<?php

namespace App\Services\Google;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\GoogleLeadWebhookLog;
use App\Models\LeadSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleLeadService
{
    public function ingest(array $payload, array $requestMeta = []): array
    {
        $googleKey = $this->stringValue($payload['google_key'] ?? null);
        $externalId = $this->stringValue($payload['lead_id'] ?? null);
        $externalFormId = $this->stringValue($payload['form_id'] ?? null);

        $log = GoogleLeadWebhookLog::create([
            'external_id' => $externalId,
            'external_form_id' => $externalFormId,
            'google_key_hash' => $googleKey ? hash('sha256', $googleKey) : null,
            'status' => 'received',
            'payload' => [
                'payload' => $payload,
                'request_meta' => $requestMeta,
            ],
            'received_at' => now(),
        ]);

        if (! $googleKey) {
            $log->markInvalidPayload('Missing google_key.');

            return [
                'ok' => false,
                'status' => 'invalid_payload',
                'http_status' => 422,
                'message' => 'Missing google_key.',
            ];
        }

        if (! $externalId) {
            $log->markInvalidPayload('Missing lead_id.');

            return [
                'ok' => false,
                'status' => 'invalid_payload',
                'http_status' => 422,
                'message' => 'Missing lead_id.',
            ];
        }

        $leadSource = $this->resolveLeadSource($googleKey, $externalFormId);

        if (! $leadSource) {
            $log->markInvalidKey('Invalid Google webhook key.');

            return [
                'ok' => false,
                'status' => 'invalid_key',
                'http_status' => 401,
                'message' => 'Invalid Google webhook key.',
            ];
        }

        $companyId = (int) $leadSource->company_id;

        $log->update([
            'company_id' => $companyId,
            'lead_source_id' => $leadSource->id,
        ]);

        try {
            return DB::transaction(function () use (
                $payload,
                $requestMeta,
                $log,
                $leadSource,
                $companyId,
                $externalId,
                $externalFormId
            ) {
                $flat = $this->flattenGoogleFields((array) ($payload['user_column_data'] ?? []));

                $email = $this->normalizeEmail(
                    $flat['email']
                    ?? $flat['email_address']
                    ?? $flat['work_email']
                    ?? null
                );

                $phone = $this->normalizePhone(
                    $flat['phone_number']
                    ?? $flat['phone']
                    ?? $flat['mobile_number']
                    ?? $flat['mobile']
                    ?? null
                );

                $phoneNorm = $this->digitsOnly($phone);
                $name = $this->resolveName($flat, $email);

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
                        'source' => 'google',
                        'status' => 'active',
                        'preferred_channel' => $phone ? 'whatsapp' : ($email ? 'email' : 'phone'),
                    ]);
                } else {
                    $this->updateClientSafely($client, [
                        'name' => $client->name ?: $name,
                        'email' => $client->email ?: $email,
                        'email_norm' => $email,
                        'phone' => $client->phone ?: $phone,
                        'phone_norm' => $phoneNorm,
                        'source' => $client->source ?: 'google',
                        'status' => $client->status ?: 'active',
                        'preferred_channel' => $client->preferred_channel ?: ($phone ? 'whatsapp' : ($email ? 'email' : 'phone')),
                    ]);
                }

                $sourceName = $leadSource->name ?: config('services.google_leads.default_source_name', 'Google Ads');

                $leadPayload = [
                    'company_id' => $companyId,
                    'client_id' => $client?->id,
                    'name' => $name,
                    'email' => $email,
                    'email_norm' => $email,
                    'phone' => $phone,
                    'phone_norm' => $phoneNorm,
                    'status' => 'new',
                    'source' => $sourceName,
                    'preferred_channel' => $phone ? 'whatsapp' : ($email ? 'email' : 'phone'),
                    'external_source' => 'google',
                    'external_id' => $externalId,
                    'external_form_id' => $externalFormId,
                    'external_payload' => [
                        'provider' => 'google',
                        'raw' => $payload,
                        'flattened' => $flat,
                        'request_meta' => $requestMeta,
                        'lead_source' => [
                            'id' => $leadSource->id,
                            'name' => $leadSource->name,
                            'config' => $leadSource->config,
                        ],
                    ],
                    'external_received_at' => now(),
                    'lead_source_id' => $leadSource->id,
                    'campaign_name' => $this->stringValue(
                        $payload['campaign_name']
                        ?? $payload['campaign_id']
                        ?? data_get($leadSource->config, 'campaign_name')
                        ?? data_get($leadSource->config, 'campaign_id')
                    ),
                    'service_category' => $this->stringValue(
                        $flat['service_category']
                        ?? $flat['category']
                        ?? null
                    ),
                    'service_type' => $this->stringValue(
                        $flat['service_required']
                        ?? $flat['service']
                        ?? $flat['service_type']
                        ?? $flat['interested_in']
                        ?? null
                    ),
                    'vehicle_make' => $this->stringValue(
                        $flat['vehicle_make']
                        ?? $flat['car_make']
                        ?? $flat['make']
                        ?? null
                    ),
                    'vehicle_model' => $this->stringValue(
                        $flat['vehicle_model']
                        ?? $flat['car_model']
                        ?? $flat['model']
                        ?? null
                    ),
                    'vehicle_year' => $this->numericYear(
                        $flat['vehicle_year']
                        ?? $flat['car_year']
                        ?? $flat['year']
                        ?? null
                    ),
                    'plate_number' => $this->stringValue(
                        $flat['plate_number']
                        ?? $flat['registration_number']
                        ?? null
                    ),
                    'notes' => $this->stringValue(
                        $flat['message']
                        ?? $flat['comments']
                        ?? $flat['notes']
                        ?? null
                    ),
                ];

                $leadPayload = $this->filterForModel(new Lead(), $leadPayload);

                $lead = $this->findLeadByExternalId($companyId, $externalId);
                $matchedExistingBy = null;

                if ($lead) {
                    $matchedExistingBy = 'external_id';
                }

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
                            externalId: $externalId,
                            externalFormId: $externalFormId,
                            name: $name,
                            email: $email,
                            phone: $phone,
                            phoneNorm: $phoneNorm,
                            matchedOn: $matchedExistingBy,
                            payload: $payload
                        );
                    }
                }

                if ($lead) {
                    $lead->fill($leadPayload);
                    $lead->save();

                    $leadSource->update([
                        'last_received_at' => now(),
                    ]);

                    $log->markDuplicate(
                        leadId: (int) $lead->id,
                        matchedBy: $matchedExistingBy ?: 'existing',
                        companyId: $companyId,
                        leadSourceId: (int) $leadSource->id
                    );

                    Log::info('[GOOGLE_LEADS][DUPLICATE_OR_UPDATED]', [
                        'company_id' => $companyId,
                        'lead_id' => $lead->id,
                        'lead_source_id' => $leadSource->id,
                        'external_id' => $externalId,
                        'matched_existing_by' => $matchedExistingBy,
                        'direct_ack_sent' => false,
                    ]);

                    return [
                        'ok' => true,
                        'status' => 'duplicate',
                        'http_status' => 200,
                        'lead_id' => $lead->id,
                        'matched_existing_by' => $matchedExistingBy,
                    ];
                }

                $lead = Lead::create($leadPayload);

                $leadSource->update([
                    'last_received_at' => now(),
                ]);

                $log->markProcessed(
                    leadId: (int) $lead->id,
                    companyId: $companyId,
                    leadSourceId: (int) $leadSource->id
                );

                Log::info('[GOOGLE_LEADS][LEAD_CAPTURED]', [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'lead_source_id' => $leadSource->id,
                    'external_id' => $externalId,
                    'direct_ack_sent' => false,
                    'ack_owner' => 'LeadCreated listener / HandleLeadCreatedOutbound',
                ]);

                return [
                    'ok' => true,
                    'status' => 'processed',
                    'http_status' => 200,
                    'lead_id' => $lead->id,
                ];
            });
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());

            Log::error('[GOOGLE_LEADS][INGEST_FAILED]', [
                'log_id' => $log->id,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 'failed',
                'http_status' => 500,
                'message' => 'Google lead ingest failed.',
            ];
        }
    }

    private function resolveLeadSource(string $googleKey, ?string $externalFormId): ?LeadSource
    {
        $sourceType = config('services.google_leads.source_type', 'google');

        $sources = LeadSource::query()
            ->where('type', $sourceType)
            ->whereIn('status', ['active', 'connected'])
            ->where(function ($query) use ($googleKey) {
                $query->where('form_token', $googleKey)
                    ->orWhere('config->webhook_key', $googleKey);
            })
            ->get();

        foreach ($sources as $source) {
            $configuredFormId = $this->stringValue(data_get($source->config, 'form_id'));

            if ($configuredFormId && $externalFormId && $configuredFormId !== $externalFormId) {
                continue;
            }

            return $source;
        }

        return null;
    }

    private function flattenGoogleFields(array $rows): array
    {
        $flat = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rawKey = $row['column_name']
                ?? $row['column_id']
                ?? $row['field_name']
                ?? $row['name']
                ?? null;

            if (! $rawKey) {
                continue;
            }

            $key = Str::of((string) $rawKey)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();

            $value = $row['string_value']
                ?? $row['value']
                ?? null;

            if ($value === null && isset($row['string_values']) && is_array($row['string_values'])) {
                $value = $row['string_values'][0] ?? null;
            }

            if ($value === null && isset($row['values']) && is_array($row['values'])) {
                $value = $row['values'][0] ?? null;
            }

            $value = $this->stringValue($value);

            if ($key && $value !== null && $value !== '') {
                $flat[$key] = $value;
            }
        }

        return $flat;
    }

    private function resolveName(array $flat, ?string $email): string
    {
        $name = $this->stringValue(
            $flat['full_name']
            ?? $flat['name']
            ?? $flat['contact_name']
            ?? null
        );

        if (! $name) {
            $first = $this->stringValue($flat['first_name'] ?? null);
            $last = $this->stringValue($flat['last_name'] ?? null);
            $name = trim(($first ?: '') . ' ' . ($last ?: ''));
        }

        if (! $name && $email) {
            $name = Str::before($email, '@');
        }

        return $name ?: 'Google Lead';
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
            $query->where('external_source', 'google');
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
                ->where('external_source', 'google')
                ->where('external_id', $externalId)
                ->exists();

            if ($exists) {
                return;
            }

            DB::table('lead_duplicates')->insert([
                'company_id' => $companyId,
                'primary_lead_id' => $primaryLeadId,
                'external_source' => 'google',
                'external_id' => $externalId,
                'external_form_id' => $externalFormId,
                'name' => $name,
                'email' => $email,
                'email_norm' => $email,
                'phone' => $phone,
                'phone_norm' => $phoneNorm,
                'matched_on' => in_array($matchedOn, ['email', 'phone', 'both'], true) ? $matchedOn : 'phone',
                'window_days' => 30,
                'reason' => 'Google lead matched existing CRM lead by ' . $matchedOn,
                'payload' => json_encode($payload),
                'detected_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[GOOGLE_LEADS][DUPLICATE_RECORD_FAILED]', [
                'company_id' => $companyId,
                'primary_lead_id' => $primaryLeadId,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
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

    private function numericYear(mixed $value): ?int
    {
        $value = preg_replace('/\D+/', '', (string) $value);

        if ($value === '') {
            return null;
        }

        $year = (int) $value;

        if ($year < 1900 || $year > ((int) date('Y') + 2)) {
            return null;
        }

        return $year;
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}