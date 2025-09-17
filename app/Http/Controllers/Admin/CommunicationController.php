<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreCommunicationRequest;
use App\Http\Requests\Communication\UpdateCommunicationRequest;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Models\Opportunity\Opportunity;
use App\Models\Booking\Booking;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = company_id(); // replace with your tenant resolver
        $filters   = $request->only([
            'client_id','lead_id','opportunity_id','booking_id',
            'type','follow_up_required','date_from','date_to','q'
        ]);

        $communications = Communication::with(['client'])
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
        $clients   = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name']);

        $prefill = [
            'client_id'      => $request->get('client_id'),
            'lead_id'        => $request->get('lead_id'),
            'opportunity_id' => $request->get('opportunity_id'),
            'booking_id'     => $request->get('booking_id'),
            'type'           => $request->get('type'),
        ];

        return view('admin.communications.create', compact('clients','prefill'));
    }

    public function store(StoreCommunicationRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = company_id();
        if (empty($data['communication_date'])) {
            $data['communication_date'] = now();
        }

        $comm = Communication::create($data);

        return redirect()
            ->route('admin.communications.show', $comm)
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
        $companyId = company_id();
        $clients   = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name']);

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

    // Embedded lists
    public function clientList(Client $client)
    {
        $this->authorizeCompany($client);

        $communications = Communication::where('company_id', company_id())
            ->where('client_id', $client->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.communications._list', compact('communications'));
    }

    public function leadList(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $communications = Communication::where('company_id', company_id())
            ->where('lead_id', $lead->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.communications._list', compact('communications'));
    }

    public function opportunityList(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $communications = Communication::where('company_id', company_id())
            ->where('opportunity_id', $opportunity->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.communications._list', compact('communications'));
    }

    public function bookingList(Booking $booking)
    {
        $this->authorizeCompany($booking);

        $communications = Communication::where('company_id', company_id())
            ->where('booking_id', $booking->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.communications._list', compact('communications'));
    }

    protected function authorizeCompany($model): void
    {
        if (method_exists($model, 'getAttribute') && $model->getAttribute('company_id') !== company_id()) {
            abort(403);
        }
    }
}
