<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['client', 'opportunity', 'vehicleData', 'assignedUser'])
            ->where('company_id', auth()->user()->company_id)
            ->where('is_archived', false) // ✅ Show only active bookings
            ->latest()
            ->paginate(20);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $opportunities = Opportunity::where('company_id', auth()->user()->company_id)->get();
        $vehicles = Vehicle::all();
        $users = User::all();

        return view('admin.bookings.create', compact('clients', 'opportunities', 'vehicles', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'             => 'required|exists:clients,id',
            'opportunity_id'        => 'nullable|exists:opportunities,id',
            'vehicle_id'            => 'nullable|exists:vehicles,id',
            'name'                  => 'required|string|max:255',
            'service_type'          => 'nullable|string|max:100',
            'date'                  => 'required|date',
            'slot'                  => 'required|string|in:morning,afternoon,evening,full_day',
            'assigned_to'           => 'nullable|exists:users,id',
            'pickup_required'       => 'boolean',
            'pickup_address'        => 'nullable|string|max:255',
            'pickup_contact_number' => 'nullable|string|max:20',
            'notes'                 => 'nullable|string',
            'expected_duration'     => 'nullable|integer|min:1',
            'expected_close_date'   => 'nullable|date|after_or_equal:date',
            'priority'              => 'nullable|in:low,medium,high',
        ]);

        $data['company_id'] = auth()->user()->company_id;
        $data['pickup_required'] = $request->boolean('pickup_required');
        $data['is_archived'] = false;

        if (!Booking::isSlotAvailable($data['date'], $data['slot'], $data['company_id'])) {
            return back()->withErrors(['slot' => 'The selected slot is already booked. Please choose another.'])->withInput();
        }

        Booking::create($data);

        return redirect()->route('admin.bookings.index')->with('success', 'Booking created successfully.');
    }

    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);
        return view('admin.bookings.show', compact('booking'));
    }

    public function edit(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $opportunities = Opportunity::where('company_id', auth()->user()->company_id)->get();
        $vehicles = Vehicle::all();
        $users = User::all();

        // ✅ Add these two lines
        $vehicleMakes = \App\Models\Vehicle\VehicleMake::all();
        $vehicleModels = \App\Models\Vehicle\VehicleModel::all();

        return view('admin.bookings.edit', compact(
            'booking',
            'clients',
            'opportunities',
            'vehicles',
            'users',
            'vehicleMakes',
            'vehicleModels'
        ));
    }

    public function update(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $data = $request->validate([
            'client_id'             => 'required|exists:clients,id',
            'opportunity_id'        => 'nullable|exists:opportunities,id',
            'vehicle_id'            => 'nullable|exists:vehicles,id',
            'name'                  => 'required|string|max:255',
            'service_type'          => 'nullable|string|max:100',
            'date'                  => 'required|date',
            'slot'                  => 'required|string|in:morning,afternoon,evening,full_day',
            'assigned_to'           => 'nullable|exists:users,id',
            'pickup_required'       => 'boolean',
            'pickup_address'        => 'nullable|string|max:255',
            'pickup_contact_number' => 'nullable|string|max:20',
            'notes'                 => 'nullable|string',
            'expected_duration'     => 'nullable|integer|min:1',
            'expected_close_date'   => 'nullable|date|after_or_equal:date',
            'priority'              => 'nullable|in:low,medium,high',
        ]);

        $data['pickup_required'] = $request->boolean('pickup_required');

        if (!Booking::isSlotAvailable($data['date'], $data['slot'], $booking->company_id, $booking->id)) {
            return back()->withErrors(['slot' => 'The selected slot is already booked. Please choose another.'])->withInput();
        }

        $booking->update($data);

        return redirect()->route('admin.bookings.index')->with('success', 'Booking updated successfully.');
    }

    public function destroy(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking deleted successfully.');
    }

    protected function authorizeBooking(Booking $booking)
    {
        abort_if($booking->company_id !== auth()->user()->company_id, 403);
    }

    public function archive($id)
    {
        $booking = Booking::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $booking->is_archived = true;
        $booking->save();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking archived successfully.');
    }

    public function restore($id)
    {
        $booking = Booking::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $booking->is_archived = false;
        $booking->save();

        return redirect()->route('admin.bookings.archived')->with('success', 'Booking restored successfully.');
    }

    public function archived()
    {
        $bookings = Booking::with(['client', 'opportunity', 'vehicleData', 'assignedUser'])
            ->where('company_id', auth()->user()->company_id)
            ->where('is_archived', true)
            ->latest()
            ->paginate(20);

        return view('admin.bookings.archived', compact('bookings'));
    }
}
