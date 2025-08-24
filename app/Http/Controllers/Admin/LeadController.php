<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Lead::with('client')
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(20);

        return view('admin.leads.index', compact('leads'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        return view('admin.leads.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'nullable|email|max:150',
            'phone'              => 'nullable|string|max:20',
            'status'             => 'required|string',
            'source'             => 'nullable|string|max:100',
            'notes'              => 'nullable|string',
            'assigned_to'        => 'nullable|integer',
            'lead_score_reason'  => 'nullable|string',
            'preferred_channel'  => 'nullable|string|in:email,phone,whatsapp',
            'is_hot'             => 'boolean',
            'client_id'          => 'nullable|exists:clients,id',
            'last_contacted_at'  => 'nullable|date',
        ]);

        $data['company_id'] = auth()->user()->company_id;

        $companyId = $data['company_id'];
        $newStatus = strtolower((string)($data['status'] ?? ''));

        DB::transaction(function () use (&$lead, $data, $companyId, $newStatus) {
            // 1) Create lead
            $lead = Lead::create($data);
            $lead->calculateScore();

            // 2) If created directly as qualified, auto-convert
            if ($newStatus === 'qualified') {
                // Ensure a client
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

                // Create (or get) an opportunity linked to this lead
                Opportunity::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'lead_id'    => $lead->id, // if FK exists
                    ],
                    [
                        'client_id'   => $lead->client_id,
                        'title'       => 'Opportunity: ' . ($lead->name ?: 'New') . ' - ' . Str::limit(($lead->source ?? 'Lead'), 30),
                        'stage'       => 'new',       // adjust to your default
                        'amount'      => 0,           // adjust/remove if not in schema
                        'notes'       => $lead->notes,
                        'assigned_to' => $lead->assigned_to,
                    ]
                );

                // Flip lead to converted
                $lead->status = 'converted';
                $lead->save();
            }
        });

        return redirect()->route('admin.leads.index')->with('success', 'Lead created.');
    }

    public function edit(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        return view('admin.leads.edit', compact('lead', 'clients'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead);

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'nullable|email|max:150',
            'phone'              => 'nullable|string|max:20',
            'status'             => 'required|string',
            'source'             => 'nullable|string|max:100',
            'notes'              => 'nullable|string',
            'assigned_to'        => 'nullable|integer',
            'lead_score_reason'  => 'nullable|string',
            'preferred_channel'  => 'nullable|string|in:email,phone,whatsapp',
            'is_hot'             => 'boolean',
            'client_id'          => 'nullable|exists:clients,id',
            'last_contacted_at'  => 'nullable|date',
        ]);

        $companyId = auth()->user()->company_id;
        $oldStatus = strtolower((string) $lead->status);
        $newStatus = strtolower((string) $data['status']);

        DB::transaction(function () use ($lead, $data, $companyId, $oldStatus, $newStatus) {
            // 1) Update the lead
            $lead->update($data);
            $lead->calculateScore();

            // 2) Transition to qualified -> auto-convert
            $justQualified = $oldStatus !== 'qualified' && $newStatus === 'qualified';

            if ($justQualified) {
                // Ensure a client
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
                }

                // Create (or get) an opportunity for this lead
                Opportunity::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'lead_id'    => $lead->id, // if FK exists
                    ],
                    [
                        'client_id'   => $lead->client_id,
                        'title'       => 'Opportunity: ' . ($lead->name ?: 'New') . ' - ' . Str::limit(($lead->source ?? 'Lead'), 30),
                        'stage'       => 'new',       // adjust to your default
                        'amount'      => 0,           // adjust/remove if not in schema
                        'notes'       => $lead->notes,
                        'assigned_to' => $lead->assigned_to,
                    ]
                );

                // Flip lead to converted
                $lead->status = 'converted';
                $lead->save();
            }
        });

        return redirect()->route('admin.leads.index')->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorizeCompany($lead);
        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted.');
    }

    public function show(Lead $lead)
    {
        $this->authorizeCompany($lead);

        return view('admin.leads.show', compact('lead'));
    }

    protected function authorizeCompany(Lead $lead)
    {
        abort_if($lead->company_id !== auth()->user()->company_id, 403);
    }
}
