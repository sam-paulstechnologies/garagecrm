<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Client\Client;
use Illuminate\Http\Request;

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

        $lead = Lead::create($data);
        $lead->calculateScore();

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

        $lead->update($data);
        $lead->calculateScore();

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
