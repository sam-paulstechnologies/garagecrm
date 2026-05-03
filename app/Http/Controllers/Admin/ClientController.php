<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Note;
use App\Models\Job\Job; // ✅ ADDED
use Illuminate\Http\Request;
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

        $clients = Client::where('company_id', $companyId)
            ->where('is_archived', false)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('whatsapp', 'like', "%{$q}%");
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
     * 💾 Store client (logical dedupe only)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'phone'             => 'nullable|string|max:50',
            'whatsapp'          => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:255',
            'dob'               => 'nullable|date',
            'gender'            => ['nullable', Rule::in(['male','female','other'])],
            'address'           => 'nullable|string',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:100',
            'source'            => 'nullable|string|max:255',
            'status'            => 'nullable|string|max:50',
            'notes'             => 'nullable|string',
            'preferred_channel' => ['nullable', Rule::in(['email','phone','whatsapp'])],
            'is_vip'            => 'nullable|boolean',
        ]);

        $companyId = auth()->user()->company_id;

        /** ---------------------------
         * LOGICAL DEDUPE (WARNING ONLY)
         * --------------------------- */
        $possibleDuplicate = Client::where('company_id', $companyId)
            ->where(function ($q) use ($data) {
                if (!empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }
                if (!empty($data['whatsapp'])) {
                    $q->orWhere('whatsapp', $data['whatsapp']);
                }
                if (!empty($data['email'])) {
                    $q->orWhere('email', $data['email']);
                }
            })
            ->first();

        $data['company_id'] = $companyId;
        $data['is_vip']     = $request->boolean('is_vip');

        $client = Client::create($data);

        if ($possibleDuplicate) {
            return redirect()
                ->route('admin.clients.show', $client->id)
                ->with('warning', 'Possible duplicate client detected. Please review.');
        }

        return redirect()
            ->route('admin.clients.index')
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

        /** ✅ SERVICE HISTORY (ADDED) */
        $serviceHistory = Job::where('client_id', $client->id)
            ->where('status', 'completed')
            ->latest('end_time')
            ->take(10)
            ->get();

        /** VEHICLE COUNT */
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

        /** PROFILE SCORE */
        $score = 0;
        if ($client->name) $score += 10;
        if ($client->phone) $score += 10;
        if ($client->email || $client->whatsapp) $score += 10;
        if ($client->city || $client->country || $client->address) $score += 10;
        if ($vehicleCount > 0) $score += 20;
        if ($client->leads?->count()) $score += 10;
        if ($client->opportunities?->count()) $score += 10;
        if ($client->bookings?->count()) $score += 10;
        if ($client->notes?->count()) $score += 10;

        $kpis = [
            'cars'        => $vehicleCount,
            'ltv'         => 0,
            'avg_spend'   => 0,
            'last_service'=> null,
            'next_service'=> null,
            'profile_pct' => min(100, $score),
        ];

        return view('admin.clients.show', compact('client', 'kpis', 'serviceHistory')); // ✅ ADDED
    }

    public function edit(Client $client)
    {
        $this->authorizeClient($client);
        return view('admin.clients.edit', compact('client'));
    }

    /**
     * 🔁 Update client (logical dedupe warning)
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
            'gender'            => ['nullable', Rule::in(['male','female','other'])],
            'address'           => 'nullable|string',
            'city'              => 'nullable|string|max:100',
            'state'             => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:100',
            'source'            => 'nullable|string|max:255',
            'status'            => 'nullable|string|max:50',
            'notes'             => 'nullable|string',
            'preferred_channel' => ['nullable', Rule::in(['email','phone','whatsapp'])],
            'is_vip'            => 'nullable|boolean',
        ]);

        $possibleDuplicate = Client::where('company_id', auth()->user()->company_id)
            ->where('id', '!=', $client->id)
            ->where(function ($q) use ($data) {
                if (!empty($data['phone'])) $q->orWhere('phone', $data['phone']);
                if (!empty($data['whatsapp'])) $q->orWhere('whatsapp', $data['whatsapp']);
                if (!empty($data['email'])) $q->orWhere('email', $data['email']);
            })
            ->first();

        $data['is_vip'] = $request->boolean('is_vip');
        $client->update($data);

        if ($possibleDuplicate) {
            return redirect()
                ->route('admin.clients.show', $client->id)
                ->with('warning', 'Possible duplicate client detected. Please review.');
        }

        return redirect()
            ->route('admin.clients.index')
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
        $clients = Client::where('company_id', auth()->user()->company_id)
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

    /** Notes */
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
        abort_if($client->company_id !== auth()->user()->company_id, 403);
    }
}