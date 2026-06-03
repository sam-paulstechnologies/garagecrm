<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Services\Leads\LeadFactory;
use App\Services\Meta\MetaLeadService;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        return view('admin.leads.import.index');
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
            'import_type' => ['nullable', 'in:standard,historic,recent'],
        ]);

        $companyId = (int) $request->user()->company_id;
        $importType = $request->input('import_type', 'standard') ?: 'standard';

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
            'name',
            'phone',
            'source',
            'service_category',
        ];

        $missingHeaders = array_diff($requiredHeaders, $header);

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
                $name = $data['name'] ?? null;
                $phone = $this->normalizePhone($data['phone'] ?? null);
                $email = $this->normalizeEmail($data['email'] ?? null);

                if (! $name || ! $phone) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Name and phone are required.";
                    continue;
                }

                $originalSource = $this->normalizeImportSource($data['source'] ?? 'csv');
                $leadSource = $this->sourceForImportType($importType, $originalSource);

                /*
                |--------------------------------------------------------------------------
                | Create/update Client first
                |--------------------------------------------------------------------------
                |
                | Import should always create/reuse client, then attach the lead.
                | Imported leads are still skipped from instant WhatsApp ACK by
                | HandleLeadCreatedOutbound, because external_source stays import.
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
                    'notes'             => $this->notesForImportType($data['notes'] ?? null, $importType, $originalSource),
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
                        'raw' => $data,
                    ];

                    if ($importType !== 'standard') {
                        $leadPayload['external_payload']['import_type'] = $importType;
                        $leadPayload['external_payload']['original_source'] = $originalSource;
                    }
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
                    'retention_tag',
                ];

                foreach ($extraLeadFields as $field) {
                    if (Schema::hasColumn('leads', $field) && array_key_exists($field, $data)) {
                        $leadPayload[$field] = $this->normalizeFieldValue($field, $data[$field]);
                    }
                }

                $this->applyImportModeOverrides($leadPayload, $importType, $data);

                if (
                    Schema::hasColumn('leads', 'assigned_to')
                    && ! empty($data['assigned_to'])
                ) {
                    $assignedUserId = $this->resolveAssignedUserId($companyId, $data['assigned_to']);

                    if ($assignedUserId) {
                        $leadPayload['assigned_to'] = $assignedUserId;
                    }
                }

                $result = $importType === 'standard'
                    ? $this->factory->createOrDetectDuplicate($leadPayload)
                    : Lead::withoutEvents(fn () => $this->factory->createOrDetectDuplicate($leadPayload));

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

        $message = $this->importTypeLabel($importType) . " completed: +{$inserted} new, ~{$updated} updated, {$dupes} duplicates, {$skipped} skipped. "
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

    private function sourceForImportType(string $importType, string $originalSource): string
    {
        return match ($importType) {
            'historic' => 'imported_historic',
            'recent' => 'imported_recent',
            default => $originalSource,
        };
    }

    private function importTypeLabel(string $importType): string
    {
        return match ($importType) {
            'historic' => 'Historic Data Import',
            'recent' => 'Recent Leads Import',
            default => 'Standard Import',
        };
    }

    private function notesForImportType(?string $notes, string $importType, string $originalSource): ?string
    {
        $lines = [];

        if ($notes) {
            $lines[] = trim($notes);
        }

        if ($importType === 'historic') {
            $lines[] = 'Historic data import - customer/vehicle history record.';
        }

        if ($importType === 'recent') {
            $lines[] = 'Recent imported lead - conversation required.';
        }

        if (in_array($importType, ['historic', 'recent'], true)) {
            $lines[] = "Original CSV source: {$originalSource}";
        }

        return $lines ? implode(PHP_EOL, $lines) : null;
    }

    private function applyImportModeOverrides(array &$leadPayload, string $importType, array $data): void
    {
        if ($importType === 'historic') {
            if (Schema::hasColumn('leads', 'is_active')) {
                $leadPayload['is_active'] = false;
            }

            if (Schema::hasColumn('leads', 'follow_up_required')) {
                $leadPayload['follow_up_required'] = false;
            }

            return;
        }

        if ($importType !== 'recent') {
            return;
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

        if (! $makeName && ! $modelName && ! $plate) {
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
