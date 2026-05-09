<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Note;
use App\Models\Job\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $clients = Client::with([
                'vehicles.make',
                'vehicles.model',
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', false)
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

        return view('admin.clients.index', compact('clients', 'q'));
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
    public function show(Client $client)
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

        /*
        |--------------------------------------------------------------------------
        | Garage Customer Profile Completion Score
        |--------------------------------------------------------------------------
        | Total = 100
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Service History + Next Service Logic
        |--------------------------------------------------------------------------
        | Current rule:
        | - Last Service = latest completed job end_time
        | - Next Service = Last Service + 6 months
        |--------------------------------------------------------------------------
        */
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

        return view('admin.clients.show', compact('client', 'kpis', 'serviceHistory'));
    }

    public function edit(Client $client)
    {
        $this->authorizeClient($client);

        return view('admin.clients.edit', compact('client'));
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

    public function archived()
    {
        $clients = Client::with([
                'vehicles.make',
                'vehicles.model',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->where('is_archived', true)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.clients.archived', compact('clients'));
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
     * 🔐 Company guard
     */
    protected function authorizeClient(Client $client): void
    {
        abort_if((int) $client->company_id !== (int) auth()->user()->company_id, 403);
    }
}