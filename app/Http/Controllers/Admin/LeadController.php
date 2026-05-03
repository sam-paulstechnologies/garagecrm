<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\Leads\LeadResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $q = trim((string) $request->get('q', ''));

        $leads = Lead::query()
            ->with(['client:id,name,phone,email', 'assignee:id,name'])
            ->where('company_id', $companyId)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('source', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('admin.leads.index', compact('leads', 'q'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;

        return view('admin.leads.create', [
            'clients' => Client::where('company_id', $companyId)->orderBy('name')->get(),
            'managers' => User::where('company_id', $companyId)
                ->where('role', 'manager')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, WhatsAppService $whatsapp, LeadResolver $leadResolver)
    {
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'name' => 'required|string|max:255',

            // ✅ FIX: At least one required
            'email' => 'nullable|email|max:150|required_without:phone',
            'phone' => 'nullable|string|max:20|required_without:email',

            'source' => 'nullable|string|max:100',
            'notes' => 'nullable|string',

            // ✅ FIX: Company scoped validation
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId))
            ],

            'client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId))
            ],

            'preferred_channel' => 'nullable|in:email,phone,whatsapp',
        ]);

        $data['company_id'] = $companyId;
        $data['status'] = 'new';
        $data['source'] = $data['source'] ?? 'Manual';

        DB::transaction(function () use ($data, $leadResolver, $companyId, &$lead, &$messageType, &$messageText) {

            $lead = $leadResolver->resolve([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'source' => $data['source'],
            ], $companyId);

            if (!$lead) {
                throw new \Exception('Lead creation failed');
            }

            // ✅ UX message
            if ($lead->wasRecentlyCreated) {
                $messageType = 'success';
                $messageText = '✅ New lead created successfully.';
            } else {
                if ($lead->is_active) {
                    $messageType = 'warning';
                    $messageText = '⚠️ Active lead already exists. Reusing existing record.';
                } else {
                    $messageType = 'success';
                    $messageText = '🔁 Previous enquiry closed. New lead created.';
                }
            }

            // Update optional fields
            $lead->update([
                'assigned_to' => $data['assigned_to'] ?? $lead->assigned_to,
                'notes' => $data['notes'] ?? $lead->notes,
                'preferred_channel' => $data['preferred_channel'] ?? $lead->preferred_channel,
            ]);

            // ✅ FIX: Convert ONLY if new lead
            if ($lead->wasRecentlyCreated) {
                $this->convertToOpportunity($lead);
            }
        });

        session()->flash($messageType, $messageText);

        return redirect()->route('admin.leads.index');
    }

    public function show(Lead $lead)
    {
        $this->authorizeCompany($lead);

        return view('admin.leads.show', [
            'lead' => $lead,
            'communications' => Communication::where('lead_id', $lead->id)->latest()->paginate(10),
            'messageLogs' => \App\Models\MessageLog::where('lead_id', $lead->id)->latest()->paginate(10),
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $companyId = auth()->user()->company_id;

        return view('admin.leads.edit', [
            'lead' => $lead,
            'clients' => Client::where('company_id', $companyId)->get(),
            'managers' => User::where('company_id', $companyId)
                ->where('role', 'manager')
                ->get(),
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead);

        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'name' => 'required|string|max:255',

            'email' => 'nullable|email|max:150|required_without:phone',
            'phone' => 'nullable|string|max:20|required_without:email',

            'source' => 'nullable|string|max:100',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId))
            ],

            'client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId))
            ],
        ]);

        $lead->update($data);

        return redirect()->route('admin.leads.show', $lead);
    }

    protected function authorizeCompany(Lead $lead): void
    {
        abort_if($lead->company_id !== auth()->user()->company_id, 403);
    }

    protected function convertToOpportunity(Lead $lead): void
    {
        // ✅ SAFE CLIENT CREATION
        $clientQuery = Client::where('company_id', $lead->company_id);

        if ($lead->phone_norm) {
            $clientQuery->where('phone', $lead->phone_norm);
        } elseif ($lead->email) {
            $clientQuery->where('email', $lead->email);
        }

        $client = $clientQuery->first();

        if (!$client) {
            $client = Client::create([
                'company_id' => $lead->company_id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone_norm,
            ]);
        }

        Opportunity::firstOrCreate(
            [
                'lead_id' => $lead->id,
                'company_id' => $lead->company_id,
            ],
            [
                'client_id' => $client->id,
                'title' => 'Lead: ' . ($lead->name ?? 'New Opportunity'),
                'stage' => 'new',
                'source' => $lead->source,
            ]
        );

        // deactivate others safely
        if ($lead->phone_norm) {
            Lead::where('company_id', $lead->company_id)
                ->where('phone_norm', $lead->phone_norm)
                ->where('id', '!=', $lead->id)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        }

        $lead->update([
            'is_active' => 0,
            'status' => 'converted'
        ]);
    }
}