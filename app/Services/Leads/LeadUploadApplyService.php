<?php

namespace App\Services\Leads;

use App\Jobs\SendWhatsAppTemplateJob;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\LeadUploadBatch;
use App\Models\Client\LeadUploadRow;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadUploadApplyService
{
    public function dryRun(LeadUploadBatch $batch, ?int $userId = null, string $ackMode = 'import_only'): array
    {
        return $this->process($batch, false, $userId, $ackMode);
    }

    public function apply(LeadUploadBatch $batch, ?int $userId = null, string $ackMode = 'import_only'): array
    {
        return $this->process($batch, true, $userId, $ackMode);
    }

    private function process(LeadUploadBatch $batch, bool $apply, ?int $userId, string $ackMode): array
    {
        $summary = $this->emptySummary($apply ? 'apply' : 'dry_run');
        $summary['ack_mode'] = $ackMode === 'send_ack' ? 'send_ack' : 'import_only';
        $records = [];

        $rows = LeadUploadRow::query()
            ->where('company_id', $batch->company_id)
            ->where('batch_id', $batch->id)
            ->orderBy('row_number')
            ->get();

        foreach ($rows as $row) {
            $record = $this->classifyRow($batch, $row);

            if ($record['eligible']) {
                $summary['eligible_rows']++;
            } else {
                $summary['rows_skipped']++;
                $summary[$record['overall_status']] = ($summary[$record['overall_status']] ?? 0) + 1;
                $records[] = $record;
                continue;
            }

            $summary[$record['overall_status']] = ($summary[$record['overall_status']] ?? 0) + 1;

            if ($record['lead_action'] === 'duplicate_recent_lead') {
                $summary['duplicate_leads_blocked']++;
            }

            if ($record['overall_status'] === 'ready_to_apply') {
                foreach ([
                    'clients_to_create' => $record['client_action'] === 'create_client',
                    'clients_to_reuse' => $record['client_action'] === 'reuse_client',
                    'leads_to_create' => $record['lead_action'] === 'create_lead',
                    'vehicles_to_create' => $record['vehicle_action'] === 'create_vehicle',
                    'vehicles_to_reuse' => $record['vehicle_action'] === 'reuse_vehicle',
                    'vehicles_missing' => $record['vehicle_action'] === 'vehicle_optional_missing',
                ] as $key => $increment) {
                    if ($increment) {
                        $summary[$key]++;
                    }
                }
            }

            if (! $apply || $record['overall_status'] !== 'ready_to_apply') {
                $records[] = $record;
                continue;
            }

            try {
                $applied = DB::transaction(fn () => $this->applyRow($batch, $row, $record, $userId, $summary['ack_mode']));
                $summary['rows_applied']++;
                $record = array_merge($record, $applied);
                $ackStatus = $record['ack_status'] ?? 'not_requested';
                $summary['ack_' . $ackStatus] = ($summary['ack_' . $ackStatus] ?? 0) + 1;
            } catch (\Throwable $e) {
                $summary['errors']++;
                $record['overall_status'] = 'error';
                $record['reason'] = $e->getMessage();
            }

            $records[] = $record;
        }

        if ($apply) {
            $this->refreshBatchAfterApply($batch, $summary, $userId);
        }

        $summary['records'] = $records;

        return $summary;
    }

    private function classifyRow(LeadUploadBatch $batch, LeadUploadRow $row): array
    {
        $payload = array_merge($row->raw_payload ?? [], $row->normalized_payload ?? []);
        $name = trim((string) ($payload['name'] ?? ''));
        $phone = $this->normalizePhone($payload['contact_phone'] ?? $payload['whatsapp'] ?? $payload['phone'] ?? null);
        $email = $this->normalizeEmail($payload['email'] ?? null);
        $client = $this->findClient($batch->company_id, $phone, $email);
        $lead = $this->findRecentLead($batch->company_id, $phone, $email);

        $record = [
            'row_id' => $row->id,
            'row_number' => $row->row_number,
            'eligible' => false,
            'overall_status' => 'skipped_not_approved',
            'reason' => null,
            'client_action' => null,
            'client_id' => $client?->id,
            'client_name' => $client?->name,
            'lead_action' => null,
            'lead_id' => $lead?->id,
            'vehicle_action' => null,
            'vehicle_id' => null,
            'ack_status' => 'not_sent_phase_9f',
        ];

        if ($row->review_status === 'applied') {
            return array_merge($record, [
                'overall_status' => 'skipped_already_applied',
                'reason' => 'Row is already applied.',
            ]);
        }

        if ($row->review_status !== 'approved') {
            return array_merge($record, [
                'overall_status' => 'skipped_not_approved',
                'reason' => 'Row is not approved.',
            ]);
        }

        if ($row->validation_status === 'invalid') {
            return array_merge($record, [
                'overall_status' => 'skipped_invalid',
                'reason' => 'Invalid rows cannot be applied.',
            ]);
        }

        if ($name === '' || ! $phone) {
            return array_merge($record, [
                'eligible' => true,
                'overall_status' => 'blocked_missing_required_data',
                'reason' => 'Name and phone/WhatsApp are required.',
            ]);
        }

        if ($lead) {
            return array_merge($record, [
                'eligible' => true,
                'overall_status' => 'blocked_duplicate_recent_lead',
                'reason' => "Recent duplicate lead #{$lead->id} exists.",
                'client_action' => $client ? 'reuse_client' : 'create_client',
                'lead_action' => 'duplicate_recent_lead',
                'vehicle_action' => $this->classifyVehicle($batch->company_id, $client, $payload)['action'],
            ]);
        }

        $vehicle = $this->classifyVehicle($batch->company_id, $client, $payload);

        return array_merge($record, [
            'eligible' => true,
            'overall_status' => 'ready_to_apply',
            'reason' => 'Approved row is safe to apply without sending WhatsApp.',
            'client_action' => $client ? 'reuse_client' : 'create_client',
            'lead_action' => 'create_lead',
            'vehicle_action' => $vehicle['action'],
            'vehicle_id' => $vehicle['vehicle']?->id,
        ]);
    }

    private function applyRow(LeadUploadBatch $batch, LeadUploadRow $row, array $record, ?int $userId, string $ackMode): array
    {
        $payload = array_merge($row->raw_payload ?? [], $row->normalized_payload ?? []);
        $clientResult = $this->resolveOrCreateClient($batch->company_id, $payload);
        $journeyMapping = $this->resolveCampaignMapping($batch, $payload['campaign_type'] ?? null);
        $ackStatus = $ackMode === 'send_ack'
            ? $this->ackEligibilityStatus($row, $payload, $journeyMapping)
            : 'not_requested';
        $lead = $this->createLeadWithoutEvents($batch->company_id, $clientResult['client'], $payload, $row, $journeyMapping, $ackMode === 'send_ack', $ackStatus);
        $vehicleResult = $this->resolveOrCreateVehicle($batch->company_id, $clientResult['client'], $payload);

        if ($ackMode === 'send_ack' && $ackStatus === 'queued') {
            $ackStatus = $this->queueInstantAck($batch, $row, $lead, $payload, $journeyMapping);
            $leadPayload = $lead->external_payload ?? [];
            $leadPayload['ack_send_status'] = $ackStatus;
            $lead->forceFill(['external_payload' => $leadPayload])->saveQuietly();
        }

        $meta = $row->raw_payload ?? [];
        $meta['_phase_9f_apply'] = [
            'applied_by' => $userId,
            'applied_at' => now()->toIso8601String(),
            'client_action' => $clientResult['action'],
            'lead_action' => 'created',
            'vehicle_action' => $vehicleResult['action'],
            'ack_mode' => $ackMode,
            'ack_status' => $ackStatus,
        ];

        $row->update([
            'review_status' => 'applied',
            'client_match_id' => $clientResult['client']->id,
            'lead_match_id' => $lead->id,
            'vehicle_match_id' => $vehicleResult['vehicle']?->id,
            'raw_payload' => $meta,
        ]);

        return [
            'client_id' => $clientResult['client']->id,
            'lead_id' => $lead->id,
            'vehicle_id' => $vehicleResult['vehicle']?->id,
            'client_action' => $clientResult['action'],
            'lead_action' => 'created',
            'vehicle_action' => $vehicleResult['action'],
            'ack_status' => $ackStatus,
        ];
    }

    private function resolveOrCreateClient(int $companyId, array $payload): array
    {
        $phone = $this->normalizePhone($payload['contact_phone'] ?? $payload['whatsapp'] ?? $payload['phone'] ?? null);
        $email = $this->normalizeEmail($payload['email'] ?? null);
        $client = $this->findClient($companyId, $phone, $email);

        if ($client) {
            $updates = [];

            foreach ([
                'email' => $email,
                'phone' => $phone,
                'phone_norm' => $phone,
                'whatsapp' => $phone,
                'whatsapp_number' => $phone,
                'preferred_channel' => $payload['preferred_channel'] ?? ($phone ? 'whatsapp' : null),
                'source' => $payload['source'] ?? 'lead_upload',
            ] as $field => $value) {
                if ($value !== null && $value !== '' && Schema::hasColumn('clients', $field) && empty($client->{$field})) {
                    $updates[$field] = $value;
                }
            }

            if (! empty($payload['notes']) && Schema::hasColumn('clients', 'notes') && empty($client->notes)) {
                $updates['notes'] = $payload['notes'];
            }

            if (! empty($updates)) {
                $client->update($updates);
            }

            return ['client' => $client->fresh(), 'action' => ! empty($updates) ? 'updated_client_blank_fields' : 'reused_client'];
        }

        $data = [
            'company_id' => $companyId,
            'name' => trim((string) ($payload['name'] ?? 'Customer')) ?: 'Customer',
            'phone' => $phone,
            'email' => $email,
        ];

        foreach ([
            'phone_norm' => $phone,
            'email_norm' => $email,
            'whatsapp' => $phone,
            'whatsapp_number' => $phone,
            'source' => $payload['source'] ?? 'lead_upload',
            'preferred_channel' => $payload['preferred_channel'] ?? ($phone ? 'whatsapp' : 'phone'),
            'status' => 'active',
            'notes' => $payload['notes'] ?? null,
        ] as $field => $value) {
            if (Schema::hasColumn('clients', $field)) {
                $data[$field] = $value;
            }
        }

        return ['client' => Client::create($data), 'action' => 'created_client'];
    }

    private function createLeadWithoutEvents(int $companyId, Client $client, array $payload, LeadUploadRow $row, array $journeyMapping, bool $ackRequested, string $ackStatus): Lead
    {
        $phone = $this->normalizePhone($payload['contact_phone'] ?? $payload['whatsapp'] ?? $payload['phone'] ?? null);
        $email = $this->normalizeEmail($payload['email'] ?? null);
        $campaignType = LeadCampaignTypeJourneyMap::normalize($payload['campaign_type'] ?? null);
        $data = [
            'company_id' => $companyId,
            'client_id' => $client->id,
            'name' => trim((string) ($payload['name'] ?? $client->name)) ?: $client->name,
            'phone' => $phone,
            'email' => $email,
            'source' => $payload['source'] ?? 'lead_upload',
            'status' => Lead::STATUS_NEW,
            'preferred_channel' => $payload['preferred_channel'] ?? ($phone ? 'whatsapp' : 'phone'),
            'external_source' => 'lead_upload',
            'external_payload' => [
                'lead_upload_batch_id' => $row->batch_id,
                'lead_upload_row_id' => $row->id,
                'upload_batch_id' => $row->batch_id,
                'upload_row_id' => $row->id,
                'campaign_type' => $campaignType,
                'campaign_group_key' => $campaignType ? LeadCampaignTypeJourneyMap::lookupKey($campaignType) : null,
                'mapped_journey_key' => $journeyMapping['journey_key'] ?? null,
                'mapped_journey_label' => $journeyMapping['journey_label'] ?? null,
                'mapped_journey_trigger_key' => $journeyMapping['journey_trigger_key'] ?? null,
                'journey_mapping_active' => (bool) ($journeyMapping['is_active'] ?? false),
                'journey_preview_only' => (bool) ($journeyMapping['preview_only'] ?? true),
                'whatsapp_enabled' => (bool) ($journeyMapping['whatsapp_enabled'] ?? false),
                'whatsapp_template_name' => $journeyMapping['whatsapp_template_name'] ?? null,
                'ack_send_requested' => $ackRequested,
                'ack_send_status' => $ackStatus,
                'city' => $payload['city'] ?? null,
                'preferred_date' => $payload['preferred_date'] ?? $payload['follow_up_date'] ?? null,
                'preferred_time' => $payload['preferred_time'] ?? null,
                'raw' => $row->raw_payload,
            ],
            'external_received_at' => now(),
        ];

        foreach ([
            'phone_norm' => $phone,
            'email_norm' => $email,
            'notes' => $payload['notes'] ?? null,
            'service_category' => $payload['service_category'] ?? null,
            'service_type' => $payload['service_type'] ?? ($payload['service'] ?? null),
            'vehicle_make' => $payload['vehicle_make'] ?? null,
            'vehicle_model' => $payload['vehicle_model'] ?? null,
            'vehicle_year' => $payload['vehicle_year'] ?? null,
            'plate_number' => $payload['plate_number'] ?? null,
            'lead_temperature' => $payload['lead_temperature'] ?? null,
            'lead_priority' => $payload['lead_priority'] ?? null,
            'customer_type' => $payload['customer_type'] ?? null,
            'follow_up_required' => $this->truthy($payload['follow_up_required'] ?? null),
            'follow_up_date' => $payload['preferred_date'] ?? $payload['follow_up_date'] ?? null,
            'campaign_name' => $payload['campaign_name'] ?? ($payload['campaign'] ?? null),
            'campaign_type' => $campaignType,
            'assigned_to' => $this->resolveAssignedUserId($companyId, $payload['assigned_to'] ?? null),
            'is_active' => 1,
        ] as $field => $value) {
            if (Schema::hasColumn('leads', $field) && $value !== null && $value !== '') {
                $data[$field] = $value;
            }
        }

        return Lead::withoutEvents(fn () => Lead::create($data));
    }

    private function classifyVehicle(int $companyId, ?Client $client, array $payload): array
    {
        $makeName = trim((string) ($payload['vehicle_make'] ?? ''));
        $modelName = trim((string) ($payload['vehicle_model'] ?? ''));
        $year = trim((string) ($payload['vehicle_year'] ?? ''));
        $plate = trim((string) ($payload['plate_number'] ?? ''));

        if ($plate === '' && ($makeName === '' || $modelName === '')) {
            return ['action' => 'vehicle_optional_missing', 'vehicle' => null];
        }

        if (! $client) {
            return ['action' => 'create_vehicle', 'vehicle' => null];
        }

        $vehicle = $this->findVehicle($companyId, $client->id, $makeName, $modelName, $year, $plate);

        return ['action' => $vehicle ? 'reuse_vehicle' : 'create_vehicle', 'vehicle' => $vehicle];
    }

    private function resolveOrCreateVehicle(int $companyId, Client $client, array $payload): array
    {
        $makeName = trim((string) ($payload['vehicle_make'] ?? ''));
        $modelName = trim((string) ($payload['vehicle_model'] ?? ''));
        $year = trim((string) ($payload['vehicle_year'] ?? ''));
        $plate = trim((string) ($payload['plate_number'] ?? ''));

        if ($plate === '' && ($makeName === '' || $modelName === '')) {
            return ['action' => 'skipped_missing_data', 'vehicle' => null];
        }

        $vehicle = $this->findVehicle($companyId, $client->id, $makeName, $modelName, $year, $plate);

        if ($vehicle) {
            return ['action' => 'reused_vehicle', 'vehicle' => $vehicle];
        }

        $makeId = null;
        $modelId = null;

        if ($makeName !== '') {
            $make = VehicleMake::firstOrCreate(['name' => $makeName]);
            $makeId = $make->id;
        }

        if ($modelName !== '') {
            $model = VehicleModel::firstOrCreate([
                'make_id' => $makeId,
                'name' => $modelName,
            ]);
            $modelId = $model->id;
        }

        $data = [
            'company_id' => $companyId,
            'client_id' => $client->id,
            'make_id' => $makeId,
            'model_id' => $modelId,
            'plate_number' => $plate ?: null,
            'year' => $year ?: null,
        ];

        $vehicle = Vehicle::create(array_intersect_key($data, array_flip(Schema::getColumnListing('vehicles'))));

        return ['action' => 'created_vehicle', 'vehicle' => $vehicle];
    }

    private function findClient(int $companyId, ?string $phone, ?string $email): ?Client
    {
        if (! $phone && ! $email) {
            return null;
        }

        return Client::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($phone, $email) {
                if ($phone) {
                    $query->orWhere('phone', $phone);
                    foreach (['phone_norm', 'whatsapp', 'whatsapp_number'] as $field) {
                        if (Schema::hasColumn('clients', $field)) {
                            $query->orWhere($field, $phone);
                        }
                    }
                }

                if ($email) {
                    $query->orWhere('email', $email);
                    if (Schema::hasColumn('clients', 'email_norm')) {
                        $query->orWhere('email_norm', $email);
                    }
                }
            })
            ->first();
    }

    private function findRecentLead(int $companyId, ?string $phone, ?string $email): ?Lead
    {
        if (! $phone && ! $email) {
            return null;
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->where(function ($query) use ($phone, $email) {
                if ($phone) {
                    $query->orWhere('phone', $phone);
                    if (Schema::hasColumn('leads', 'phone_norm')) {
                        $query->orWhere('phone_norm', $phone);
                    }
                }

                if ($email) {
                    $query->orWhere('email', $email);
                    if (Schema::hasColumn('leads', 'email_norm')) {
                        $query->orWhere('email_norm', $email);
                    }
                }
            })
            ->latest()
            ->first();
    }

    private function findVehicle(int $companyId, int $clientId, string $makeName, string $modelName, string $year, string $plate): ?Vehicle
    {
        $query = Vehicle::query()
            ->where('company_id', $companyId)
            ->where('client_id', $clientId);

        if ($plate !== '' && Schema::hasColumn('vehicles', 'plate_number')) {
            return (clone $query)->where('plate_number', $plate)->first();
        }

        $makeId = $makeName !== '' ? VehicleMake::whereRaw('LOWER(name) = ?', [strtolower($makeName)])->value('id') : null;
        $modelId = $modelName !== '' ? VehicleModel::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($modelName)])
            ->when($makeId, fn ($query) => $query->where('make_id', $makeId))
            ->value('id') : null;

        if (! $makeId && ! $modelId) {
            return null;
        }

        $query->when($makeId, fn ($q) => $q->where('make_id', $makeId))
            ->when($modelId, fn ($q) => $q->where('model_id', $modelId));

        if ($year !== '' && Schema::hasColumn('vehicles', 'year')) {
            $query->where('year', $year);
        }

        return $query->first();
    }

    private function resolveCampaignMapping(LeadUploadBatch $batch, ?string $campaignType): array
    {
        $campaignType = LeadCampaignTypeJourneyMap::normalize($campaignType);
        $key = $campaignType ? LeadCampaignTypeJourneyMap::lookupKey($campaignType) : null;
        $meta = $batch->meta ?? [];
        $overrides = $meta['campaign_group_mappings'] ?? [];

        if ($key && isset($overrides[$key]) && is_array($overrides[$key])) {
            return $this->mappingFromArray($overrides[$key]);
        }

        return LeadCampaignTypeJourneyMap::resolve((int) $batch->company_id, $campaignType);
    }

    private function mappingFromArray(array $mapping): array
    {
        $journeyKey = $this->nullableString($mapping['journey_key'] ?? null);
        $isActive = (bool) ($mapping['is_active'] ?? true);
        $previewOnly = (bool) ($mapping['preview_only'] ?? true);
        $whatsappEnabled = (bool) ($mapping['whatsapp_enabled'] ?? false);
        $template = $this->nullableString($mapping['whatsapp_template_name'] ?? null);

        return [
            'campaign_type' => $mapping['campaign_type'] ?? null,
            'journey_key' => $journeyKey,
            'journey_label' => $this->nullableString($mapping['journey_label'] ?? null),
            'journey_trigger_key' => $this->nullableString($mapping['journey_trigger_key'] ?? null),
            'is_active' => $isActive,
            'preview_only' => $previewOnly,
            'whatsapp_enabled' => $whatsappEnabled,
            'whatsapp_template_name' => $template,
            'followup_template_name' => $this->nullableString($mapping['followup_template_name'] ?? null),
            'mapping_status' => ! $journeyKey ? 'Missing' : (! $isActive ? 'Inactive' : ($previewOnly ? 'Preview Only' : 'Mapped')),
            'whatsapp_status' => ! $whatsappEnabled ? 'Disabled' : ($template ? ($previewOnly ? 'Preview Only' : 'Enabled') : 'Template Missing'),
        ];
    }

    private function ackEligibilityStatus(LeadUploadRow $row, array $payload, array $journeyMapping): string
    {
        if ($row->validation_status === 'invalid') {
            return 'not_eligible';
        }

        if ($row->duplicate_lead_status === 'recent_duplicate') {
            return 'skipped_duplicate';
        }

        if (! $this->normalizePhone($payload['contact_phone'] ?? $payload['whatsapp'] ?? $payload['phone'] ?? null)) {
            return 'not_eligible';
        }

        if (empty($payload['campaign_type']) || empty($journeyMapping['journey_key'])) {
            return 'not_eligible';
        }

        if (! (bool) ($journeyMapping['is_active'] ?? false)) {
            return 'skipped_mapping_inactive';
        }

        if ((bool) ($journeyMapping['preview_only'] ?? true)) {
            return 'skipped_preview_only';
        }

        if (! (bool) ($journeyMapping['whatsapp_enabled'] ?? false)) {
            return 'not_eligible';
        }

        if (empty($journeyMapping['whatsapp_template_name'])) {
            return 'skipped_missing_template';
        }

        return 'queued';
    }

    private function queueInstantAck(LeadUploadBatch $batch, LeadUploadRow $row, Lead $lead, array $payload, array $journeyMapping): string
    {
        try {
            SendWhatsAppTemplateJob::dispatch(
                $this->normalizePhone($payload['contact_phone'] ?? $payload['whatsapp'] ?? $payload['phone'] ?? null),
                (string) $journeyMapping['whatsapp_template_name'],
                $this->ackTemplateParams($payload),
                [],
                [
                    'company_id' => (int) $batch->company_id,
                    'lead_id' => $lead->id,
                    'source' => 'lead_import_preview',
                    'upload_batch_id' => $batch->id,
                    'upload_row_id' => $row->id,
                    'campaign_type' => $payload['campaign_type'] ?? null,
                    'journey_key' => $journeyMapping['journey_key'] ?? null,
                ]
            );

            return 'queued';
        } catch (\Throwable) {
            return 'failed';
        }
    }

    private function ackTemplateParams(array $payload): array
    {
        $vehicle = trim(implode(' ', array_filter([
            $payload['vehicle_year'] ?? null,
            $payload['vehicle_make'] ?? null,
            $payload['vehicle_model'] ?? null,
        ])));

        return [
            trim((string) ($payload['name'] ?? 'there')) ?: 'there',
            trim((string) ($payload['service_type'] ?? $payload['service'] ?? 'your enquiry')) ?: 'your enquiry',
            $vehicle ?: 'your vehicle',
        ];
    }

    private function refreshBatchAfterApply(LeadUploadBatch $batch, array $summary, ?int $userId): void
    {
        $rowCounts = LeadUploadRow::query()
            ->where('batch_id', $batch->id)
            ->select('review_status', DB::raw('count(*) as rows_count'))
            ->groupBy('review_status')
            ->pluck('rows_count', 'review_status');

        $pendingApproved = LeadUploadRow::query()
            ->where('batch_id', $batch->id)
            ->where('review_status', 'approved')
            ->where('validation_status', '!=', 'invalid')
            ->count();

        $meta = $batch->meta ?? [];
        $meta['phase_9f_apply'] = [
            'mode' => $summary['mode'],
            'ack_mode' => $summary['ack_mode'] ?? 'import_only',
            'applied_by' => $userId,
            'applied_at' => now()->toIso8601String(),
            'rows_applied' => $summary['rows_applied'],
            'clients_to_create' => $summary['clients_to_create'],
            'clients_to_reuse' => $summary['clients_to_reuse'],
            'leads_to_create' => $summary['leads_to_create'],
            'duplicate_leads_blocked' => $summary['duplicate_leads_blocked'],
            'vehicles_to_create' => $summary['vehicles_to_create'],
            'vehicles_to_reuse' => $summary['vehicles_to_reuse'],
            'vehicles_missing' => $summary['vehicles_missing'],
            'errors' => $summary['errors'],
            'ack_queued' => $summary['ack_queued'],
            'ack_failed' => $summary['ack_failed'],
            'ack_not_eligible' => $summary['ack_not_eligible'],
            'ack_skipped_duplicate' => $summary['ack_skipped_duplicate'],
            'ack_skipped_missing_template' => $summary['ack_skipped_missing_template'],
            'ack_skipped_preview_only' => $summary['ack_skipped_preview_only'],
            'ack_skipped_mapping_inactive' => $summary['ack_skipped_mapping_inactive'],
        ];
        $meta['ack_send_groups'] = $this->ackSendGroups($batch);

        $status = $batch->status;
        if ($pendingApproved === 0 && (int) ($rowCounts['applied'] ?? 0) > 0) {
            $status = 'applied';
        }

        $batch->update([
            'status' => $status,
            'meta' => $meta,
        ]);
    }

    private function emptySummary(string $mode): array
    {
        return [
            'mode' => $mode,
            'eligible_rows' => 0,
            'rows_applied' => 0,
            'rows_skipped' => 0,
            'ready_to_apply' => 0,
            'blocked_duplicate_recent_lead' => 0,
            'blocked_missing_required_data' => 0,
            'skipped_not_approved' => 0,
            'skipped_invalid' => 0,
            'skipped_already_applied' => 0,
            'clients_to_create' => 0,
            'clients_to_reuse' => 0,
            'leads_to_create' => 0,
            'duplicate_leads_blocked' => 0,
            'vehicles_to_create' => 0,
            'vehicles_to_reuse' => 0,
            'vehicles_missing' => 0,
            'errors' => 0,
            'ack_queued' => 0,
            'ack_sent' => 0,
            'ack_failed' => 0,
            'ack_not_eligible' => 0,
            'ack_skipped_duplicate' => 0,
            'ack_skipped_missing_template' => 0,
            'ack_skipped_preview_only' => 0,
            'ack_skipped_mapping_inactive' => 0,
            'ack_not_requested' => 0,
        ];
    }

    private function ackSendGroups(LeadUploadBatch $batch): array
    {
        $groups = [];

        LeadUploadRow::query()
            ->where('company_id', $batch->company_id)
            ->where('batch_id', $batch->id)
            ->get()
            ->each(function (LeadUploadRow $row) use (&$groups) {
                $payload = array_merge($row->raw_payload ?? [], $row->normalized_payload ?? []);
                $campaignType = LeadCampaignTypeJourneyMap::normalize($payload['campaign_type'] ?? null);

                if (! $campaignType) {
                    return;
                }

                $key = LeadCampaignTypeJourneyMap::lookupKey($campaignType);
                $ackStatus = $payload['_phase_9f_apply']['ack_status'] ?? 'not_requested';

                $groups[$key] ??= [
                    'campaign_type' => $campaignType,
                    'send_status' => 'Not requested',
                    'queued' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                ];

                if ($ackStatus === 'queued') {
                    $groups[$key]['queued']++;
                    $groups[$key]['send_status'] = 'Queued';
                } elseif ($ackStatus === 'failed') {
                    $groups[$key]['failed']++;
                    $groups[$key]['send_status'] = 'Failed';
                } elseif ($ackStatus !== 'not_requested') {
                    $groups[$key]['skipped']++;

                    if ($groups[$key]['send_status'] === 'Not requested') {
                        $groups[$key]['send_status'] = 'Skipped';
                    }
                }
            });

        return $groups;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveAssignedUserId(int $companyId, mixed $assignedTo): ?int
    {
        if (! $assignedTo) {
            return null;
        }

        if (is_numeric($assignedTo)) {
            return User::query()->where('company_id', $companyId)->where('id', (int) $assignedTo)->value('id');
        }

        $assignedTo = trim((string) $assignedTo);

        return User::query()
            ->where('company_id', $companyId)
            ->where(fn ($query) => $query->where('email', $assignedTo)->orWhere('name', $assignedTo))
            ->value('id');
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', trim((string) $phone));

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone ?: null;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if (! $email) {
            return null;
        }

        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
