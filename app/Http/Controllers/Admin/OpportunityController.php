<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Opportunity;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index()
    {
        $opportunities = Opportunity::with(['client', 'lead'])
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(20);

        return view('admin.opportunities.index', compact('opportunities'));
    }

    public function archived()
    {
        $opportunities = Opportunity::onlyTrashed()
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(20);

        return view('admin.opportunities.archived', compact('opportunities'));
    }

    public function restore($id)
    {
        $opportunity = Opportunity::onlyTrashed()
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $opportunity->restore();

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity restored.');
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $leads = Lead::where('company_id', auth()->user()->company_id)->get();
        $vehicles = Vehicle::all();
        $makes = VehicleMake::all();
        $models = VehicleModel::all();

        return view('admin.opportunities.create', compact('clients', 'leads', 'vehicles', 'makes', 'models'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'lead_id'             => 'nullable|exists:leads,id',
            'title'               => 'required|string|max:255',
            'service_type'        => 'nullable|string',
            'stage'               => 'required|in:new,attempting_contact,appointment,offer,closed_won,closed_lost',
            'value'               => 'nullable|numeric',
            'expected_close_date' => 'nullable|date',
            'notes'               => 'nullable|string',
            'source'              => 'nullable|string|max:100',
            'assigned_to'         => 'nullable|integer',
            'priority'            => 'nullable|in:low,medium,high',
            'expected_duration'   => 'nullable|integer',
            'score'               => 'nullable|integer',
            'is_converted'        => 'nullable|boolean',
            'close_reason'        => 'nullable|string',
            'next_follow_up'      => 'nullable|date',
            'vehicle_id'          => 'nullable|exists:vehicles,id',
            'vehicle_make_id'     => 'nullable|exists:vehicle_makes,id',
            'vehicle_model_id'    => 'nullable|exists:vehicle_models,id',
            'other_make'          => 'nullable|string|max:255',
            'other_model'         => 'nullable|string|max:255',
        ]);

        $data['company_id'] = auth()->user()->company_id;
        $data['is_converted'] = $data['is_converted'] ?? false;

        Opportunity::create($data);

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity created.');
    }

    public function edit(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $leads = Lead::where('company_id', auth()->user()->company_id)->get();
        $vehicles = Vehicle::all();
        $makes = VehicleMake::all();
        $models = VehicleModel::all();

        return view('admin.opportunities.edit', compact('opportunity', 'clients', 'leads', 'vehicles', 'makes', 'models'));
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);
        return view('admin.opportunities.show', compact('opportunity'));
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $data = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'lead_id'             => 'nullable|exists:leads,id',
            'title'               => 'required|string|max:255',
            'service_type'        => 'nullable|string',
            'stage'               => 'required|in:new,attempting_contact,appointment,offer,closed_won,closed_lost',
            'value'               => 'nullable|numeric',
            'expected_close_date' => 'nullable|date',
            'notes'               => 'nullable|string',
            'source'              => 'nullable|string|max:100',
            'assigned_to'         => 'nullable|integer',
            'priority'            => 'nullable|in:low,medium,high',
            'expected_duration'   => 'nullable|integer',
            'score'               => 'nullable|integer',
            'is_converted'        => 'nullable|boolean',
            'close_reason'        => 'nullable|string',
            'next_follow_up'      => 'nullable|date',
            'vehicle_id'          => 'nullable|exists:vehicles,id',
            'vehicle_make_id'     => 'nullable|exists:vehicle_makes,id',
            'vehicle_model_id'    => 'nullable|exists:vehicle_models,id',
            'other_make'          => 'nullable|string|max:255',
            'other_model'         => 'nullable|string|max:255',
        ]);

        $data['is_converted'] = $data['is_converted'] ?? false;

        $opportunity->update($data);

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity updated.');
    }

    public function destroy(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);
        $opportunity->delete();

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity deleted.');
    }

    protected function authorizeCompany(Opportunity $opportunity)
    {
        abort_if($opportunity->company_id !== auth()->user()->company_id, 403);
    }
}
