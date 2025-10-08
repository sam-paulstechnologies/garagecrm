<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\LeadCreated;

class LeadController extends Controller
{
    /** 📄 List leads with search, filters, and pagination */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $q         = trim((string) $request->get('q', ''));
        $status    = $request->get('status');
        $source    = $request->get('source');
        $assignee  = $request->get('assigned_to');
        $isHot     = $request->has('is_hot') ? (int) $request->boolean('is_hot') : null;
        $order     = $request->get('order', 'latest');

        $leads = Lead::query()
            ->with(['client:id,name,phone,email', 'assignee:id,name'])
            ->where('company_id', $companyId)
            ->when($q, fn($query) => $query->where(fn($sub) => $sub
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('notes', 'like', "%{$q}%")
                ->orWhere('source', 'like', "%{$q}%")))
            ->when($status, fn($q2) => $q2->where('status', $status))
            ->when($source, fn($q3) => $q3->where('source', $source))
            ->when($assignee, fn($q4) => $q4->where('assigned_to', $assignee))
            ->when(!is_null($isHot), fn($q5) => $q5->where('is_hot', (bool)$isHot))
            ->when($order === 'score', fn($q6) => $q6->orderByDesc('lead_score')->latest('id'))
            ->when($order === 'last_contact', fn($q7) => $q7->orderByDesc('last_contacted_at')->latest('id'))
            ->when($order === 'latest', fn($q8) => $q8->latest())
            ->paginate(20)
            ->withQueryString();

        return view('admin.leads.index', compact('leads', 'q', 'status', 'source', 'assignee', 'isHot', 'order'));
    }

    /** ➕ Create form */
    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get(['id','name','phone','email']);

        return view('admin.leads.create', compact('clients'));
    }

    /** 💾 Store lead (auto-convert if status=qualified) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => ['required','string','max:255'],
            'email'              => ['nullable','email','max:150'],
            'phone'              => ['nullable','string','max:20'],
            'status'             => ['required','string','max:50'],
            'source'             => ['nullable','string','max:100'],
            'notes'              => ['nullable','string'],
            'assigned_to'        => ['nullable','integer'],
            'lead_score_reason'  => ['nullable','string'],
            'preferred_channel'  => ['nullable','in:email,phone,whatsapp'],
            'is_hot'             => ['nullable','boolean'],
            'client_id'          => ['nullable','exists:clients,id'],
            'last_contacted_at'  => ['nullable','date'],
        ]);

        $data['company_id'] = auth()->user()->company_id;
        $newStatus = strtolower((string) ($data['status'] ?? ''));

        $lead = null;

        DB::transaction(function () use (&$lead, $data, $newStatus) {
            $lead = Lead::create($data);

            if (method_exists($lead, 'calculateScore')) {
                $lead->calculateScore();
            }

            if ($newStatus === 'qualified') {
                $this->convertToOpportunity($lead);
            }
        });

        event(new LeadCreated($lead));

        return redirect()->route('admin.leads.index')->with('success', 'Lead created.');
    }

    /** ✏️ Edit form */
    public function edit(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get(['id','name','phone','email']);

        return view('admin.leads.edit', compact('lead', 'clients'));
    }

    /** 🔁 Update lead (auto-convert when moving into qualified) */
    public function update(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead);

        $data = $request->validate([
            'name'               => ['required','string','max:255'],
            'email'              => ['nullable','email','max:150'],
            'phone'              => ['nullable','string','max:20'],
            'status'             => ['required','string','max:50'],
            'source'             => ['nullable','string','max:100'],
            'notes'              => ['nullable','string'],
            'assigned_to'        => ['nullable','integer'],
            'lead_score_reason'  => ['nullable','string'],
            'preferred_channel'  => ['nullable','in:email,phone,whatsapp'],
            'is_hot'             => ['nullable','boolean'],
            'client_id'          => ['nullable','exists:clients,id'],
            'last_contacted_at'  => ['nullable','date'],
        ]);

        $oldStatus = strtolower((string) $lead->status);
        $newStatus = strtolower((string) $data['status']);

        DB::transaction(function () use ($lead, $data, $oldStatus, $newStatus) {
            $lead->update($data);

            if (method_exists($lead, 'calculateScore')) {
                $lead->calculateScore();
            }

            if ($oldStatus !== 'qualified' && $newStatus === 'qualified') {
                $this->convertToOpportunity($lead);
            }
        });

        return redirect()->route('admin.leads.index')->with('success', 'Lead updated.');
    }

    /** 🗑️ Delete */
    public function destroy(Lead $lead)
    {
        $this->authorizeCompany($lead);
        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted.');
    }

    /** 👁️ Show a single lead (+ communications) */
    public function show(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $lead->loadMissing([
            'client:id,name,phone,email',
            'opportunity',
            'assignee:id,name',
        ]);

        $communications = Communication::query()
            ->forCompany(auth()->user()->company_id)
            ->where('lead_id', $lead->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.leads.show', compact('lead', 'communications'));
    }

    /** ⭐ Toggle "hot" flag (AJAX or normal) */
    public function toggleHot(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $lead->update(['is_hot' => !$lead->is_hot]);

        return request()->expectsJson()
            ? response()->json(['is_hot' => (bool) $lead->is_hot])
            : back()->with('success', 'Lead hot flag updated.');
    }

    /** 👤 Assign/Reassign lead owner (AJAX or normal) */
    public function assign(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead);

        $data = $request->validate([
            'assigned_to' => ['nullable','integer'],
        ]);

        $lead->update(['assigned_to' => $data['assigned_to'] ?? null]);

        return $request->expectsJson()
            ? response()->json(['assigned_to' => $lead->assigned_to])
            : back()->with('success', 'Lead assigned.');
    }

    /** 🔄 Manual conversion endpoint */
    public function convert(Lead $lead)
    {
        $this->authorizeCompany($lead);

        DB::transaction(function () use ($lead) {
            $this->convertToOpportunity($lead);
        });

        return request()->expectsJson()
            ? response()->json(['message' => 'Lead converted.'])
            : back()->with('success', 'Lead converted.');
    }

    /** ☎️ Touch last_contacted_at = now (AJAX or normal) */
    public function touchContacted(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $lead->update(['last_contacted_at' => now()]);

        return request()->expectsJson()
            ? response()->json(['last_contacted_at' => $lead->last_contacted_at])
            : back()->with('success', 'Last contacted time updated.');
    }

    /** 🔐 Company guard */
    protected function authorizeCompany(Lead $lead): void
    {
        abort_if($lead->company_id !== auth()->user()->company_id, 403);
    }

    /** 🧠 Core conversion logic */
    protected function convertToOpportunity(Lead $lead): void
    {
        $companyId = $lead->company_id;

        if (!$lead->client_id) {
            $client = Client::create([
                'name'        => $lead->name,
                'email'       => $lead->email,
                'phone'       => $lead->phone,
                'location'    => null,
                'last_service'=> null,
                'source'      => $lead->source ?? 'Lead',
                'company_id'  => $companyId,
            ]);
            $lead->client_id = $client->id;
            $lead->save();
        }

        Opportunity::firstOrCreate(
            [
                'company_id' => $companyId,
                'lead_id'    => $lead->id,
            ],
            [
                'client_id'   => $lead->client_id,
                'title'       => 'Opportunity: ' . ($lead->name ?: 'New') . ' - ' . Str::limit(($lead->source ?? 'Lead'), 30),
                'stage'       => 'new',
                'amount'      => 0,
                'notes'       => $lead->notes,
                'assigned_to' => $lead->assigned_to,
            ]
        );

        $lead->status = 'converted';
        $lead->save();
    }
}
