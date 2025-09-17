<?php

namespace App\Http\Controllers;

use App\Http\Requests\Communication\StoreCommunicationRequest;
use App\Http\Requests\Communication\UpdateCommunicationRequest;
use App\Models\Client\Client;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    // Global Index (admin view)
    public function index(Request $request)
    {
        $companyId = company_id(); // helper from your BelongsToCompany trait/ecosystem
        $filters   = $request->only(['client_id','type','follow_up_required','date_from','date_to','q']);

        $communications = Communication::with(['client'])
            ->forCompany($companyId)
            ->filter($filters)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name']);

        return view('communications.index', compact('communications', 'clients', 'filters'));
    }

    // Create (global)
    public function create(Request $request)
    {
        $companyId = company_id();
        $clients   = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name']);
        $prefill   = [
            'client_id' => $request->get('client_id'),
            'type'      => $request->get('type'),
        ];

        return view('communications.create', compact('clients', 'prefill'));
    }

    public function store(StoreCommunicationRequest $request)
    {
        $data = $request->validated();

        // ensure company scoping
        $data['company_id'] = company_id();

        // default date if not provided
        if (empty($data['communication_date'])) {
            $data['communication_date'] = now();
        }

        $comm = Communication::create($data);

        return redirect()
            ->route('communications.show', $comm)
            ->with('success', 'Communication logged successfully.');
    }

    public function show(Communication $communication)
    {
        $this->authorizeCompany($communication);
        $communication->load('client');

        return view('communications.show', compact('communication'));
    }

    public function edit(Communication $communication)
    {
        $this->authorizeCompany($communication);
        $companyId = company_id();
        $clients   = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name']);

        return view('communications.edit', compact('communication', 'clients'));
    }

    public function update(UpdateCommunicationRequest $request, Communication $communication)
    {
        $this->authorizeCompany($communication);
        $communication->update($request->validated());

        return redirect()
            ->route('communications.show', $communication)
            ->with('success', 'Communication updated.');
    }

    public function destroy(Communication $communication)
    {
        $this->authorizeCompany($communication);
        $communication->delete();

        return redirect()
            ->route('communications.index')
            ->with('success', 'Communication deleted.');
    }

    // Embed list for Client tab (partial endpoint – optional)
    public function clientTimeline(Client $client, Request $request)
    {
        // Only for embed/tab usage
        $this->authorizeCompany($client);
        $communications = Communication::where('company_id', company_id())
            ->where('client_id', $client->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('communications._list', compact('communications'));
    }

    protected function authorizeCompany($model): void
    {
        if (method_exists($model, 'getAttribute') && $model->getAttribute('company_id') !== company_id()) {
            abort(403);
        }
    }
}
