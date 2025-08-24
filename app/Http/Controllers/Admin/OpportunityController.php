<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Opportunity;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Models\Job\Booking; // ✅ correct Booking model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // used to detect schedule columns

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
        $clients  = Client::where('company_id', auth()->user()->company_id)->get();
        $leads    = Lead::where('company_id', auth()->user()->company_id)->get();
        $vehicles = Vehicle::all();
        $makes    = VehicleMake::all();
        $models   = VehicleModel::all();

        return view('admin.opportunities.create', compact('clients', 'leads', 'vehicles', 'makes', 'models'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'lead_id'             => 'nullable|exists:leads,id',
            'title'               => 'required|string|max:255',
            // ✅ Require a service when converting to booking
            'service_type'        => 'required_if:stage,closed_won|string',
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

            // From booking popup
            'booking_date'        => 'nullable|date',
            'booking_time'        => 'nullable|string', // "HH:MM"
        ], [
            // Optional clearer message
            'service_type.required_if' => 'Please select at least one service when marking the opportunity as Closed Won.',
        ]);

        $data['company_id']   = auth()->user()->company_id;
        $data['is_converted'] = $data['is_converted'] ?? false;

        DB::transaction(function () use (&$opportunity, $data) {
            // Create Opportunity
            $opportunity = Opportunity::create($data);

            // If Closed Won + booking info present → create Booking
            if (
                ($data['stage'] ?? null) === 'closed_won' &&
                !empty($data['booking_date']) &&
                !empty($data['booking_time'])
            ) {
                $lookup = [
                    'company_id'     => $data['company_id'],
                    'opportunity_id' => $opportunity->id,
                ];

                // 👇 Include service_type so NOT NULL w/o default passes
                $payload = [
                    'client_id'    => $data['client_id'],
                    'service_type' => $data['service_type'] ?? '', // guaranteed by required_if
                    'notes'        => $data['notes'] ?? null,
                    'assigned_to'  => $data['assigned_to'] ?? null,
                ];

                // Set schedule based on existing columns
                if (Schema::hasColumn('bookings', 'scheduled_at')) {
                    $payload['scheduled_at'] = $data['booking_date'].' '.$data['booking_time'].':00';
                } else {
                    if (Schema::hasColumn('bookings', 'booking_date')) {
                        $payload['booking_date'] = $data['booking_date'];
                    }
                    if (Schema::hasColumn('bookings', 'booking_time')) {
                        $payload['booking_time'] = $data['booking_time'];
                    }
                }

                Booking::firstOrCreate($lookup, $payload);
            }
        });

        if (
            $request->input('stage') === 'closed_won' &&
            $request->filled(['booking_date', 'booking_time'])
        ) {
            return redirect()->route('admin.bookings.index')
                ->with('success', 'Opportunity created and Booking scheduled.');
        }

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity created.');
    }

    public function edit(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $clients  = Client::where('company_id', auth()->user()->company_id)->get();
        $leads    = Lead::where('company_id', auth()->user()->company_id)->get();
        $vehicles = Vehicle::all();
        $makes    = VehicleMake::all();
        $models   = VehicleModel::all();

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
            // ✅ Require a service when converting to booking
            'service_type'        => 'required_if:stage,closed_won|string',
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

            // From booking popup
            'booking_date'        => 'nullable|date',
            'booking_time'        => 'nullable|string', // "HH:MM"
        ], [
            'service_type.required_if' => 'Please select at least one service when marking the opportunity as Closed Won.',
        ]);

        $data['is_converted'] = $data['is_converted'] ?? false;

        DB::transaction(function () use ($opportunity, $data) {
            // Update Opportunity
            $opportunity->update(array_merge($data, [
                'company_id' => auth()->user()->company_id,
            ]));

            // If Closed Won + booking info → create or update Booking
            if (
                ($data['stage'] ?? null) === 'closed_won' &&
                !empty($data['booking_date']) &&
                !empty($data['booking_time'])
            ) {
                $lookup = [
                    'company_id'     => auth()->user()->company_id,
                    'opportunity_id' => $opportunity->id,
                ];

                // 👇 Include service_type here too
                $payload = [
                    'client_id'    => $data['client_id'],
                    'service_type' => $data['service_type'] ?? '',
                    'notes'        => $data['notes'] ?? null,
                    'assigned_to'  => $data['assigned_to'] ?? null,
                ];

                if (Schema::hasColumn('bookings', 'scheduled_at')) {
                    $payload['scheduled_at'] = $data['booking_date'].' '.$data['booking_time'].':00';
                } else {
                    if (Schema::hasColumn('bookings', 'booking_date')) {
                        $payload['booking_date'] = $data['booking_date'];
                    }
                    if (Schema::hasColumn('bookings', 'booking_time')) {
                        $payload['booking_time'] = $data['booking_time'];
                    }
                }

                Booking::updateOrCreate($lookup, $payload);
            }
        });

        if (
            $request->input('stage') === 'closed_won' &&
            $request->filled(['booking_date', 'booking_time'])
        ) {
            return redirect()->route('admin.bookings.index')
                ->with('success', 'Opportunity updated and Booking scheduled.');
        }

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
