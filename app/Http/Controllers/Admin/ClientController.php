<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Note;
use App\Models\Job\Job;
use App\Services\Retention\VehicleRenewalOpportunityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('warning', 'Client import screen is available, but import processing is not configured yet.');
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
