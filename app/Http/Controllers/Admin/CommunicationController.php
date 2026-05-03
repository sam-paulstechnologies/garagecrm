<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreCommunicationRequest;
use App\Http\Requests\Communication\UpdateCommunicationRequest;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Models\Opportunity\Opportunity;
use App\Models\Job\Booking;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = company_id();

        $filters = $request->only([
            'client_id','lead_id','opportunity_id','booking_id',
            'type','follow_up_required','date_from','date_to','q'
        ]);

        $communications = Communication::with('client')
            ->forCompany($companyId)
            ->filter($filters)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $clients = Client::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.communications.index', compact('communications','clients','filters'));
    }

    public function create(Request $request)
    {
        $companyId = company_id();

        $clients = Client::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id','name']);

        $prefill = $request->only([
            'client_id','lead_id','opportunity_id','booking_id','type'
        ]);

        return view('admin.communications.create', compact('clients','prefill'));
    }

    public function store(StoreCommunicationRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = company_id();
        $data['communication_date'] ??= now();

        $communication = Communication::create($data);

        return redirect()
            ->route('admin.communications.show', $communication)
            ->with('success', 'Communication logged successfully.');
    }

    public function show(Communication $communication)
    {
        $this->authorizeCompany($communication);
        $communication->load('client');

        return view('admin.communications.show', compact('communication'));
    }

    public function edit(Communication $communication)
    {
        $this->authorizeCompany($communication);

        $clients = Client::where('company_id', company_id())
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.communications.edit', compact('communication','clients'));
    }

    public function update(UpdateCommunicationRequest $request, Communication $communication)
    {
        $this->authorizeCompany($communication);
        $communication->update($request->validated());

        return redirect()
            ->route('admin.communications.show', $communication)
            ->with('success', 'Communication updated.');
    }

    public function destroy(Communication $communication)
    {
        $this->authorizeCompany($communication);
        $communication->delete();

        return redirect()
            ->route('admin.communications.index')
            ->with('success', 'Communication deleted.');
    }

    /* ============================================================
     | FOLLOW-UPS DASHBOARD (R1 – UAT CRITICAL)
     | Shows all pending follow-ups
     ============================================================ */
    public function followUps()
    {
        $communications = Communication::with('client')
            ->forCompany(company_id())
            ->pendingFollowups()
            ->orderBy('communication_date')
            ->paginate(20);

        return view('admin.communications.followups', compact('communications'));
    }

    /* ✅ FOLLOW-UP COMPLETE */
    public function complete(Communication $communication)
    {
        $this->authorizeCompany($communication);

        $communication->update([
            'completed_at'       => now(),
            'follow_up_required' => false,
        ]);

        return back()->with('success', 'Follow-up marked as completed.');
    }

    protected function authorizeCompany($model): void
    {
        abort_if($model->company_id !== company_id(), 403);
    }
}
