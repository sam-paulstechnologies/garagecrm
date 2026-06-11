<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\ClientImportBatch;
use App\Models\Client\ClientImportRow;
use App\Models\Client\Note;
use App\Models\Job\Job;
use App\Services\Clients\ClientImportApplyService;
use App\Services\Clients\ClientImportRetentionActionService;
use App\Services\Clients\ClientImportRetentionPreviewService;
use App\Services\Clients\ClientImportServiceHistoryService;
use App\Services\Retention\VehicleRenewalOpportunityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * 📄 List active clients
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $q = trim((string) $request->get('q', ''));

        $filters = [
            'q'               => $q,
            'customer_type'   => $request->get('customer_type', 'all'),
            'vehicle_make'    => $request->get('vehicle_make', 'all'),
            'service_history' => $request->get('service_history', 'all'),
            'last_activity'   => $request->get('last_activity', 'all'),
            'source'          => $request->get('source', 'all'),
        ];

        $clientsQuery = Client::with([
                'vehicles.make',
                'vehicles.model',
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', false);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        $clientsQuery->when($q, function ($query) use ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('whatsapp', 'like', "%{$q}%")
                    ->orWhereHas('vehicles.make', function ($vehicleMakeQuery) use ($q) {
                        $vehicleMakeQuery->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('vehicles.model', function ($vehicleModelQuery) use ($q) {
                        $vehicleModelQuery->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('vehicles', function ($vehicleQuery) use ($q) {
                        $vehicleQuery->where('plate_number', 'like', "%{$q}%")
                            ->orWhere('vin', 'like', "%{$q}%");
                    });
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Customer Type
        |--------------------------------------------------------------------------
        */
        if ($filters['customer_type'] === 'new') {
            $clientsQuery->where('created_at', '>=', now()->subDays(30)->startOfDay());
        }

        if ($filters['customer_type'] === 'returning') {
            $clientsQuery->where('created_at', '<', now()->subDays(30)->startOfDay());
        }

        if ($filters['customer_type'] === 'vip' && Schema::hasColumn('clients', 'is_vip')) {
            $clientsQuery->where('is_vip', true);
        }

        /*
        |--------------------------------------------------------------------------
        | Vehicle Make
        |--------------------------------------------------------------------------
        */
        if ($filters['vehicle_make'] !== 'all') {
            $clientsQuery->whereHas('vehicles', function ($vehicleQuery) use ($filters) {
                $vehicleQuery->where('make_id', $filters['vehicle_make']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Service History
        |--------------------------------------------------------------------------
        */
        if ($filters['service_history'] === 'has_booking') {
            $clientsQuery->whereHas('bookings');
        }

        if ($filters['service_history'] === 'has_job') {
            $clientsQuery->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('jobs')
                    ->whereColumn('jobs.client_id', 'clients.id');
            });
        }

        if ($filters['service_history'] === 'has_invoice') {
            $clientsQuery->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('invoices')
                    ->whereColumn('invoices.client_id', 'clients.id')
                    ->whereNull('invoices.deleted_at');
            });
        }

        if ($filters['service_history'] === 'has_unpaid_invoice') {
            $clientsQuery->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('invoices')
                    ->whereColumn('invoices.client_id', 'clients.id')
                    ->whereNull('invoices.deleted_at')
                    ->where('invoices.status', '!=', 'paid');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Last Activity
        |--------------------------------------------------------------------------
        | Current v1 rule: based on client updated_at.
        |--------------------------------------------------------------------------
        */
        if ($filters['last_activity'] === 'last_7_days') {
            $clientsQuery->where('updated_at', '>=', now()->subDays(7)->startOfDay());
        }

        if ($filters['last_activity'] === 'this_month') {
            $clientsQuery->whereBetween('updated_at', [
                now()->startOfMonth()->startOfDay(),
                now()->endOfDay(),
            ]);
        }

        if ($filters['last_activity'] === 'last_90_days') {
            $clientsQuery->where('updated_at', '>=', now()->subDays(90)->startOfDay());
        }

        /*
        |--------------------------------------------------------------------------
        | Source
        |--------------------------------------------------------------------------
        */
        if ($filters['source'] !== 'all' && Schema::hasColumn('clients', 'source')) {
            $clientsQuery->where('source', $filters['source']);
        }

        $clients = $clientsQuery
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $vehicleMakes = $this->vehicleMakesForCompany($companyId);

        return view('admin.clients.index', compact(
            'clients',
            'q',
            'filters',
            'vehicleMakes'
        ));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    /**
     * 💾 Store client safely
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'phone'             => 'nullable|string|max:50',
            'whatsapp'          => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:255',
            'dob'               => 'nullable|date',
            'gender'            => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address'           => 'nullable|string',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:100',
            'source'            => 'nullable|string|max:255',
            'status'            => 'nullable|string|max:50',
            'notes'             => 'nullable|string',
            'preferred_channel' => ['nullable', Rule::in(['email', 'phone', 'whatsapp'])],
            'is_vip'            => 'nullable|boolean',
        ]);

        $companyId = auth()->user()->company_id;

        $possibleDuplicate = Client::where('company_id', $companyId)
            ->where(function ($q) use ($data) {
                if (! empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }

                if (! empty($data['whatsapp'])) {
                    $q->orWhere('whatsapp', $data['whatsapp']);
                }

                if (! empty($data['email'])) {
                    $q->orWhere('email', $data['email']);
                }
            })
            ->first();

        if ($possibleDuplicate) {
            return redirect()
                ->route('admin.clients.show', $possibleDuplicate->id)
                ->with('warning', 'Client already exists. Opened the existing client instead.');
        }

        $data['company_id'] = $companyId;
        $data['is_vip'] = $request->boolean('is_vip');

        $client = Client::create($data);

        return redirect()
            ->route('admin.clients.show', $client->id)
            ->with('success', 'Client created successfully.');
    }

    /**
     * 👁️ View client profile
     */
    public function show(Client $client, VehicleRenewalOpportunityService $vehicleRenewalOpportunityService)
    {
        $this->authorizeClient($client);

        $client->load([
            'vehicles.make',
            'vehicles.model',
            'opportunities.vehicleMake',
            'opportunities.vehicleModel',
            'leads',
            'bookings',
            'notes',
            'files',
        ]);

        $serviceHistory = Job::where('company_id', auth()->user()->company_id)
            ->where('client_id', $client->id)
            ->where('status', 'completed')
            ->latest('end_time')
            ->take(10)
            ->get();

        $vehicleCount = $client->vehicles?->count() ?? 0;

        if ($vehicleCount === 0) {
            $vehicleCount = collect($client->opportunities ?? [])
                ->map(fn ($o) => trim(
                    ($o->vehicleMake?->name ?? $o->other_make ?? '') . ' ' .
                    ($o->vehicleModel?->name ?? $o->other_model ?? '')
                ))
                ->filter()
                ->unique()
                ->count();
        }

        $primaryVehicle = $client->vehicles?->first();

        $missingItems = [];
        $score = 0;

        if ($client->name) {
            $score += 10;
        } else {
            $missingItems[] = 'Customer name';
        }

        if ($client->phone || $client->whatsapp) {
            $score += 15;
        } else {
            $missingItems[] = 'Phone or WhatsApp number';
        }

        if ($client->email) {
            $score += 10;
        } else {
            $missingItems[] = 'Email address';
        }

        if ($client->city || $client->country || $client->address) {
            $score += 10;
        } else {
            $missingItems[] = 'Address / location';
        }

        if ($vehicleCount > 0) {
            $score += 15;
        } else {
            $missingItems[] = 'Vehicle details';
        }

        if ($primaryVehicle?->plate_number) {
            $score += 10;
        } else {
            $missingItems[] = 'Plate number';
        }

        if ($primaryVehicle?->vin) {
            $score += 10;
        } else {
            $missingItems[] = 'VIN';
        }

        if ($primaryVehicle?->registration_expiry_date) {
            $score += 10;
        } else {
            $missingItems[] = 'Mulkia / registration expiry date';
        }

        if ($primaryVehicle?->insurance_expiry_date) {
            $score += 5;
        } else {
            $missingItems[] = 'Insurance expiry date';
        }

        if ($primaryVehicle?->current_mileage) {
            $score += 5;
        } else {
            $missingItems[] = 'Current mileage';
        }

        $lastCompletedJob = $serviceHistory->first();

        $lastServiceDate = $lastCompletedJob?->end_time;

        $nextServiceDate = $lastServiceDate
            ? Carbon::parse($lastServiceDate)->addMonths(6)
            : null;

        $nextServiceStatus = 'not_available';

        if ($nextServiceDate) {
            if ($nextServiceDate->isPast()) {
                $nextServiceStatus = 'overdue';
            } elseif ($nextServiceDate->diffInDays(now()) <= 30) {
                $nextServiceStatus = 'due_soon';
            } else {
                $nextServiceStatus = 'scheduled';
            }
        }

        $kpis = [
            'cars'                => $vehicleCount,
            'ltv'                 => 0,
            'avg_spend'           => 0,
            'last_service'        => $lastServiceDate,
            'next_service'        => $nextServiceDate,
            'next_service_status' => $nextServiceStatus,
            'last_service_type'   => $lastCompletedJob?->description,
            'profile_pct'         => min(100, $score),
            'missing_items'       => $missingItems,
        ];

        $nextRetentionFollowUp = $vehicleRenewalOpportunityService->nextForClient($client, true) ?? [
            'state' => 'empty',
            'status_label' => 'No Data',
            'segment_label' => null,
            'follow_up_date' => null,
            'channel' => null,
            'message' => null,
            'source_label' => null,
            'safety_note' => 'No message is sent from this card.',
        ];

        return view('admin.clients.show', compact('client', 'kpis', 'serviceHistory', 'nextRetentionFollowUp'));
    }

    public function edit(Client $client)
    {
        $this->authorizeClient($client);

        $client->load([
            'vehicles.make',
            'vehicles.model',
        ]);

        $latestServiceHistory = null;
        $profileMissingItems = $this->profileMissingItemsFor($client);

        return view('admin.clients.edit', compact('client', 'latestServiceHistory', 'profileMissingItems'));
    }

    private function profileMissingItemsFor(Client $client): array
    {
        $primaryVehicle = $client->vehicles?->first();
        $missingItems = [];

        if (! filled($client->email)) {
            $missingItems[] = 'Email address';
        }

        if (! filled($client->phone) && ! filled($client->whatsapp)) {
            $missingItems[] = 'Phone or WhatsApp number';
        }

        if (! filled($client->city) && ! filled($client->country) && ! filled($client->address)) {
            $missingItems[] = 'Address / location';
        }

        if (! $primaryVehicle) {
            $missingItems[] = 'Vehicle details';

            return $missingItems;
        }

        if (! filled($primaryVehicle->plate_number)) {
            $missingItems[] = 'Plate number';
        }

        if (! filled($primaryVehicle->vin)) {
            $missingItems[] = 'VIN';
        }

        if (! filled($primaryVehicle->registration_expiry_date)) {
            $missingItems[] = 'Mulkia / registration expiry date';
        }

        if (! filled($primaryVehicle->insurance_expiry_date)) {
            $missingItems[] = 'Insurance expiry date';
        }

        if (! filled($primaryVehicle->current_mileage)) {
            $missingItems[] = 'Current mileage';
        }

        return $missingItems;
    }

    /**
     * 🔁 Update client safely
     */
    public function update(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'phone'             => 'nullable|string|max:50',
            'whatsapp'          => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:255',
            'dob'               => 'nullable|date',
            'gender'            => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address'           => 'nullable|string',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:100',
            'source'            => 'nullable|string|max:255',
            'status'            => 'nullable|string|max:50',
            'notes'             => 'nullable|string',
            'preferred_channel' => ['nullable', Rule::in(['email', 'phone', 'whatsapp'])],
            'is_vip'            => 'nullable|boolean',
        ]);

        $possibleDuplicate = Client::where('company_id', auth()->user()->company_id)
            ->where('id', '!=', $client->id)
            ->where(function ($q) use ($data) {
                if (! empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }

                if (! empty($data['whatsapp'])) {
                    $q->orWhere('whatsapp', $data['whatsapp']);
                }

                if (! empty($data['email'])) {
                    $q->orWhere('email', $data['email']);
                }
            })
            ->first();

        if ($possibleDuplicate) {
            return redirect()
                ->route('admin.clients.show', $possibleDuplicate->id)
                ->with('warning', 'Another client already exists with the same phone, WhatsApp, or email.');
        }

        $data['is_vip'] = $request->boolean('is_vip');

        $client->update($data);

        return redirect()
            ->route('admin.clients.show', $client->id)
            ->with('success', 'Client updated successfully.');
    }

    public function archive(Client $client)
    {
        $this->authorizeClient($client);

        $client->update(['is_archived' => true]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client archived.');
    }

    public function archived(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $q = trim((string) $request->get('q', ''));

        $clients = Client::with([
                'vehicles.make',
                'vehicles.model',
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', true)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('whatsapp', 'like', "%{$q}%")
                        ->orWhereHas('vehicles.make', function ($vehicleMakeQuery) use ($q) {
                            $vehicleMakeQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicles.model', function ($vehicleModelQuery) use ($q) {
                            $vehicleModelQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicles', function ($vehicleQuery) use ($q) {
                            $vehicleQuery->where('plate_number', 'like', "%{$q}%")
                                ->orWhere('vin', 'like', "%{$q}%");
                        });
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.archived', compact('clients', 'q'));
    }

    public function restore(Client $client)
    {
        $this->authorizeClient($client);

        $client->update(['is_archived' => false]);

        return redirect()
            ->route('admin.clients.archived')
            ->with('success', 'Client restored.');
    }

    /**
     * 📥 Import form
     */
    public function importForm()
    {
        return view('admin.clients.import');
    }

    /**
     * 📥 Import clients
     */
    public function importSample()
    {
        $headers = [
            'name',
            'phone',
            'whatsapp',
            'email',
            'vehicle_make',
            'vehicle_model',
            'plate_number',
            'vehicle_year',
            'last_service_date',
            'last_service_type',
            'last_invoice_amount',
            'last_mileage',
            'insurance_expiry_date',
            'mulkia_expiry_date',
            'source',
            'status',
            'is_vip',
            'preferred_channel',
            'notes',
        ];

        $sampleRow = [
            'Sam Abhishek',
            '971586934377',
            '971586934377',
            'sam@example.com',
            'Mercedes-Benz',
            'GLE',
            'D12345',
            '2021',
            now()->subMonths(7)->toDateString(),
            'General Service',
            '850.00',
            '72000',
            now()->addDays(24)->toDateString(),
            now()->addDays(28)->toDateString(),
            'import',
            'active',
            '1',
            'whatsapp',
            'Previous garage customer',
        ];

        return response()->streamDownload(function () use ($headers, $sampleRow) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            fputcsv($output, $sampleRow);
            fclose($output);
        }, 'sample_client_import.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Client import workflow
     */
    public function import(Request $request, ClientImportRetentionPreviewService $previewService)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $companyId = (int) auth()->user()->company_id;
        $preview = $previewService->buildPreview(
            $data['file'],
            $companyId,
            ClientImportRetentionPreviewService::DEFAULT_LIMIT
        );

        $storedPath = $data['file']->storeAs(
            'private/client-imports',
            now()->format('YmdHis') . '_' . Str::random(8) . '_' . Str::slug(pathinfo($data['file']->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $data['file']->getClientOriginalExtension()
        );

        $batch = DB::transaction(function () use ($preview, $companyId, $data, $storedPath) {
            $summary = $preview['summary'];

            $batch = ClientImportBatch::create([
                'company_id' => $companyId,
                'uploaded_by' => auth()->id(),
                'original_filename' => $data['file']->getClientOriginalName(),
                'stored_path' => $storedPath,
                'status' => 'parsed',
                'total_rows' => $summary['rows_uploaded'] ?? 0,
                'valid_rows' => $summary['valid_rows'] ?? 0,
                'warning_rows' => $summary['warning_rows'] ?? 0,
                'invalid_rows' => $summary['invalid_rows'] ?? 0,
                'duplicate_rows' => $summary['duplicates'] ?? 0,
                'suggested_retention_actions' => $summary['suggested_retention_actions'] ?? 0,
                'meta' => [
                    'rows_previewed' => $summary['rows_previewed'] ?? 0,
                    'truncated' => $summary['truncated'] ?? false,
                    'limit' => $summary['limit'] ?? ClientImportRetentionPreviewService::DEFAULT_LIMIT,
                    'stored_disk' => config('filesystems.default'),
                ],
            ]);

            foreach ($preview['rows'] as $row) {
                ClientImportRow::create([
                    'batch_id' => $batch->id,
                    'company_id' => $companyId,
                    'row_number' => $row['row_number'],
                    'raw_payload' => $row['raw_payload'] ?? $row['data'],
                    'normalized_payload' => $row['data'],
                    'client_match_id' => $row['duplicate']['id'] ?? null,
                    'vehicle_match_id' => null,
                    'duplicate_status' => $row['duplicate_status'] ?? 'none',
                    'validation_status' => $row['status'],
                    'errors' => $row['errors'],
                    'warnings' => $row['warnings'],
                    'suggested_segment_code' => $row['suggestion']['segment_code'] ?? null,
                    'suggested_segment_label' => $row['suggestion']['segment_label'] ?? null,
                    'suggested_next_action_date' => $row['suggestion']['follow_up_date'] ?? null,
                    'suggested_message' => $row['suggestion']['message'] ?? null,
                    'review_status' => 'pending_review',
                ]);
            }

            return $batch;
        });

        return redirect()
            ->route('admin.clients.import.batches.show', $batch)
            ->with('success', 'Import preview saved. No clients, vehicles, actions, or messages were created.');
    }

    public function importBatches()
    {
        $batches = ClientImportBatch::with('uploadedBy')
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(15);

        return view('admin.clients.import-batches', compact('batches'));
    }

    public function importBatchShow(int $batch)
    {
        $batch = ClientImportBatch::with(['uploadedBy', 'rows.clientMatch'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($batch);

        return view('admin.clients.import-preview', $this->previewPayloadFromBatch($batch));
    }

    public function reviewImportRow(Request $request, int $batch, int $row)
    {
        $data = $request->validate([
            'review_status' => ['required', Rule::in(['approved', 'rejected', 'skipped', 'pending_review'])],
        ]);

        $batch = $this->findImportBatchForCurrentCompany($batch);
        $row = $this->findImportRowForBatch($batch, $row);

        if ($row->review_status === 'applied') {
            return back()->with('warning', 'Applied rows cannot be changed from this review screen.');
        }

        if ($data['review_status'] === 'approved' && $row->validation_status === 'invalid') {
            return back()->with('warning', "Row #{$row->row_number} cannot be approved because it has blocking validation errors.");
        }

        $row->update(['review_status' => $data['review_status']]);
        $this->refreshImportBatchReviewStatus($batch);

        return back()->with('success', "Row #{$row->row_number} marked as " . Str::headline($data['review_status']) . '.');
    }

    public function bulkReviewImportRows(Request $request, int $batch)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'skip', 'reset'])],
            'row_ids' => ['required', 'array', 'min:1'],
            'row_ids.*' => ['integer'],
        ]);

        $batch = $this->findImportBatchForCurrentCompany($batch);
        $rows = ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->whereIn('id', $data['row_ids'])
            ->get();

        if ($rows->isEmpty()) {
            return back()->with('warning', 'No matching rows were found for this import batch.');
        }

        $targetStatus = match ($data['action']) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'skip' => 'skipped',
            'reset' => 'pending_review',
        };

        $updated = 0;
        $skippedInvalid = 0;
        $skippedApplied = 0;

        DB::transaction(function () use ($rows, $targetStatus, &$updated, &$skippedInvalid, &$skippedApplied) {
            foreach ($rows as $row) {
                if ($row->review_status === 'applied') {
                    $skippedApplied++;
                    continue;
                }

                if ($targetStatus === 'approved' && $row->validation_status === 'invalid') {
                    $skippedInvalid++;
                    continue;
                }

                $row->update(['review_status' => $targetStatus]);
                $updated++;
            }
        });

        $this->refreshImportBatchReviewStatus($batch);

        $message = "{$updated} row(s) marked as " . Str::headline($targetStatus) . '.';

        if ($skippedInvalid > 0) {
            $message .= " {$skippedInvalid} invalid row(s) were skipped.";
        }

        if ($skippedApplied > 0) {
            $message .= " {$skippedApplied} applied row(s) were not changed.";
        }

        return back()->with($updated > 0 ? 'success' : 'warning', $message);
    }

    public function applyImportBatch(Request $request, int $batch, ClientImportApplyService $applyService)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['dry_run', 'apply'])],
        ]);

        $batch = ClientImportBatch::with(['uploadedBy', 'rows.clientMatch'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($batch);

        if ($data['mode'] === 'dry_run') {
            $payload = $this->previewPayloadFromBatch($batch);
            $payload['applySummary'] = $applyService->dryRun($batch);

            return view('admin.clients.import-preview', $payload);
        }

        $summary = $applyService->apply($batch, auth()->id());

        $message = "Apply complete: {$summary['rows_applied']} approved row(s) applied. "
            . "Clients created {$summary['clients_created']}, reused {$summary['clients_reused']}, updated {$summary['clients_updated']}. "
            . "Vehicles created {$summary['vehicles_created']}, reused {$summary['vehicles_reused']}, updated {$summary['vehicles_updated']}.";

        if ($summary['vehicles_skipped_missing_data'] > 0) {
            $message .= " {$summary['vehicles_skipped_missing_data']} row(s) had no vehicle created due to missing make/model.";
        }

        return redirect()
            ->route('admin.clients.import.batches.show', $batch)
            ->with('success', $message);
    }

    public function importBatchServiceHistory(Request $request, int $batch, ClientImportServiceHistoryService $serviceHistoryService)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['dry_run', 'apply'])],
        ]);

        $batch = ClientImportBatch::with(['uploadedBy', 'rows.clientMatch'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($batch);

        if ($data['mode'] === 'dry_run') {
            $payload = $this->previewPayloadFromBatch($batch);
            $payload['serviceHistorySummary'] = $serviceHistoryService->dryRun($batch);

            return view('admin.clients.import-preview', $payload);
        }

        $summary = $serviceHistoryService->createFromAppliedRows($batch, auth()->id());

        return redirect()
            ->route('admin.clients.import.batches.show', $batch)
            ->with('success', "Service history complete: {$summary['histories_created']} imported history record(s) created. {$summary['duplicate_existing_histories']} duplicate existing record(s), {$summary['skipped_rows']} skipped row(s).");
    }

    public function importBatchRetentionActions(Request $request, int $batch, ClientImportRetentionActionService $retentionActionService)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['dry_run', 'apply'])],
        ]);

        $batch = ClientImportBatch::with(['uploadedBy', 'rows.clientMatch'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($batch);

        if ($data['mode'] === 'dry_run') {
            $payload = $this->previewPayloadFromBatch($batch);
            $payload['retentionActionSummary'] = $retentionActionService->dryRun($batch);

            return view('admin.clients.import-preview', $payload);
        }

        $summary = $retentionActionService->createFromAppliedRows($batch, auth()->id());

        return redirect()
            ->route('admin.clients.import.batches.show', $batch)
            ->with('success', "Retention action setup complete: {$summary['actions_created']} pending action(s) created. {$summary['duplicate_existing_actions']} duplicate existing action(s), {$summary['skipped_rows']} skipped row(s).");
    }

    private function previewPayloadFromBatch(ClientImportBatch $batch): array
    {
        $rows = $batch->rows->map(function (ClientImportRow $row) {
            $payload = $row->normalized_payload ?? [];

            return [
                'row_number' => $row->row_number,
                'row_id' => $row->id,
                'status' => $row->validation_status,
                'review_status' => $row->review_status,
                'raw_payload' => $row->raw_payload ?? [],
                'data' => $payload,
                'duplicate' => $row->clientMatch ? [
                    'id' => $row->clientMatch->id,
                    'name' => $row->clientMatch->name,
                    'phone' => $row->clientMatch->phone,
                    'whatsapp' => $row->clientMatch->whatsapp,
                    'email' => $row->clientMatch->email,
                ] : null,
                'duplicate_status' => $row->duplicate_status ?? 'none',
                'suggestion' => [
                    'segment_code' => $row->suggested_segment_code ?? 'unclassified',
                    'segment_label' => $row->suggested_segment_label ?? 'Unclassified',
                    'follow_up_date' => $row->suggested_next_action_date?->toDateString(),
                    'message' => $row->suggested_message,
                    'secondary_segments' => [],
                ],
                'errors' => $row->errors ?? [],
                'warnings' => $row->warnings ?? [],
            ];
        })->values()->all();

        return [
            'batch' => $batch,
            'reviewSummary' => [
                'pending_review' => $batch->rows->where('review_status', 'pending_review')->count(),
                'approved' => $batch->rows->where('review_status', 'approved')->count(),
                'rejected' => $batch->rows->where('review_status', 'rejected')->count(),
                'skipped' => $batch->rows->where('review_status', 'skipped')->count(),
                'applied' => $batch->rows->where('review_status', 'applied')->count(),
                'invalid' => $batch->rows->where('validation_status', 'invalid')->count(),
                'warning' => $batch->rows->where('validation_status', 'warning')->count(),
            ],
            'headers' => array_keys($rows[0]['data'] ?? []),
            'rows' => $rows,
            'summary' => [
                'rows_uploaded' => $batch->total_rows,
                'rows_previewed' => $batch->meta['rows_previewed'] ?? $batch->rows->count(),
                'valid_rows' => $batch->valid_rows,
                'warning_rows' => $batch->warning_rows,
                'invalid_rows' => $batch->invalid_rows,
                'duplicates' => $batch->duplicate_rows,
                'suggested_retention_actions' => $batch->suggested_retention_actions,
                'truncated' => $batch->meta['truncated'] ?? false,
                'limit' => $batch->meta['limit'] ?? ClientImportRetentionPreviewService::DEFAULT_LIMIT,
            ],
        ];
    }

    private function findImportBatchForCurrentCompany(int $batch): ClientImportBatch
    {
        return ClientImportBatch::query()
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($batch);
    }

    private function findImportRowForBatch(ClientImportBatch $batch, int $row): ClientImportRow
    {
        return ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->findOrFail($row);
    }

    private function refreshImportBatchReviewStatus(ClientImportBatch $batch): void
    {
        $counts = ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->select('review_status', DB::raw('count(*) as total'))
            ->groupBy('review_status')
            ->pluck('total', 'review_status');

        $pending = (int) ($counts['pending_review'] ?? 0);
        $reviewed = (int) ($counts['approved'] ?? 0)
            + (int) ($counts['rejected'] ?? 0)
            + (int) ($counts['skipped'] ?? 0)
            + (int) ($counts['applied'] ?? 0);

        if ($reviewed === 0) {
            $status = 'parsed';
        } elseif ($pending > 0) {
            $status = 'pending_review';
        } else {
            $status = 'reviewed';
        }

        $batch->update(['status' => $status]);
    }

    /**
     * 🗑️ Delete client
     */
    public function destroy(Client $client)
    {
        $this->authorizeClient($client);

        $client->delete();

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    public function notesIndex(Client $client)
    {
        $this->authorizeClient($client);

        $client->load(['notes.creator']);

        return view('admin.clients.notes.index', compact('client'));
    }

    public function storeNote(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        Note::create([
            'company_id' => auth()->user()->company_id,
            'client_id'  => $client->id,
            'content'    => $data['content'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.clients.show', $client->id)
            ->with('success', 'Note added.');
    }

    /**
     * Vehicle makes available for current company clients.
     */
    private function vehicleMakesForCompany(int $companyId)
    {
        if (
            ! Schema::hasTable('vehicles') ||
            ! Schema::hasTable('vehicle_makes') ||
            ! Schema::hasColumn('vehicles', 'make_id')
        ) {
            return collect();
        }

        return DB::table('vehicle_makes')
            ->join('vehicles', 'vehicles.make_id', '=', 'vehicle_makes.id')
            ->join('clients', 'clients.id', '=', 'vehicles.client_id')
            ->where('clients.company_id', $companyId)
            ->where('clients.is_archived', false)
            ->select('vehicle_makes.id', 'vehicle_makes.name')
            ->distinct()
            ->orderBy('vehicle_makes.name')
            ->get();
    }

    /**
     * 🔐 Company guard
     */
    protected function authorizeClient(Client $client): void
    {
        abort_if((int) $client->company_id !== (int) auth()->user()->company_id, 403);
    }
}
