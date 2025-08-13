<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadManagementController extends Controller
{
    public function index()
    {
        $leads = Lead::where('company_id', Auth::user()->company_id)->get();
        return view('admin.leads.index', compact('leads'));
    }

    public function create()
    {
        return view('admin.leads.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateLead($request);

        $lead = Lead::create(array_merge(
            $validated,
            ['company_id' => Auth::user()->company_id]
        ));

        $lead->calculateScore();

        // Convert to client if qualified
        if ($lead->status === 'qualified') {
            $lead->convertToClient();
        }

        return redirect()->route('admin.leads.index')->with('success', 'Lead created successfully.');
    }

    public function edit($id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorizeLead($lead);

        return view('admin.leads.edit', compact('lead'));
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorizeLead($lead);

        $validated = $this->validateLead($request);
        $lead->update($validated);
        $lead->calculateScore();

        if ($lead->status === 'qualified' && !$lead->client_id) {
            $lead->convertToClient();
        }

        return redirect()->route('admin.leads.index')->with('success', 'Lead updated successfully.');
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $this->authorizeLead($lead);

        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted successfully.');
    }

    private function validateLead(Request $request): array
    {
        return $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'nullable|email|max:150',
            'phone'             => 'nullable|string|max:20',
            'status'            => 'required|in:new,attempting_contact,contact_on_hold,qualified,disqualified,converted',
            'source'            => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'assigned_to'       => 'nullable|integer',
            'lead_score_reason' => 'nullable|string',
            'last_contacted_at' => 'nullable|date',
            'preferred_channel' => 'nullable|in:email,phone,whatsapp',
            'is_hot'            => 'nullable|boolean',
        ]);
    }

    private function authorizeLead(Lead $lead)
    {
        if ($lead->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
