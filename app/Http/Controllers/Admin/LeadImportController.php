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
        return view('admin.leads.import');
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
        ]);

        $companyId = (int) $request->user()->company_id;

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
        $skipped = 0;
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

                $leadPayload = [
                    'company_id'        => $companyId,
                    'name'              => $name,
                    'phone'             => $phone,
                    'email'             => $email,
                    'source'            => $data['source'] ?? 'csv',
                    'status'            => Lead::STATUS_NEW,
                    'notes'             => $data['notes'] ?? null,
                    'preferred_channel' => $data['preferred_channel'] ?? 'whatsapp',
                    'window_days'       => 30,
                ];

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

                if (
                    Schema::hasColumn('leads', 'assigned_to')
                    && ! empty($data['assigned_to'])
                ) {
                    $assignedUserId = $this->resolveAssignedUserId($companyId, $data['assigned_to']);

                    if ($assignedUserId) {
                        $leadPayload['assigned_to'] = $assignedUserId;
                    }
                }

                $result = $this->factory->createOrDetectDuplicate($leadPayload);

                if ($result instanceof Lead) {
                    $lead = $result;
                    $inserted++;

                    $this->createOrUpdateClientAndVehicle($companyId, $lead, $data);
                } else {
                    $dupes++;
                }

            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $message = "CSV import done: +{$inserted} new, ⚠{$dupes} duplicates, {$skipped} skipped.";

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

                    if (! empty($row['external_id'])) {
                        $lead = Lead::updateOrCreate(
                            [
                                'company_id'      => $companyId,
                                'external_source' => 'meta',
                                'external_id'     => (string) $row['external_id'],
                            ],
                            [
                                'name'                 => $row['name'] ?? 'Meta Lead',
                                'email'                => $row['email'] ?? null,
                                'phone'                => $row['phone'] ?? null,
                                'status'               => Lead::STATUS_NEW,
                                'source'               => 'meta',
                                'preferred_channel'    => 'whatsapp',
                                'external_form_id'     => (string) $formId,
                                'external_payload'     => $payload,
                                'external_received_at' => now(),
                                'created_at'           => $createdTime ?? now(),
                            ]
                        );

                        $lead->wasRecentlyCreated ? $inserted++ : $updated++;
                        continue;
                    }

                    $result = $this->factory->createOrDetectDuplicate([
                        'company_id'           => $companyId,
                        'name'                 => $row['name'] ?? 'Meta Lead',
                        'email'                => $row['email'] ?? null,
                        'phone'                => $row['phone'] ?? null,
                        'status'               => Lead::STATUS_NEW,
                        'source'               => 'meta',
                        'preferred_channel'    => 'whatsapp',
                        'external_source'      => 'meta',
                        'external_form_id'     => (string) $formId,
                        'external_payload'     => $payload,
                        'external_received_at' => now(),
                        'window_days'          => $windowDays,
                    ]);

                    $result instanceof Lead ? $inserted++ : $dupes++;
                }

                if ($maxCreated > 0) {
                    $store->set($ckKey, gmdate('c', $maxCreated));
                }

                $console[] = "✔ Form {$formId} processed";

            } catch (\Throwable $e) {
                $console[] = "❌ {$e->getMessage()}";
            } finally {
                optional($lock)->release();
            }
        }

        $summary = "Meta import done: +{$inserted} new, ~{$updated} updated, ⚠{$dupes} duplicates";

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

    private function createOrUpdateClientAndVehicle(int $companyId, Lead $lead, array $data): void
    {
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $email = $this->normalizeEmail($data['email'] ?? null);

        if (! Schema::hasTable('clients')) {
            return;
        }

        $clientQuery = Client::where('company_id', $companyId);

        $clientQuery->where(function ($q) use ($phone, $email) {
            if ($phone) {
                $q->where('phone', $phone);

                if (Schema::hasColumn('clients', 'whatsapp')) {
                    $q->orWhere('whatsapp', $phone);
                }
            }

            if ($email) {
                $q->orWhere('email', $email);
            }
        });

        $client = $clientQuery->first();

        if (! $client) {
            $clientData = [
                'company_id' => $companyId,
                'name'       => $data['name'] ?? $lead->name,
                'phone'      => $phone,
                'email'      => $email,
            ];

            if (Schema::hasColumn('clients', 'whatsapp')) {
                $clientData['whatsapp'] = $phone;
            }

            $client = Client::create($clientData);
        }

        if (Schema::hasColumn('leads', 'client_id') && ! $lead->client_id) {
            $lead->client_id = $client->id;
            $lead->save();
        }

        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $makeName = $data['vehicle_make'] ?? null;
        $modelName = $data['vehicle_model'] ?? null;
        $year = $data['vehicle_year'] ?? null;
        $plate = $data['plate_number'] ?? null;

        if (! $makeName && ! $modelName && ! $plate) {
            return;
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
            Vehicle::create([
                'company_id'   => $companyId,
                'client_id'    => $client->id,
                'make_id'      => $makeId,
                'model_id'     => $modelId,
                'plate_number' => $plate,
                'year'         => $year ? (string) $year : null,
            ]);
        }
    }
}