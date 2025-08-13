<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle\Vehicle;
use App\Models\Client\Client;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with(['client', 'model'])->latest()->paginate(20);
        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $makes = VehicleMake::all();
        $models = VehicleModel::all();

        return view('admin.vehicles.create', compact('clients', 'makes', 'models'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'               => 'required|exists:clients,id',
            'vehicle_model_id'        => 'nullable|exists:vehicle_models,id',
            'trim'                    => 'nullable|string|max:100',
            'plate_number'            => 'required|string|max:50',
            'year'                    => 'nullable|string|max:10',
            'color'                   => 'nullable|string|max:50',
            'registration_expiry_date' => 'nullable|date',
            'insurance_expiry_date'   => 'nullable|date',
        ]);

        Vehicle::create($data);

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle added successfully.');
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);

        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $makes = VehicleMake::all();
        $models = VehicleModel::all();

        return view('admin.vehicles.edit', compact('vehicle', 'clients', 'makes', 'models'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);

        $data = $request->validate([
            'client_id'               => 'required|exists:clients,id',
            'vehicle_model_id'        => 'nullable|exists:vehicle_models,id',
            'trim'                    => 'nullable|string|max:100',
            'plate_number'            => 'required|string|max:50',
            'year'                    => 'nullable|string|max:10',
            'color'                   => 'nullable|string|max:50',
            'registration_expiry_date' => 'nullable|date',
            'insurance_expiry_date'   => 'nullable|date',
        ]);

        $vehicle->update($data);

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeCompany($vehicle);
        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle deleted.');
    }

    protected function authorizeCompany(Vehicle $vehicle)
    {
        abort_if($vehicle->client->company_id !== auth()->user()->company_id, 403);
    }
}
