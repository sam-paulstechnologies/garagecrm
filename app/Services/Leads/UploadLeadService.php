<?php

namespace App\Services\Leads;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UploadLeadService
{
    protected LeadService $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    public function import(array $rows, int $companyId): array
    {
        $created = 0;
        $duplicates = 0;
        $skipped = 0;
        $clientsCreated = 0;
        $segmentsApplied = 0;

        foreach ($rows as $index => $row) {
            $row = $this->cleanRow($row);

            /*
            |--------------------------------------------------------------------------
            | Normalize input
            |--------------------------------------------------------------------------
            */

            $name  = trim((string) ($row['name'] ?? '')) ?: 'Uploaded Lead';
            $email = $this->cleanEmail($row['email'] ?? null);
            $phone = $this->cleanPhone($row['phone'] ?? null);

            /*
            |--------------------------------------------------------------------------
            | Skip empty rows
            |--------------------------------------------------------------------------
            */

            if (! $email && ! $phone) {
                $skipped++;

                Log::info('[UploadLeadService] Row skipped: no email or phone', [
                    'company_id' => $companyId,
                    'row_index' => $index,
                ]);

                continue;
            }

            try {
                /*
                |--------------------------------------------------------------------------
                | Create / reuse client first
                |--------------------------------------------------------------------------
                |
                | Uploaded/imported leads should create clients and then attach leads
                | to those clients.
                |
                | IMPORTANT:
                | These leads are marked as external_source=upload/import.
                | HandleLeadCreatedOutbound will skip instant WhatsApp ACK for these,
                | so uploads do not blindly message customers.
                |
                */

                [$client, $clientWasCreated] = $this->resolveOrCreateClient(
                    companyId: $companyId,
                    name: $name,
                    phone: $phone,
                    email: $email,
                    source: $row['source'] ?? 'upload'
                );

                if ($clientWasCreated) {
                    $clientsCreated++;
                }

                /*
                |--------------------------------------------------------------------------
                | Centralized lead creation
                |--------------------------------------------------------------------------
                |
                | Keep using the existing LeadService so duplicate detection remains
                | centralized.
                |
                */

                $payload = [
                    'company_id'        => $companyId,
                    'client_id'         => $client?->id,
                    'name'              => $name,
                    'email'             => $email,
                    'phone'             => $phone,
                    'source'            => $this->normalizeUploadSource($row['source'] ?? 'upload'),
                    'external_source'   => 'upload',
                    'preferred_channel' => $row['preferred_channel'] ?? 'whatsapp',
                ];

                if (Schema::hasColumn('leads', 'status')) {
                    $payload['status'] = defined(Lead::class . '::STATUS_NEW')
                        ? Lead::STATUS_NEW
                        : 'new';
                }

                if (Schema::hasColumn('leads', 'phone_norm')) {
                    $payload['phone_norm'] = $phone;
                }

                if (Schema::hasColumn('leads', 'email_norm')) {
                    $payload['email_norm'] = $email;
                }

                if (Schema::hasColumn('leads', 'external_payload')) {
                    $payload['external_payload'] = [
                        'source' => 'upload_lead_service',
                        'row_index' => $index,
                        'raw' => $row,
                    ];
                }

                if (Schema::hasColumn('leads', 'external_received_at')) {
                    $payload['external_received_at'] = now();
                }

                /*
                |--------------------------------------------------------------------------
                | Optional import/segmentation fields
                |--------------------------------------------------------------------------
                */

                foreach ($this->optionalLeadFields() as $field) {
                    if (
                        Schema::hasColumn('leads', $field)
                        && array_key_exists($field, $row)
                    ) {
                        $payload[$field] = $this->normalizeFieldValue($field, $row[$field]);
                    }
                }

                $lead = $this->leadService->createOrResolve($payload);

                if (! $lead instanceof Lead) {
                    $duplicates++;

                    Log::info('[UploadLeadService] Lead treated as duplicate/non-lead result', [
                        'company_id' => $companyId,
                        'row_index' => $index,
                        'phone' => $phone,
                        'email' => $email,
                    ]);

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Ensure client_id remains attached
                |--------------------------------------------------------------------------
                */

                if ($client && Schema::hasColumn('leads', 'client_id') && ! $lead->client_id) {
                    $lead->forceFill([
                        'client_id' => $client->id,
                    ])->save();
                }

                /*
                |--------------------------------------------------------------------------
                | Count created vs duplicate
                |--------------------------------------------------------------------------
                */

                if ($lead->wasRecentlyCreated) {
                    $created++;
                } else {
                    $duplicates++;
                }

                /*
                |--------------------------------------------------------------------------
                | Audience / segment assignment
                |--------------------------------------------------------------------------
                */

                if ($this->applyAudienceSegmentation($lead, $row)) {
                    $segmentsApplied++;
                }
            } catch (\Throwable $e) {
                $skipped++;

                Log::error('[UploadLeadService] Lead upload failed', [
                    'company_id' => $companyId,
                    'row_index' => $index,
                    'row'       => $row,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info('[UploadLeadService] Lead upload summary', [
            'company_id' => $companyId,
            'created'    => $created,
            'duplicates' => $duplicates,
            'skipped'    => $skipped,
            'clients_created' => $clientsCreated,
            'segments_applied' => $segmentsApplied,
            'total'      => count($rows),
        ]);

        return [
            'created'    => $created,
            'duplicates' => $duplicates,
            'skipped'    => $skipped,
            'clients_created' => $clientsCreated,
            'segments_applied' => $segmentsApplied,
            'total'      => count($rows),
        ];
    }

    protected function resolveOrCreateClient(
        int $companyId,
        string $name,
        ?string $phone,
        ?string $email,
        string $source
    ): array {
        if (! Schema::hasTable('clients')) {
            return [null, false];
        }

        $query = Client::query()
            ->where('company_id', $companyId);

        $query->where(function ($q) use ($phone, $email) {
            if ($phone) {
                $q->orWhere('phone', $phone);

                if (Schema::hasColumn('clients', 'phone_norm')) {
                    $q->orWhere('phone_norm', $phone);
                }

                if (Schema::hasColumn('clients', 'whatsapp')) {
                    $q->orWhere('whatsapp', $phone);
                }

                if (Schema::hasColumn('clients', 'whatsapp_number')) {
                    $q->orWhere('whatsapp_number', $phone);
                }
            }

            if ($email) {
                $q->orWhere('email', $email);

                if (Schema::hasColumn('clients', 'email_norm')) {
                    $q->orWhere('email_norm', $email);
                }
            }
        });

        $client = $query->first();

        if ($client) {
            $updates = [];

            $possibleUpdates = [
                'name' => $client->name ?: $name,
                'phone' => $client->phone ?: $phone,
                'email' => $client->email ?: $email,
                'source' => $client->source ?: $source,
                'status' => $client->status ?: 'active',
                'phone_norm' => $phone,
                'email_norm' => $email,
                'whatsapp' => $phone,
                'whatsapp_number' => $phone,
            ];

            foreach ($possibleUpdates as $field => $value) {
                if (
                    $value !== null
                    && $value !== ''
                    && Schema::hasColumn('clients', $field)
                    && empty($client->{$field})
                ) {
                    $updates[$field] = $value;
                }
            }

            if (! empty($updates)) {
                $client->update($updates);
            }

            return [$client, false];
        }

        $clientData = [
            'company_id' => $companyId,
            'name'       => $name,
            'phone'      => $phone,
            'email'      => $email,
        ];

        $optionalClientFields = [
            'source' => $source,
            'status' => 'active',
            'phone_norm' => $phone,
            'email_norm' => $email,
            'whatsapp' => $phone,
            'whatsapp_number' => $phone,
        ];

        foreach ($optionalClientFields as $field => $value) {
            if (Schema::hasColumn('clients', $field)) {
                $clientData[$field] = $value;
            }
        }

        $client = Client::create($clientData);

        return [$client, true];
    }

    protected function applyAudienceSegmentation(Lead $lead, array $row): bool
    {
        $class = 'App\\Services\\Audiences\\AudienceResolver';

        if (! class_exists($class)) {
            return false;
        }

        try {
            $resolver = app($class);

            foreach ([
                'resolveForLead',
                'syncForLead',
                'assignForLead',
                'resolve',
            ] as $method) {
                if (method_exists($resolver, $method)) {
                    $resolver->{$method}($lead, $row);

                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[UploadLeadService] Audience segmentation skipped', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    protected function optionalLeadFields(): array
    {
        return [
            'service_category',
            'service_type',
            'vehicle_make',
            'vehicle_model',
            'vehicle_year',
            'plate_number',
            'lead_temperature',
            'lead_priority',
            'customer_type',
            'follow_up_required',
            'follow_up_date',
            'campaign_name',
            'retention_tag',
            'notes',
        ];
    }

    protected function normalizeFieldValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'follow_up_required') {
            return in_array(strtolower((string) $value), ['yes', 'y', 'true', '1'], true) ? 1 : 0;
        }

        if ($field === 'vehicle_year') {
            return is_numeric($value) ? (int) $value : null;
        }

        if ($field === 'follow_up_date') {
            return $value;
        }

        return is_string($value) ? trim($value) : $value;
    }

    protected function normalizeUploadSource(?string $source): string
    {
        $source = strtolower(trim((string) $source));

        if ($source === '') {
            return 'upload';
        }

        if (in_array($source, ['csv', 'excel', 'xlsx', 'xls', 'bulk', 'bulk_import', 'import'], true)) {
            return 'upload';
        }

        return $source;
    }

    protected function cleanRow(array $row): array
    {
        $clean = [];

        foreach ($row as $key => $value) {
            $key = $this->cleanHeader((string) $key);

            if (is_string($value)) {
                $value = trim($value);
            }

            $clean[$key] = $value === '' ? null : $value;
        }

        return $clean;
    }

    protected function cleanHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = trim(strtolower($header));
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }

    protected function cleanEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function cleanPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = trim((string) $phone);

        if (stripos($phone, 'E+') !== false || stripos($phone, 'E-') !== false) {
            $phone = number_format((float) $phone, 0, '', '');
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone);
        $phone = preg_replace('/^\+/', '', $phone);

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone ?: null;
    }
}