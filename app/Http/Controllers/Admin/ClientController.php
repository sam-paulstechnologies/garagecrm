<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Note;
use App\Models\Vehicle\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Imports\ClientImport;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    /**
     * 📄 List all active clients with simple search & filters
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $q         = trim((string) $request->get('q', ''));
        $vip       = $request->filled('vip') ? (bool) $request->boolean('vip') : null;
        $status    = $request->get('status'); // optional free-text status filter

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('whatsapp', 'like', "%{$q}%")
                        ->orWhere('location', 'like', "%{$q}%");
                });
            })
            ->when(!is_null($vip), fn($q2) => $q2->where('is_vip', $vip))
            ->when($status, fn($q3) => $q3->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', compact('clients', 'q', 'vip', 'status'));
    }

    /**
     * ➕ Show create form
     */
    public function create()
    {
        return view('admin.clients.create');
    }

    /**
     * 💾 Store new client (AJAX or normal)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required','string','max:255'],
            'email'             => ['nullable','email','max:255'],
            'phone'             => ['nullable','string','max:20'],
            'whatsapp'          => ['nullable','string','max:20'],
            'location'          => ['nullable','string','max:255'],
            'preferred_channel' => ['nullable','string','max:50'], // Call/WhatsApp/Email/SMS
            'gender'            => ['nullable', Rule::in(['male','female','other'])],
            'dob'               => ['nullable','date'],
            'address'           => ['nullable','string','max:255'],
            'city'              => ['nullable','string','max:255'],
            'state'             => ['nullable','string','max:255'],
            'postal_code'       => ['nullable','string','max:50'],
            'country'           => ['nullable','string','max:100'],
            'source'            => ['nullable','string','max:255'],
            'status'            => ['nullable','string','max:255'],
            'notes'             => ['nullable','string'],
            'is_vip'            => ['nullable','boolean'],
        ]);

        $data['company_id'] = auth()->user()->company_id;
        $data['is_vip']     = $request->boolean('is_vip');

        $client = Client::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'id'    => $client->id,
                'name'  => $client->name,
                'phone' => $client->phone,
            ]);
        }

        return redirect()->route('admin.clients.index')->with('success', 'Client created successfully.');
    }

    /**
     * ✏️ Show edit form
     */
    public function edit(Client $client)
    {
        $this->authorizeClient($client);
        return view('admin.clients.edit', compact('client'));
    }

    /**
     * 🔁 Update client
     */
    public function update(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'name'              => ['required','string','max:255'],
            'email'             => ['nullable','email','max:255'],
            'phone'             => ['nullable','string','max:20'],
            'whatsapp'          => ['nullable','string','max:20'],
            'location'          => ['nullable','string','max:255'],
            'preferred_channel' => ['nullable','string','max:50'],
            'gender'            => ['nullable', Rule::in(['male','female','other'])],
            'dob'               => ['nullable','date'],
            'address'           => ['nullable','string','max:255'],
            'city'              => ['nullable','string','max:255'],
            'state'             => ['nullable','string','max:255'],
            'postal_code'       => ['nullable','string','max:50'],
            'country'           => ['nullable','string','max:100'],
            'source'            => ['nullable','string','max:255'],
            'status'            => ['nullable','string','max:255'],
            'notes'             => ['nullable','string'],
            'is_vip'            => ['nullable','boolean'],
        ]);

        $data['is_vip'] = $request->boolean('is_vip');

        $client->update($data);

        return redirect()->route('admin.clients.index')->with('success', 'Client updated successfully.');
    }

    /**
     * ⭐ Quick toggle VIP (AJAX or normal)
     */
    public function toggleVip(Client $client)
    {
        $this->authorizeClient($client);

        $client->update(['is_vip' => !$client->is_vip]);

        if (request()->expectsJson()) {
            return response()->json(['is_vip' => $client->is_vip]);
        }

        return back()->with('success', 'VIP status updated.');
    }

    /**
     * 🗃️ Soft delete (archive)
     */
    public function archive($id)
    {
        $client = Client::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $client->update(['is_archived' => true]);

        return redirect()->route('admin.clients.index')->with('success', 'Client archived successfully.');
    }

    /**
     * 🗂️ View archived clients
     */
    public function archived(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $q = trim((string) $request->get('q', ''));

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->where('is_archived', true)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.archived', compact('clients', 'q'));
    }

    /**
     * ♻️ Restore archived client
     */
    public function restore($id)
    {
        $client = Client::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $client->update(['is_archived' => false]);

        return redirect()->route('admin.clients.archived')->with('success', 'Client restored successfully.');
    }

    /**
     * 👁️ Show client details — 360° profile + KPIs
     */
    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $client->loadMissing([
            // Vehicles + make/model
            'vehicles.make',
            'vehicles.model',

            // Opportunities (with make/model)
            'opportunities'              => fn($q) => $q->latest(),
            'opportunities.vehicleMake',
            'opportunities.vehicleModel',

            // Related panels
            'leads'         => fn($q) => $q->latest(),
            'jobs'          => fn($q) => $q->latest('start_time'),
            'invoices'      => fn($q) => $q->latest(),
            'files',
            'notes'         => fn($q) => $q->latest(), // you can limit to 3 in the view if you want
        ]);

        // ---- KPIs ----
        $lifetimeValue = (float) $client->invoices->sum(fn($inv) => (float) ($inv->total ?? $inv->amount ?? 0));

        $visits   = max(1, $client->jobs->count());
        $avgSpend = round($lifetimeValue / $visits, 2);

        $lastService = optional(
            $client->jobs->filter(fn($j) => !empty($j->start_time))
                ->sortByDesc('start_time')
                ->first()
        )->start_time;

        $nextService = optional(
            $client->vehicles->filter(fn($v) => !empty($v->registration_expiry_date))
                ->sortBy('registration_expiry_date')
                ->first()
        )->registration_expiry_date;

        $fields = [
            $client->name,
            $client->phone,
            $client->email,
            $client->preferred_channel,
        ];
        $score = 0; $total = count($fields) + 2;
        foreach ($fields as $f) { if (!empty($f)) $score++; }
        if ($client->vehicles->isNotEmpty()) $score++;
        if ($client->leads->isNotEmpty() || $client->opportunities->isNotEmpty()) $score++;
        $profilePct = (int) round(($score / max(1, $total)) * 100);

        $kpis = [
            'cars'         => $client->vehicles->count(),
            'ltv'          => $lifetimeValue,
            'avg_spend'    => $avgSpend,
            'last_service' => $lastService,
            'next_service' => $nextService,
            'profile_pct'  => $profilePct,
        ];

        return view('admin.clients.show', compact('client', 'kpis'));
    }

    /**
     * 📝 Inline "Add Note" from the client show page (AJAX or normal)
     */
    public function storeNote(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'content' => ['required','string','max:2000'],
        ]);

        $note = Note::create([
            'company_id'  => auth()->user()->company_id,
            'client_id'   => $client->id,
            'content'     => $data['content'],
            'created_by'  => auth()->id(),
            'author_name' => optional(auth()->user())->name, // optional: keeps name even if user is later deleted
        ]);

        if ($request->expectsJson()) {
            // return the freshest 3 for UI to refresh “Recent notes”
            $recent = $client->notes()
                ->where('company_id', auth()->user()->company_id)
                ->with(['creator:id,name'])
                ->latest()
                ->take(3)
                ->get();

            return response()->json([
                'message' => 'Note added.',
                'note'    => $note->loadMissing('creator:id,name'),
                'recent'  => $recent,
            ]);
        }

        return back()->with('success', 'Note added.');
    }

    /**
     * 📒 Paginated Notes tab (View all)
     */
    public function notesIndex(Client $client)
    {
        $this->authorizeClient($client);

        $notes = $client->notes()
            ->where('company_id', auth()->user()->company_id)
            ->with(['creator:id,name'])   // creator() relation on Note
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.notes.index', compact('client', 'notes'));
    }

    /**
     * 🚗 Quick PATCH for vehicle renewals from client show page
     */
    public function updateVehicleRenewals(Request $request, Vehicle $vehicle)
    {
        // Company-guard through the vehicle's client
        $client = $vehicle->client()->first();
        abort_if(!$client || $client->company_id !== auth()->user()->company_id, 403);

        $data = $request->validate([
            'registration_expiry_date' => ['nullable','date'],
            'insurance_expiry_date'    => ['nullable','date'],
        ]);

        $vehicle->update($data);

        return back()->with('success', 'Vehicle renewal dates updated.');
    }

    /**
     * 📥 Show import form
     */
    public function importForm()
    {
        return view('admin.clients.import');
    }

    /**
     * 📤 Handle import with feedback (success/skip/total)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,csv,txt'],
        ]);

        $importer = new ClientImport(auth()->user()->company_id);

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.clients.index')
                ->with('import_error', true)
                ->with('error_message', $e->getMessage());
        }

        return redirect()->route('admin.clients.index')
            ->with('import_success', true)
            ->with('imported', $importer->imported)
            ->with('skipped', $importer->skipped)
            ->with('total', $importer->total);
    }

    /**
     * 🔐 Restrict access by company
     */
    protected function authorizeClient(Client $client): void
    {
        abort_if($client->company_id !== auth()->user()->company_id, 403);
    }
}
