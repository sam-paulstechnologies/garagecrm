<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * List vehicles (scoped to the signed-in user's company via client).
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $vehicles = Vehicle::with(['client', 'make', 'model'])
            ->whereHas('client', fn ($q) => $q->where('company_id', $companyId))
            ->latest()
            ->paginate(20);

        return view('admin.vehicles.index', compact('vehicles'));
    }

    /**
     * Create form (optionally preselect client via ?client_id=).
     */
    public function create(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $clients = Client::where('company_id', $companyId)->get(['id', 'name']);
        $makes   = VehicleMake::orderBy('name')->get(['id', 'name']);
        $models  = VehicleModel::orderBy('name')->get(['id', 'name', 'make_id']);

        $prefillClientId = $request->integer('client_id') ?: null;

        return view('admin.vehicles.create', compact('clients', 'makes', 'models', 'prefillClientId'));
    }

    /**
     * Store a new vehicle.
     */
    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id'                 => 'required|exists:clients,id',
            'make_id'                   => 'nullable|exists:vehicle_makes,id',
            'model_id'                  => 'nullable|exists:vehicle_models,id',
            'trim'                      => 'nullable|string|max:100',
            'plate_number'              => 'required|string|max:50',
            'year'                      => 'nullable|string|max:10',
            'color'                     => 'nullable|string|max:50',
            'registration_expiry_date'  => 'nullable|date',
            'insurance_expiry_date'     => 'nullable|date',
        ]);

        // Company guard: ensure the selected client belongs to this company
        $client = Client::where('company_id', $companyId)->findOrFail($data['client_id']);

        $vehicle = Vehicle::create(array_merge($data, [
            'company_id' => $companyId,
        ]));

        return redirect()
            ->route('admin.clients.show', $client->id)
            ->with('success', 'Vehicle added successfully.');
    }

    /**
     * Show a vehicle details page.
     */
    public function show(Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);

        $vehicle->loadMissing(['client', 'make', 'model']);

        return view('admin.vehicles.show', compact('vehicle'));
    }

    /**
     * Edit form.
     */
    public function edit(Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);

        $companyId = auth()->user()->company_id;

        $clients = Client::where('company_id', $companyId)->get(['id', 'name']);
        $makes   = VehicleMake::orderBy('name')->get(['id', 'name']);
        $models  = VehicleModel::orderBy('name')->get(['id', 'name', 'make_id']);

        return view('admin.vehicles.edit', compact('vehicle', 'clients', 'makes', 'models'));
    }

    /**
     * Update vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);

        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id'                 => 'required|exists:clients,id',
            'make_id'                   => 'nullable|exists:vehicle_makes,id',
            'model_id'                  => 'nullable|exists:vehicle_models,id',
            'trim'                      => 'nullable|string|max:100',
            'plate_number'              => 'required|string|max:50',
            'year'                      => 'nullable|string|max:10',
            'color'                     => 'nullable|string|max:50',
            'registration_expiry_date'  => 'nullable|date',
            'insurance_expiry_date'     => 'nullable|date',
        ]);

        // Ensure target client is in same company
        Client::where('company_id', $companyId)->findOrFail($data['client_id']);

        $vehicle->update($data);

        return redirect()
            ->route('admin.clients.show', $vehicle->client_id)
            ->with('success', 'Vehicle updated.');
    }

    /**
     * Delete vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);

        $clientId = $vehicle->client_id;
        $vehicle->delete();

        return redirect()
            ->route('admin.clients.show', $clientId)
            ->with('success', 'Vehicle deleted.');
    }

    /**
     * Lightweight PATCH endpoint to update only renewal dates from the client page.
     */
    public function updateRenewals(Request $request, Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);

        $data = $request->validate([
            'registration_expiry_date'  => 'nullable|date',
            'insurance_expiry_date'     => 'nullable|date',
        ]);

        $vehicle->update($data);

        return back()->with('success', 'Vehicle renewal dates updated.');
    }

    /**
     * Company authorization helper.
     */
    protected function authorizeCompany(Vehicle $vehicle)
    {
        abort_if(
            !$vehicle->relationLoaded('client') && !$vehicle->client()->exists(),
            404
        );

        abort_if($vehicle->client->company_id !== auth()->user()->company_id, 403);
    }
}
