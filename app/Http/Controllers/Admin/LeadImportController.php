<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\LeadUploadBatch;
use App\Models\Client\LeadUploadRow;
use App\Models\LeadCampaignJourneyMapping;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Services\Leads\LeadUploadApplyService;
use App\Services\Leads\LeadCampaignTypeJourneyMap;
use App\Services\Leads\LeadFactory;
use App\Services\Leads\LeadUploadPreviewService;
use App\Services\Meta\MetaLeadService;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LeadImportController extends Controller
{
    public function __construct(
        private MetaLeadService $meta,
        private LeadFactory $factory
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CSV Import Form
    |--------------------------------------------------------------------------
    */
    public function showCsvForm(Request $request)
    {
        return view('admin.leads.import.index', [
            'campaignTypes' => LeadCampaignTypeJourneyMap::labels(),
        ]);
    }

    public function showPreviewForm(Request $request)
    {
        return view('admin.leads.import.preview', [
            'preview' => null,
            'campaignTypes' => LeadCampaignTypeJourneyMap::labels(),
        ]);
    }

    public function previewUpload(Request $request, LeadUploadPreviewService $previewService)
    {
        $request->validate([
            'lead_file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:5120'],
            'campaign_type' => ['required', 'string', 'max:100'],
        ]);

        $companyId = (int) $request->user()->company_id;
        $defaultCampaignType = LeadCampaignTypeJourneyMap::normalize($request->input('campaign_type'));

        abort_if(! $companyId, 403);

        if (! $defaultCampaignType) {
            return back()
                ->withInput()
                ->with('error', 'Please choose a valid campaign type before previewing leads.');
        }

        try {
            $preview = $previewService->preview(
                $request->file('lead_file'),
                $companyId,
                LeadUploadPreviewService::DEFAULT_LIMIT,
                $defaultCampaignType
            );
        } catch (\Throwable $e) {
            Log::warning('[LeadUploadPreview] Preview failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Lead upload preview could not be generated: ' . $e->getMessage());
        }

        $file = $request->file('lead_file');
        $storedPath = $file->storeAs(
            'lead-upload-previews',
            now()->format('Ymd_His') . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->getClientOriginalName()),
            'local'
        );

        $batch = DB::transaction(function () use ($request, $preview, $companyId, $storedPath, $file) {
            $summary = $preview['summary'];

            $batch = LeadUploadBatch::create([
                'company_id' => $companyId,
                'uploaded_by' => $request->user()?->id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'mode' => 'preview',
                'status' => 'parsed',
                'total_rows' => $summary['rows_shown'] ?? 0,
                'valid_rows' => $summary['valid'] ?? 0,
                'warning_rows' => $summary['warnings'] ?? 0,
                'invalid_rows' => $summary['invalid'] ?? 0,
                'duplicate_client_rows' => $summary['duplicate_clients'] ?? 0,
                'duplicate_lead_rows' => $summary['duplicate_leads'] ?? 0,
                'ready_ack_rows' => $summary['ready_for_ack'] ?? 0,
                'blocked_ack_rows' => $summary['blocked_or_not_ready'] ?? 0,
                'meta' => [
                    'rows_read' => $summary['rows_read'] ?? 0,
                    'rows_shown' => $summary['rows_shown'] ?? 0,
                    'limit' => $summary['limit'] ?? LeadUploadPreviewService::DEFAULT_LIMIT,
                    'truncated' => (bool) ($summary['truncated'] ?? false),
                    'event_key' => $preview['event_key'] ?? null,
                    'fallback_event_key' => $preview['fallback_event_key'] ?? null,
                    'phase' => '9C',
                    'preview_only' => true,
                ],
            ]);

            foreach ($preview['rows'] as $row) {
                $ack = $row['ack_readiness'] ?? [];

                LeadUploadRow::create([
                    'batch_id' => $batch->id,
                    'company_id' => $companyId,
                    'row_number' => $row['row_number'],
                    'raw_payload' => $row['raw'] ?? [],
                    'normalized_payload' => [
                        'name' => $row['name'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'whatsapp' => $row['whatsapp'] ?? null,
                        'contact_phone' => $row['contact_phone'] ?? null,
                        'email' => $row['email'] ?? null,
                        'source' => $row['source'] ?? null,
                        'campaign_type' => $row['campaign_type'] ?? null,
                        'journey_key' => $row['journey_key'] ?? null,
                        'journey_label' => $row['journey_label'] ?? null,
                        'journey_trigger_key' => $row['journey_trigger_key'] ?? null,
                        'mapping_status' => $row['mapping_status'] ?? null,
                        'whatsapp_status' => $row['whatsapp_status'] ?? null,
                        'journey_mapping' => $row['journey_mapping'] ?? null,
                        'service' => $row['service'] ?? null,
                        'campaign' => $row['campaign'] ?? null,
                        'vehicle' => $row['vehicle'] ?? null,
                        'city' => $row['city'] ?? null,
                        'preferred_date' => $row['preferred_date'] ?? null,
                        'preferred_time' => $row['preferred_time'] ?? null,
                    ],
                    'client_match_id' => $row['client_match']['id'] ?? null,
                    'lead_match_id' => $row['lead_match']['id'] ?? null,
                    'vehicle_match_id' => null,
                    'duplicate_client_status' => ! empty($row['client_match']) ? 'matched' : 'none',
                    'duplicate_lead_status' => ! empty($row['lead_match']) ? 'recent_duplicate' : 'none',
                    'validation_status' => $row['status'] ?? 'valid',
                    'ack_readiness' => $ack['status'] ?? null,
                    'suggested_ack_event_key' => $ack['event_key'] ?? ($preview['event_key'] ?? null),
                    'suggested_ack_template_key' => $ack['template'] ?? null,
                    'suggested_ack_message' => $row['suggested_message'] ?? null,
                    'errors' => $row['errors'] ?? [],
                    'warnings' => $row['warnings'] ?? [],
                    'review_status' => 'pending_review',
                ]);
            }

            return $batch;
        });

        return redirect()
            ->route('admin.leads.import.preview.batches.show', $batch)
            ->with('success', 'Lead upload preview parsed and saved for review. No CRM records or WhatsApp messages were created.');
    }

    public function previewBatches(Request $request)
    {
        $companyId = (int) $request->user()->company_id;

        abort_if(! $companyId, 403);

        $batches = LeadUploadBatch::with([
                'uploadedBy',
                'rows:id,batch_id,row_number,normalized_payload',
            ])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(20);

        return view('admin.leads.import.batches', compact('batches'));
    }

    public function showPreviewBatch(Request $request, int $batch)
    {
        $leadUploadBatch = $this->findLeadUploadBatchForCurrentCompany($request, $batch);
        $leadUploadBatch->load(['uploadedBy', 'rows.clientMatch', 'rows.leadMatch']);

        return view('admin.leads.import.preview', [
            'batch' => $leadUploadBatch,
            'preview' => $this->previewPayloadFromBatch($leadUploadBatch),
            'campaignTypes' => LeadCampaignTypeJourneyMap::labels(),
        ]);
    }

    public function reviewPreviewRow(Request $request, int $batch, int $row)
    {
        $leadUploadBatch = $this->findLeadUploadBatchForCurrentCompany($request, $batch);
        $leadUploadRow = $this->findLeadUploadRowForBatch($leadUploadBatch, $row);

        $data = $request->validate([
            'review_status' => ['required', 'in:approved,rejected,skipped,pending_review'],
        ]);

        if ($leadUploadRow->review_status === 'applied') {
            return back()->with('error', 'Applied rows are locked and cannot be changed.');
        }

        if (
            $data['review_status'] === 'approved'
            && $leadUploadRow->validation_status === 'invalid'
        ) {
            return back()->with('error', 'Invalid rows cannot be approved. Reject, skip, or reset the row instead.');
        }

        $leadUploadRow->update([
            'review_status' => $data['review_status'],
        ]);

        $this->refreshLeadUploadBatchReviewStatus($leadUploadBatch);

        return back()->with('success', 'Lead upload row review status updated.');
    }

    public function bulkReviewPreviewRows(Request $request, int $batch)
    {
        $leadUploadBatch = $this->findLeadUploadBatchForCurrentCompany($request, $batch);

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,skip,reset'],
            'row_ids' => ['required', 'array', 'min:1'],
            'row_ids.*' => ['integer'],
        ]);

        $targetStatus = match ($data['action']) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'skip' => 'skipped',
            default => 'pending_review',
        };

        $rows = LeadUploadRow::query()
            ->where('company_id', $leadUploadBatch->company_id)
            ->where('batch_id', $leadUploadBatch->id)
            ->whereIn('id', array_map('intval', $data['row_ids']))
            ->get();

        $updated = 0;
        $skippedInvalid = 0;
        $skippedApplied = 0;

        foreach ($rows as $leadUploadRow) {
            if ($leadUploadRow->review_status === 'applied') {
                $skippedApplied++;
                continue;
            }

            if (
                $data['action'] === 'approve'
                && $leadUploadRow->validation_status === 'invalid'
            ) {
                $skippedInvalid++;
                continue;
            }

            $leadUploadRow->update([
                'review_status' => $targetStatus,
            ]);

            $updated++;
        }

        $this->refreshLeadUploadBatchReviewStatus($leadUploadBatch);

        $message = "Bulk review updated {$updated} row(s).";

        if ($skippedInvalid > 0) {
            $message .= " Skipped {$skippedInvalid} invalid row(s).";
        }

        if ($skippedApplied > 0) {
            $message .= " Skipped {$skippedApplied} applied row(s).";
        }

        return back()->with($updated > 0 ? 'success' : 'warning', $message);
    }

    public function applyPreviewBatch(Request $request, int $batch, LeadUploadApplyService $applyService)
    {
        $leadUploadBatch = $this->findLeadUploadBatchForCurrentCompany($request, $batch);

        $data = $request->validate([
            'mode' => ['nullable', 'in:dry_run,apply'],
            'ack_mode' => ['nullable', 'in:import_only,send_ack'],
        ]);

        $mode = $data['mode'] ?? 'dry_run';
        $ackMode = $data['ack_mode'] ?? 'import_only';
        $result = $mode === 'apply'
            ? $applyService->apply($leadUploadBatch, $request->user()?->id, $ackMode)
            : $applyService->dryRun($leadUploadBatch, $request->user()?->id, $ackMode);

        $batchLabel = "#{$leadUploadBatch->id} {$leadUploadBatch->original_filename}";
        $message = match (true) {
            $mode === 'apply' && $ackMode === 'send_ack' => "Import completed for batch {$batchLabel}: {$result['rows_applied']} lead(s) created, {$result['clients_to_create']} client(s) created, {$result['vehicles_to_create']} vehicle(s) created, {$result['duplicate_leads_blocked']} duplicate lead(s) skipped, and {$result['ack_queued']} WhatsApp ACK message(s) queued. You are still on the preview batch detail page.",
            $mode === 'apply' => "Import completed for batch {$batchLabel}: {$result['rows_applied']} lead(s) created, {$result['clients_to_create']} client(s) created, {$result['vehicles_to_create']} vehicle(s) created, and {$result['duplicate_leads_blocked']} duplicate lead(s) skipped. No WhatsApp messages were sent. You are still on the preview batch detail page.",
            default => "Dry-run completed: {$result['ready_to_apply']} row(s) ready to apply. No records were created.",
        };

        return redirect()
            ->route('admin.leads.import.preview.batches.show', $leadUploadBatch)
            ->with($mode === 'apply' ? 'success' : 'info', $message)
            ->with('apply_readiness', $result);
    }

    public function savePreviewMappings(Request $request, int $batch)
    {
        $leadUploadBatch = $this->findLeadUploadBatchForCurrentCompany($request, $batch);

        $data = $request->validate([
            'campaign_groups' => ['nullable', 'array'],
            'campaign_groups.*.campaign_type' => ['required', 'string', 'max:100'],
            'campaign_groups.*.journey_label' => ['nullable', 'string', 'max:191'],
            'campaign_groups.*.journey_key' => ['nullable', 'string', 'max:191'],
            'campaign_groups.*.journey_trigger_key' => ['nullable', 'string', 'max:191'],
            'campaign_groups.*.whatsapp_template_name' => ['nullable', 'string', 'max:191'],
            'campaign_groups.*.followup_template_name' => ['nullable', 'string', 'max:191'],
            'campaign_groups.*.preview_only' => ['nullable', 'boolean'],
            'campaign_groups.*.whatsapp_enabled' => ['nullable', 'boolean'],
            'campaign_groups.*.save_as_default' => ['nullable', 'boolean'],
        ]);

        $meta = $leadUploadBatch->meta ?? [];
        $mappings = $meta['campaign_group_mappings'] ?? [];
        $savedDefaults = 0;

        foreach ($data['campaign_groups'] ?? [] as $group) {
            $campaignType = LeadCampaignTypeJourneyMap::normalize($group['campaign_type'] ?? null);

            if (! $campaignType) {
                continue;
            }

            $key = LeadCampaignTypeJourneyMap::lookupKey($campaignType);
            $mapping = [
                'campaign_type' => $campaignType,
                'journey_label' => $this->nullableString($group['journey_label'] ?? null),
                'journey_key' => $this->nullableString($group['journey_key'] ?? null),
                'journey_trigger_key' => $this->nullableString($group['journey_trigger_key'] ?? null),
                'whatsapp_template_name' => $this->nullableString($group['whatsapp_template_name'] ?? null),
                'followup_template_name' => $this->nullableString($group['followup_template_name'] ?? null),
                'is_active' => true,
                'preview_only' => $request->boolean("campaign_groups.{$key}.preview_only"),
                'whatsapp_enabled' => $request->boolean("campaign_groups.{$key}.whatsapp_enabled"),
                'updated_by' => $request->user()?->id,
                'updated_at' => now()->toIso8601String(),
            ];

            $mappings[$key] = $mapping;

            if ($request->boolean("campaign_groups.{$key}.save_as_default")) {
                LeadCampaignJourneyMapping::query()->updateOrCreate(
                    [
                        'company_id' => $leadUploadBatch->company_id,
                        'campaign_type' => $campaignType,
                    ],
                    array_merge($mapping, [
                        'company_id' => $leadUploadBatch->company_id,
                        'garage_id' => null,
                        'created_by' => $request->user()?->id,
                        'updated_by' => $request->user()?->id,
                    ])
                );

                $savedDefaults++;
            }
        }

        $meta['campaign_group_mappings'] = $mappings;
        $meta['campaign_group_mappings_updated_at'] = now()->toIso8601String();
        $meta['campaign_group_mappings_updated_by'] = $request->user()?->id;

        $leadUploadBatch->update(['meta' => $meta]);

        $message = 'Campaign group mappings saved for this upload.';

        if ($savedDefaults > 0) {
            $message .= " {$savedDefaults} default mapping(s) updated.";
        }

        return back()->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | CSV Import Handler
    |--------------------------------------------------------------------------
    */
    public function importFromCsv(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'campaign_type' => ['required', 'string', 'max:100'],
        ]);

        $companyId = (int) $request->user()->company_id;
        $defaultCampaignType = LeadCampaignTypeJourneyMap::normalize($request->input('campaign_type'));

        if (! $defaultCampaignType) {
            return back()
                ->withInput()
                ->with('error', 'Please choose a valid campaign type before uploading leads.');
        }

        abort_if(! $companyId, 403);

        $path = $request->file('csv_file')->getRealPath();

        if (! $path || ! file_exists($path)) {
            return back()->with('error', 'CSV file could not be read.');
        }

        $handle = fopen($path, 'r');

        if (! $handle) {
            return back()->with('error', 'Unable to open CSV file.');
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return back()->with('error', 'CSV file is empty or missing headers.');
        }

        $header = array_map(fn ($h) => $this->cleanHeader($h), $header);

        $requiredHeaders = [
            'customer_name' => ['customer_name', 'name'],
            'phone' => ['phone'],
            'lead_source' => ['lead_source', 'source'],
        ];

        $missingHeaders = [];

        foreach ($requiredHeaders as $label => $aliases) {
            if (empty(array_intersect($aliases, $header))) {
                $missingHeaders[] = $label;
            }
        }

        if (! empty($missingHeaders)) {
            fclose($handle);

            return back()->with(
                'error',
                'Missing required columns: ' . implode(', ', $missingHeaders)
            );
        }

        $inserted = 0;
        $dupes = 0;
        $updated = 0;
        $skipped = 0;
        $clientsCreated = 0;
        $vehiclesCreated = 0;
        $segmentsApplied = 0;
        $errors = [];

        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));

            if (! $data) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: Invalid row format.";
                continue;
            }

            $data = $this->cleanRow($data);

            try {
                $data = $this->canonicalizeLeadImportRow($data, $defaultCampaignType);

                $name = $data['name'] ?? null;
                $phone = $this->normalizePhone($data['phone'] ?? null);
                $email = $this->normalizeEmail($data['email'] ?? null);
                $campaignType = LeadCampaignTypeJourneyMap::normalize($data['campaign_type'] ?? null);
                $journeyMapping = LeadCampaignTypeJourneyMap::resolve($companyId, $campaignType);

                if (! $name || ! $phone || ! $campaignType || empty($data['source'])) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Customer name, phone, lead source, and campaign type are required.";
                    continue;
                }

                $leadSource = $this->normalizeImportSource($data['source'] ?? 'lead_import');

                /*
                |--------------------------------------------------------------------------
                | Create/update Client first
                |--------------------------------------------------------------------------
                |
                | Import should always create/reuse client, then attach the lead.
                | New lead imports are deliberately created without LeadCreated
                | side effects below. Journey/ACK routing will use campaign_type
                | later when explicitly enabled.
                */

                [$client, $clientWasCreated] = $this->resolveOrCreateClient(
                    companyId: $companyId,
                    name: $name,
                    phone: $phone,
                    email: $email,
                    source: $leadSource,
                    extra: $data
                );

                if ($clientWasCreated) {
                    $clientsCreated++;
                }

                $leadPayload = [
                    'company_id'        => $companyId,
                    'client_id'         => $client?->id,
                    'name'              => $name,
                    'phone'             => $phone,
                    'email'             => $email,
                    'source'            => $leadSource,
                    'external_source'   => 'import',
                    'status'            => Lead::STATUS_NEW,
                    'notes'             => $data['notes'] ?? null,
                    'preferred_channel' => $data['preferred_channel'] ?? 'whatsapp',
                    'window_days'       => 30,
                ];

                if (Schema::hasColumn('leads', 'phone_norm')) {
                    $leadPayload['phone_norm'] = $phone;
                }

                if (Schema::hasColumn('leads', 'email_norm')) {
                    $leadPayload['email_norm'] = $email;
                }

                if (Schema::hasColumn('leads', 'external_payload')) {
                    $leadPayload['external_payload'] = [
                        'source' => 'csv_import',
                        'row_number' => $rowNumber,
                        'campaign_type' => $campaignType,
                        'campaign_group_key' => $campaignType ? LeadCampaignTypeJourneyMap::lookupKey($campaignType) : null,
                        'mapped_journey_key' => $journeyMapping['journey_key'] ?? null,
                        'mapped_journey_label' => $journeyMapping['journey_label'] ?? null,
                        'mapped_journey_trigger_key' => $journeyMapping['journey_trigger_key'] ?? null,
                        'journey_mapping_active' => (bool) ($journeyMapping['is_active'] ?? false),
                        'journey_preview_only' => (bool) ($journeyMapping['preview_only'] ?? true),
                        'whatsapp_enabled' => (bool) ($journeyMapping['whatsapp_enabled'] ?? false),
                        'whatsapp_template_name' => $journeyMapping['whatsapp_template_name'] ?? null,
                        'ack_send_requested' => false,
                        'ack_send_status' => 'not_requested',
                        'city' => $data['city'] ?? null,
                        'preferred_date' => $data['preferred_date'] ?? null,
                        'preferred_time' => $data['preferred_time'] ?? null,
                        'raw' => $data,
                    ];
                }

                if (Schema::hasColumn('leads', 'external_received_at')) {
                    $leadPayload['external_received_at'] = now();
                }

                $extraLeadFields = [
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
                    'campaign_type',
                    'retention_tag',
                ];

                foreach ($extraLeadFields as $field) {
                    if (Schema::hasColumn('leads', $field) && array_key_exists($field, $data)) {
                        $leadPayload[$field] = $this->normalizeFieldValue($field, $data[$field]);
                    }
                }

                if (Schema::hasColumn('leads', 'is_active')) {
                    $leadPayload['is_active'] = true;
                }

                if (
                    Schema::hasColumn('leads', 'follow_up_required')
                    && ! $this->isExplicitFalse($data['follow_up_required'] ?? null)
                ) {
                    $leadPayload['follow_up_required'] = true;
                }

                if (
                    Schema::hasColumn('leads', 'assigned_to')
                    && ! empty($data['assigned_to'])
                ) {
                    $assignedUserId = $this->resolveAssignedUserId($companyId, $data['assigned_to']);

                    if ($assignedUserId) {
                        $leadPayload['assigned_to'] = $assignedUserId;
                    }
                }

                $result = Lead::withoutEvents(fn () => $this->factory->createOrDetectDuplicate($leadPayload));

                if ($result instanceof Lead) {
                    $lead = $result;
                    $inserted++;

                    $vehicleWasCreated = $this->createOrUpdateClientAndVehicle($companyId, $lead, $data, $client);

                    if ($vehicleWasCreated) {
                        $vehiclesCreated++;
                    }

                    if ($this->applyAudienceSegmentation($lead, $data)) {
                        $segmentsApplied++;
                    }
                } else {
                    $dupes++;

                    /*
                    |--------------------------------------------------------------------------
                    | Duplicate lead fallback
                    |--------------------------------------------------------------------------
                    |
                    | Even if lead factory marks this as duplicate, we still created/reused
                    | the client above. This keeps imported customer data usable.
                    */
                }
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();

                Log::warning('[LeadImport] CSV row skipped', [
                    'company_id' => $companyId,
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        fclose($handle);

        $message = "Recent lead import completed: +{$inserted} new, ~{$updated} updated, {$dupes} duplicates, {$skipped} skipped. "
            . "Clients created: {$clientsCreated}. Vehicles created: {$vehiclesCreated}. Segments applied: {$segmentsApplied}.";

        return back()
            ->with('success', $message)
            ->with('csv_errors', $errors);
    }

    /*
    |--------------------------------------------------------------------------
    | Meta Import Form
    |--------------------------------------------------------------------------
    */
    public function showMetaForm(Request $request)
    {
        $companyId = (int) $request->user()->company_id;
        $store = new SettingsStore($companyId);

        $prefill = [
            'meta_access_token' => (string) $store->get('meta.access_token', config('services.meta.access_token', '')),
            'meta_form_id'      => (string) $store->get('meta.form_id', config('services.meta.form_id', '')),
            'limit'             => 100,
        ];

        return view('admin.leads.import-meta', compact('prefill'));
    }

    /*
    |--------------------------------------------------------------------------
    | Meta Import Handler
    |--------------------------------------------------------------------------
    */
    public function importFromMeta(Request $request)
    {
        $companyId = (int) $request->user()->company_id;
        $store = new SettingsStore($companyId);

        $accessToken = trim(
            (string) $request->input('meta_access_token')
            ?: $store->get('meta.access_token')
            ?: config('services.meta.access_token')
        );

        $formIds = collect(
            array_filter(array_map('trim', explode(',',
                (string) $request->input('meta_form_id')
                ?: (string) $store->get('meta.form_ids')
                ?: config('services.meta.form_id')
            )))
        )->unique();

        if (! $accessToken || $formIds->isEmpty()) {
            return back()->with('error', 'Meta import: Access Token and Form ID required.');
        }

        $inserted = 0;
        $updated = 0;
        $dupes = 0;
        $clientsCreated = 0;
        $console = [];

        $windowDays = (int) $store->get('leads.dedupe_days', 30);

        foreach ($formIds as $formId) {
            $lock = Cache::lock("meta-import:{$companyId}:{$formId}", 60);

            if (! $lock->get()) {
                $console[] = "⚠ Import already running for form {$formId}";
                continue;
            }

            try {
                $ckKey = "meta.forms.{$formId}.last_created_time";
                $sinceIso = $store->get($ckKey);

                $rows = $this->meta->fetchLeadsSince(
                    $accessToken,
                    (string) $formId,
                    $sinceIso,
                    (int) $request->input('limit', 100)
                );

                $maxCreated = $sinceIso ? strtotime($sinceIso) : 0;

                foreach ($rows as $row) {
                    $createdTime = $row['created_time'] ?? null;

                    if ($createdTime && strtotime($createdTime) > $maxCreated) {
                        $maxCreated = strtotime($createdTime);
                    }

                    $payload = $row['raw'] ?? $row;

                    $name = $row['name'] ?? 'Meta Lead';
                    $phone = $this->normalizePhone($row['phone'] ?? null);
                    $email = $this->normalizeEmail($row['email'] ?? null);

                    [$client, $clientWasCreated] = $this->resolveOrCreateClient(
                        companyId: $companyId,
                        name: $name,
                        phone: $phone,
                        email: $email,
                        source: 'meta',
                        extra: $row
                    );

                    if ($clientWasCreated) {
                        $clientsCreated++;
                    }

                    if (! empty($row['external_id'])) {
                        $identity = [
                            'company_id'      => $companyId,
                            'external_source' => 'meta',
                            'external_id'     => (string) $row['external_id'],
                        ];

                        $values = [
                            'client_id'            => $client?->id,
                            'name'                 => $name,
                            'email'                => $email,
                            'phone'                => $phone,
                            'status'               => Lead::STATUS_NEW,
                            'source'               => 'meta',
                            'preferred_channel'    => 'whatsapp',
                            'external_form_id'     => (string) $formId,
                            'external_payload'     => $payload,
                            'external_received_at' => now(),
                        ];

                        if (Schema::hasColumn('leads', 'phone_norm')) {
                            $values['phone_norm'] = $phone;
                        }

                        if (Schema::hasColumn('leads', 'email_norm')) {
                            $values['email_norm'] = $email;
                        }

                        if ($createdTime && Schema::hasColumn('leads', 'created_at')) {
                            $values['created_at'] = $createdTime;
                        }

                        $lead = Lead::updateOrCreate($identity, $values);

                        $lead->wasRecentlyCreated ? $inserted++ : $updated++;

                        $this->applyAudienceSegmentation($lead, [
                            'source' => 'meta',
                            'service_category' => $row['service_category'] ?? null,
                            'campaign_name' => $row['campaign_name'] ?? null,
                        ]);

                        continue;
                    }

                    $result = $this->factory->createOrDetectDuplicate([
                        'company_id'           => $companyId,
                        'client_id'            => $client?->id,
                        'name'                 => $name,
                        'email'                => $email,
                        'phone'                => $phone,
                        'status'               => Lead::STATUS_NEW,
                        'source'               => 'meta',
                        'preferred_channel'    => 'whatsapp',
                        'external_source'      => 'meta',
                        'external_form_id'     => (string) $formId,
                        'external_payload'     => $payload,
                        'external_received_at' => now(),
                        'window_days'          => $windowDays,
                    ]);

                    if ($result instanceof Lead) {
                        $inserted++;
                        $this->applyAudienceSegmentation($result, ['source' => 'meta']);
                    } else {
                        $dupes++;
                    }
                }

                if ($maxCreated > 0) {
                    $store->set($ckKey, gmdate('c', $maxCreated));
                }

                $console[] = "✔ Form {$formId} processed";
            } catch (\Throwable $e) {
                $console[] = "❌ {$e->getMessage()}";

                Log::error('[LeadImport] Meta import failed', [
                    'company_id' => $companyId,
                    'form_id' => $formId,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                optional($lock)->release();
            }
        }

        $summary = "Meta import done: +{$inserted} new, ~{$updated} updated, ⚠{$dupes} duplicates, clients created: {$clientsCreated}";

        return back()
            ->with('success', $summary)
            ->with('meta_output', implode(PHP_EOL, $console));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function findLeadUploadBatchForCurrentCompany(Request $request, int $batch): LeadUploadBatch
    {
        $companyId = (int) $request->user()->company_id;

        abort_if(! $companyId, 403);

        return LeadUploadBatch::query()
            ->where('company_id', $companyId)
            ->where('id', $batch)
            ->firstOrFail();
    }

    private function findLeadUploadRowForBatch(LeadUploadBatch $batch, int $row): LeadUploadRow
    {
        return LeadUploadRow::query()
            ->where('company_id', $batch->company_id)
            ->where('batch_id', $batch->id)
            ->where('id', $row)
            ->firstOrFail();
    }

    private function refreshLeadUploadBatchReviewStatus(LeadUploadBatch $batch): void
    {
        $counts = LeadUploadRow::query()
            ->where('batch_id', $batch->id)
            ->select('review_status', DB::raw('count(*) as rows_count'))
            ->groupBy('review_status')
            ->pluck('rows_count', 'review_status');

        $total = (int) $counts->sum();
        $pending = (int) ($counts['pending_review'] ?? 0);
        $reviewed = (int) ($counts['approved'] ?? 0)
            + (int) ($counts['rejected'] ?? 0)
            + (int) ($counts['skipped'] ?? 0)
            + (int) ($counts['applied'] ?? 0);

        $status = 'parsed';

        if ($reviewed > 0 && $pending > 0) {
            $status = 'pending_review';
        }

        if ($total > 0 && $pending === 0 && $reviewed >= $total) {
            $status = 'reviewed';
        }

        $meta = $batch->meta ?? [];
        $meta['review_counts'] = [
            'pending_review' => $pending,
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'skipped' => (int) ($counts['skipped'] ?? 0),
            'applied' => (int) ($counts['applied'] ?? 0),
            'total' => $total,
        ];

        $batch->update([
            'status' => $status,
            'meta' => $meta,
        ]);
    }

    private function previewPayloadFromBatch(LeadUploadBatch $batch): array
    {
        $meta = $batch->meta ?? [];
        $reviewCounts = $batch->rows
            ->groupBy('review_status')
            ->map(fn ($rows) => $rows->count());
        $rows = $batch->rows->map(function (LeadUploadRow $row) use ($batch) {
            $normalized = $row->normalized_payload ?? [];
            $campaignType = $normalized['campaign_type'] ?? null;
            $journeyMapping = $this->resolveCampaignGroupMapping($batch, $campaignType);

            return [
                'row_number' => $row->row_number,
                'status' => $row->validation_status,
                'name' => $normalized['name'] ?? null,
                'phone' => $normalized['phone'] ?? null,
                'whatsapp' => $normalized['whatsapp'] ?? null,
                'contact_phone' => $normalized['contact_phone'] ?? null,
                'email' => $normalized['email'] ?? null,
                'source' => $normalized['source'] ?? null,
                'campaign_type' => $campaignType,
                'journey_key' => $journeyMapping['journey_key'] ?? null,
                'journey_label' => $journeyMapping['journey_label'] ?? null,
                'journey_trigger_key' => $journeyMapping['journey_trigger_key'] ?? null,
                'mapping_status' => $journeyMapping['mapping_status'] ?? 'Missing',
                'whatsapp_status' => $journeyMapping['whatsapp_status'] ?? 'Disabled',
                'journey_mapping' => $journeyMapping,
                'service' => $normalized['service'] ?? null,
                'campaign' => $normalized['campaign'] ?? null,
                'vehicle' => $normalized['vehicle'] ?? null,
                'city' => $normalized['city'] ?? null,
                'preferred_date' => $normalized['preferred_date'] ?? null,
                'preferred_time' => $normalized['preferred_time'] ?? null,
                'client_match' => $row->clientMatch ? [
                    'id' => $row->clientMatch->id,
                    'name' => $row->clientMatch->name,
                ] : null,
                'lead_match' => $row->leadMatch ? [
                    'id' => $row->leadMatch->id,
                    'name' => $row->leadMatch->name,
                    'status' => $row->leadMatch->status,
                    'source' => $row->leadMatch->source,
                    'created_at' => optional($row->leadMatch->created_at)->format('d M Y'),
                ] : null,
                'ack_readiness' => [
                    'status' => $row->ack_readiness,
                    'label' => $this->ackReadinessLabel($row->ack_readiness),
                    'reason' => $this->ackReadinessReason($row),
                    'event_key' => $row->suggested_ack_event_key,
                    'template' => $row->suggested_ack_template_key,
                ],
                'suggested_message' => $row->suggested_ack_message,
                'warnings' => $row->warnings ?? [],
                'errors' => $row->errors ?? [],
                'review_status' => $row->review_status,
                'row_id' => $row->id,
                'raw' => $row->raw_payload ?? [],
            ];
        })->all();

        return [
            'summary' => [
                'rows_read' => $meta['rows_read'] ?? $batch->total_rows,
                'rows_shown' => $batch->total_rows,
                'limit' => $meta['limit'] ?? LeadUploadPreviewService::DEFAULT_LIMIT,
                'truncated' => (bool) ($meta['truncated'] ?? false),
                'valid' => $batch->valid_rows,
                'warnings' => $batch->warning_rows,
                'invalid' => $batch->invalid_rows,
                'duplicate_clients' => $batch->duplicate_client_rows,
                'duplicate_leads' => $batch->duplicate_lead_rows,
                'ready_for_ack' => $batch->ready_ack_rows,
                'blocked_or_not_ready' => $batch->blocked_ack_rows,
                'review_pending' => (int) ($reviewCounts['pending_review'] ?? 0),
                'review_approved' => (int) ($reviewCounts['approved'] ?? 0),
                'review_rejected' => (int) ($reviewCounts['rejected'] ?? 0),
                'review_skipped' => (int) ($reviewCounts['skipped'] ?? 0),
                'review_applied' => (int) ($reviewCounts['applied'] ?? 0),
            ],
            'headers' => [],
            'rows' => $rows,
            'campaign_groups' => $this->campaignGroupsForPreview($rows, $batch),
            'notice' => 'Review only. No leads, clients, vehicles, WhatsApp messages, campaigns, or journeys have been created yet.',
            'event_key' => $meta['event_key'] ?? 'lead.upload.instant_ack',
            'fallback_event_key' => $meta['fallback_event_key'] ?? 'lead.created',
        ];
    }

    private function campaignGroupsForPreview(array $rows, LeadUploadBatch $batch): array
    {
        $groups = [];
        $meta = $batch->meta ?? [];
        $ackGroups = $meta['ack_send_groups'] ?? [];

        foreach ($rows as $row) {
            $campaignType = LeadCampaignTypeJourneyMap::normalize($row['campaign_type'] ?? null) ?: 'Missing Campaign Type';
            $key = LeadCampaignTypeJourneyMap::lookupKey($campaignType);

            if (! isset($groups[$key])) {
                $mapping = $this->resolveCampaignGroupMapping($batch, $campaignType);

                $groups[$key] = [
                    'key' => $key,
                    'campaign_type' => $campaignType,
                    'total' => 0,
                    'valid_rows' => 0,
                    'duplicate_rows' => 0,
                    'invalid_rows' => 0,
                    'journey_label' => $mapping['journey_label'] ?? null,
                    'journey_key' => $mapping['journey_key'] ?? null,
                    'journey_trigger_key' => $mapping['journey_trigger_key'] ?? null,
                    'whatsapp_template_name' => $mapping['whatsapp_template_name'] ?? null,
                    'followup_template_name' => $mapping['followup_template_name'] ?? null,
                    'preview_only' => (bool) ($mapping['preview_only'] ?? true),
                    'whatsapp_enabled' => (bool) ($mapping['whatsapp_enabled'] ?? false),
                    'mapping_status' => $this->campaignGroupMappingStatus($mapping),
                    'whatsapp_status' => $mapping['whatsapp_status'] ?? 'Disabled',
                    'send_status' => $ackGroups[$key]['send_status'] ?? 'Not requested',
                ];
            }

            $groups[$key]['total']++;

            if (($row['status'] ?? null) === 'valid') {
                $groups[$key]['valid_rows']++;
            }

            if (($row['status'] ?? null) === 'invalid') {
                $groups[$key]['invalid_rows']++;
            }

            if (! empty($row['client_match']) || ! empty($row['lead_match'])) {
                $groups[$key]['duplicate_rows']++;
            }
        }

        return array_values($groups);
    }

    private function resolveCampaignGroupMapping(LeadUploadBatch $batch, ?string $campaignType): array
    {
        $campaignType = LeadCampaignTypeJourneyMap::normalize($campaignType);
        $key = $campaignType ? LeadCampaignTypeJourneyMap::lookupKey($campaignType) : null;
        $meta = $batch->meta ?? [];
        $overrides = $meta['campaign_group_mappings'] ?? [];

        if ($key && isset($overrides[$key]) && is_array($overrides[$key])) {
            return $this->campaignGroupMappingFromArray($overrides[$key]);
        }

        return LeadCampaignTypeJourneyMap::resolve((int) $batch->company_id, $campaignType);
    }

    private function campaignGroupMappingFromArray(array $mapping): array
    {
        $hasJourneyKey = $this->nullableString($mapping['journey_key'] ?? null) !== null;
        $isActive = (bool) ($mapping['is_active'] ?? true);
        $previewOnly = (bool) ($mapping['preview_only'] ?? true);
        $whatsappEnabled = (bool) ($mapping['whatsapp_enabled'] ?? false);
        $template = $this->nullableString($mapping['whatsapp_template_name'] ?? null);

        return [
            'campaign_type' => $mapping['campaign_type'] ?? null,
            'journey_key' => $this->nullableString($mapping['journey_key'] ?? null),
            'journey_label' => $this->nullableString($mapping['journey_label'] ?? null),
            'journey_trigger_key' => $this->nullableString($mapping['journey_trigger_key'] ?? null),
            'is_active' => $isActive,
            'preview_only' => $previewOnly,
            'whatsapp_enabled' => $whatsappEnabled,
            'whatsapp_template_name' => $template,
            'followup_template_name' => $this->nullableString($mapping['followup_template_name'] ?? null),
            'mapping_status' => ! $hasJourneyKey ? 'Missing' : (! $isActive ? 'Inactive' : ($previewOnly ? 'Preview Only' : 'Mapped')),
            'whatsapp_status' => ! $whatsappEnabled ? 'Disabled' : ($template ? ($previewOnly ? 'Preview Only' : 'Enabled') : 'Template Missing'),
        ];
    }

    private function campaignGroupMappingStatus(array $mapping): string
    {
        if (empty($mapping['journey_key']) || ($mapping['mapping_status'] ?? null) === 'Missing') {
            return 'Missing Mapping';
        }

        if (! (bool) ($mapping['is_active'] ?? false)) {
            return 'Mapping Inactive';
        }

        if ((bool) ($mapping['preview_only'] ?? true)) {
            return 'Preview Only';
        }

        if ((bool) ($mapping['whatsapp_enabled'] ?? false) && empty($mapping['whatsapp_template_name'])) {
            return 'Template Missing';
        }

        if ((bool) ($mapping['whatsapp_enabled'] ?? false)) {
            return 'Ready to Send';
        }

        return 'Preview Only';
    }

    private function ackReadinessLabel(?string $status): string
    {
        return match ($status) {
            'ready' => 'Ready',
            'missing_template_mapping' => 'Missing mapping',
            'template_pending' => 'Template pending',
            'missing_phone' => 'Missing phone',
            'opted_out' => 'Opted out',
            'duplicate_recent_lead' => 'Duplicate lead',
            'invalid_row' => 'Invalid row',
            default => 'Needs review',
        };
    }

    private function ackReadinessReason(LeadUploadRow $row): string
    {
        return match ($row->ack_readiness) {
            'ready' => 'Upload ACK mapping and active template were available at preview time.',
            'missing_template_mapping' => 'No active upload ACK template mapping was available at preview time.',
            'template_pending' => 'Mapped template was not active/approved at preview time.',
            'missing_phone' => 'Phone or WhatsApp is required.',
            'opted_out' => 'Customer appeared opted out from WhatsApp automation.',
            'duplicate_recent_lead' => 'A recent lead already existed for this phone/email.',
            'invalid_row' => 'Fix row errors before instant response can be considered.',
            default => 'Row requires manager review before future instant response.',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function cleanHeader(?string $header): string
    {
        $header = (string) $header;
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = trim(strtolower($header));
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }

    private function cleanRow(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $key = $this->cleanHeader($key);

            if (is_string($value)) {
                $value = trim($value);
            }

            $clean[$key] = $value === '' ? null : $value;
        }

        return $clean;
    }

    private function canonicalizeLeadImportRow(array $data, string $defaultCampaignType): array
    {
        $rowCampaignType = trim((string) ($data['campaign_type'] ?? ''));
        $campaignType = LeadCampaignTypeJourneyMap::normalize($rowCampaignType !== '' ? $rowCampaignType : $defaultCampaignType);

        $canonical = array_merge($data, [
            'name' => $data['customer_name'] ?? $data['name'] ?? null,
            'source' => $data['lead_source'] ?? $data['source'] ?? null,
            'campaign_type' => $campaignType,
            'service_type' => $data['service_type'] ?? $data['service_category'] ?? null,
            'follow_up_date' => $data['preferred_date'] ?? $data['follow_up_date'] ?? null,
            'follow_up_required' => $data['follow_up_required'] ?? 'yes',
            'preferred_channel' => $data['preferred_channel'] ?? 'whatsapp',
        ]);

        if (! isset($canonical['customer_name'])) {
            $canonical['customer_name'] = $canonical['name'];
        }

        if (! isset($canonical['lead_source'])) {
            $canonical['lead_source'] = $canonical['source'];
        }

        $canonical['journey_key'] = LeadCampaignTypeJourneyMap::journeyKeyFor($campaignType);

        return $canonical;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        $email = trim(strtolower($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = trim((string) $phone);

        if (stripos($phone, 'E+') !== false || stripos($phone, 'E-') !== false) {
            $phone = number_format((float) $phone, 0, '', '');
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone ?: null;
    }

    private function normalizeImportSource(?string $source): string
    {
        $source = strtolower(trim((string) $source));

        if ($source === '') {
            return 'csv';
        }

        if (in_array($source, ['excel', 'xlsx', 'xls', 'upload', 'bulk', 'bulk_import'], true)) {
            return 'import';
        }

        return $source;
    }

    private function isExplicitFalse(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array(strtolower(trim((string) $value)), ['0', 'no', 'n', 'false'], true);
    }

    private function normalizeFieldValue(string $field, mixed $value): mixed
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

        if ($field === 'campaign_type') {
            return LeadCampaignTypeJourneyMap::normalize((string) $value);
        }

        return is_string($value) ? strtolower(trim($value)) : $value;
    }

    private function resolveAssignedUserId(int $companyId, mixed $assignedTo): ?int
    {
        if (! $assignedTo) {
            return null;
        }

        if (is_numeric($assignedTo)) {
            return User::where('company_id', $companyId)
                ->where('id', (int) $assignedTo)
                ->value('id');
        }

        $assignedTo = trim((string) $assignedTo);

        return User::where('company_id', $companyId)
            ->where(function ($q) use ($assignedTo) {
                $q->where('email', $assignedTo)
                    ->orWhere('name', $assignedTo);
            })
            ->value('id');
    }

    private function resolveOrCreateClient(
        int $companyId,
        ?string $name,
        ?string $phone,
        ?string $email,
        string $source,
        array $extra = []
    ): array {
        if (! Schema::hasTable('clients')) {
            return [null, false];
        }

        $phone = $this->normalizePhone($phone);
        $email = $this->normalizeEmail($email);
        $name = trim((string) $name) ?: 'Customer';

        $query = Client::where('company_id', $companyId);

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

            foreach ([
                'name' => $client->name ?: $name,
                'phone' => $client->phone ?: $phone,
                'email' => $client->email ?: $email,
                'source' => $client->source ?: $source,
                'phone_norm' => $phone,
                'email_norm' => $email,
                'whatsapp' => $phone,
                'whatsapp_number' => $phone,
            ] as $field => $value) {
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

        foreach ([
            'source' => $source,
            'status' => 'active',
            'phone_norm' => $phone,
            'email_norm' => $email,
            'whatsapp' => $phone,
            'whatsapp_number' => $phone,
        ] as $field => $value) {
            if (Schema::hasColumn('clients', $field)) {
                $clientData[$field] = $value;
            }
        }

        $client = Client::create($clientData);

        return [$client, true];
    }

    private function createOrUpdateClientAndVehicle(
        int $companyId,
        Lead $lead,
        array $data,
        ?Client $existingClient = null
    ): bool {
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $email = $this->normalizeEmail($data['email'] ?? null);

        if (! Schema::hasTable('clients')) {
            return false;
        }

        $client = $existingClient;

        if (! $client) {
            [$client] = $this->resolveOrCreateClient(
                companyId: $companyId,
                name: $data['name'] ?? $lead->name,
                phone: $phone,
                email: $email,
                source: $data['source'] ?? 'csv',
                extra: $data
            );
        }

        if ($client && Schema::hasColumn('leads', 'client_id') && ! $lead->client_id) {
            $lead->client_id = $client->id;
            $lead->save();
        }

        if (! Schema::hasTable('vehicles') || ! $client) {
            return false;
        }

        $makeName = $data['vehicle_make'] ?? null;
        $modelName = $data['vehicle_model'] ?? null;
        $year = $data['vehicle_year'] ?? null;
        $plate = $data['plate_number'] ?? null;

        if (! $plate && (! $makeName || ! $modelName)) {
            return false;
        }

        $makeId = null;
        $modelId = null;

        if ($makeName) {
            $make = VehicleMake::whereRaw('LOWER(name) = ?', [strtolower($makeName)])->first();

            if (! $make) {
                $make = VehicleMake::create([
                    'name' => $makeName,
                ]);
            }

            $makeId = $make->id;
        }

        if ($modelName) {
            $modelQuery = VehicleModel::whereRaw('LOWER(name) = ?', [strtolower($modelName)]);

            if ($makeId) {
                $modelQuery->where('make_id', $makeId);
            }

            $model = $modelQuery->first();

            if (! $model) {
                $model = VehicleModel::create([
                    'make_id' => $makeId,
                    'name'    => $modelName,
                ]);
            }

            $modelId = $model->id;
        }

        $vehicleQuery = Vehicle::where('company_id', $companyId)
            ->where('client_id', $client->id);

        if ($plate) {
            $vehicleQuery->where('plate_number', $plate);
        } elseif ($makeId || $modelId) {
            $vehicleQuery->where('make_id', $makeId)
                ->where('model_id', $modelId);
        }

        $vehicle = $vehicleQuery->first();

        if (! $vehicle) {
            $vehicleData = [
                'company_id'   => $companyId,
                'client_id'    => $client->id,
                'make_id'      => $makeId,
                'model_id'     => $modelId,
                'plate_number' => $plate,
                'year'         => $year ? (string) $year : null,
            ];

            $vehicle = Vehicle::create($vehicleData);

            $this->syncVehicleToLead($lead, $vehicle, $makeId, $modelId, $makeName, $modelName);

            return true;
        }

        $this->syncVehicleToLead($lead, $vehicle, $makeId, $modelId, $makeName, $modelName);

        return false;
    }

    private function syncVehicleToLead(
        Lead $lead,
        Vehicle $vehicle,
        ?int $makeId,
        ?int $modelId,
        ?string $makeName,
        ?string $modelName
    ): void {
        $updates = [];

        foreach ([
            'vehicle_id' => $vehicle->id,
            'vehicle_make_id' => $makeId,
            'vehicle_model_id' => $modelId,
            'other_make' => $makeId ? null : $makeName,
            'other_model' => $modelId ? null : $modelName,
        ] as $field => $value) {
            if (Schema::hasColumn('leads', $field)) {
                $updates[$field] = $value;
            }
        }

        if (! empty($updates)) {
            $lead->forceFill($updates)->save();
        }
    }

    private function applyAudienceSegmentation(Lead $lead, array $data): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Safe dynamic audience segmentation
        |--------------------------------------------------------------------------
        |
        | We do not assume the exact method name because existing AudienceResolver
        | may differ. This safely calls it only when available.
        */

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
                    $resolver->{$method}($lead, $data);

                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[LeadImport] Audience segmentation skipped', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
