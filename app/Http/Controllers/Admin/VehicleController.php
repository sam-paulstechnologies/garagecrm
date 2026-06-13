<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $vehicles = Vehicle::with(['client', 'make', 'model'])
            ->whereHas('client', fn ($q) => $q->where('company_id', $companyId))
            ->latest()
            ->paginate(20);

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        $makes   = VehicleMake::orderBy('name')->get(['id', 'name']);
        $models  = VehicleModel::orderBy('name')->get(['id', 'name', 'make_id']);

        $prefillClientId = $request->integer('client_id');

        return view('admin.vehicles.create', compact(
            'clients', 'makes', 'models', 'prefillClientId'
        ));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id'                => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
            'make_id'                  => 'nullable|exists:vehicle_makes,id',
            'model_id'                 => 'nullable|exists:vehicle_models,id',
            'plate_number'             => 'nullable|string|max:100',
            'vin'                      => 'nullable|string|max:17',
            'year'                     => 'nullable|string|max:10',
            'color'                    => 'nullable|string|max:50',
            'registration_expiry_date' => 'nullable|date',
            'insurance_expiry_date'    => 'nullable|date',
            'last_inspection_date'     => 'nullable|date|before_or_equal:today',
            'inspection_expiry_date'   => 'nullable|date',
            'current_mileage'          => 'nullable|integer|min:0'
        ], [
            'last_inspection_date.before_or_equal' => 'Last inspection date cannot be in the future.',
        ]);

        $client = Client::where('company_id', $companyId)
            ->findOrFail($data['client_id']);

        Vehicle::create(array_merge($data, [
            'company_id' => $companyId,
        ]));

        return redirect()
            ->route('admin.clients.show', $client->id)
            ->with('success', 'Vehicle added successfully.');
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);

        $vehicle->load([
            'client',
            'make',
            'model',
        ]);

        return view('admin.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);

        $companyId = auth()->user()->company_id;

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        $makes   = VehicleMake::orderBy('name')->get(['id', 'name']);
        $models  = VehicleModel::orderBy('name')->get(['id', 'name', 'make_id']);

        return view('admin.vehicles.edit', compact(
            'vehicle', 'clients', 'makes', 'models'
        ));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);

        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id'                => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
            'make_id'                  => 'nullable|exists:vehicle_makes,id',
            'model_id'                 => 'nullable|exists:vehicle_models,id',
            'plate_number'             => 'nullable|string|max:100',
            'vin'                      => 'nullable|string|max:17',
            'year'                     => 'nullable|string|max:10',
            'color'                    => 'nullable|string|max:50',
            'registration_expiry_date' => 'nullable|date',
            'insurance_expiry_date'    => 'nullable|date',
            'last_inspection_date'     => 'nullable|date|before_or_equal:today',
            'inspection_expiry_date'   => 'nullable|date',
            'current_mileage'          => 'nullable|integer|min:0'
        ], [
            'last_inspection_date.before_or_equal' => 'Last inspection date cannot be in the future.',
        ]);

        Client::where('company_id', $companyId)
            ->findOrFail($data['client_id']);

        $vehicle->update($data);

        return redirect()
            ->route('admin.clients.show', $vehicle->client_id)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);

        $clientId = $vehicle->client_id;
        $vehicle->delete();

        return redirect()
            ->route('admin.clients.show', $clientId)
            ->with('success', 'Vehicle deleted.');
    }

    protected function authorizeVehicle(Vehicle $vehicle): void
    {
        $vehicle->loadMissing('client');

        abort_if(
            optional($vehicle->client)->company_id !== auth()->user()->company_id,
            403
        );
    }
}
