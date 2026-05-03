<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $bookings = Booking::with([
                'client',
                'opportunity',
                'vehicleData.make',
                'vehicleData.model',
                'assignedUser'
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->latest()
            ->paginate(20);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;

        return view('admin.bookings.create', [
            'clients'       => Client::where('company_id', $companyId)->get(),
            'opportunities' => Opportunity::where('company_id', $companyId)->get(),
            'vehicles'      => Vehicle::with(['make', 'model'])->where('company_id', $companyId)->get(),
            'users'         => User::where('company_id', $companyId)->get(),

            'vehicleMakes'  => VehicleMake::orderBy('name')->get(),
            'vehicleModels' => VehicleModel::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'opportunity_id' => [
                'nullable',
                Rule::exists('opportunities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'name'         => 'required|string|max:255',
            'service_type' => 'nullable|string|max:255',

            'booking_date' => 'required|date',
            'slot'         => 'required|in:morning,afternoon,evening,full_day',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'pickup_required'       => 'boolean',
            'pickup_address'        => 'nullable|string|max:255',
            'pickup_contact_number' => 'nullable|string|max:20',

            'priority'            => 'nullable|in:low,medium,high',
            'expected_duration'   => 'nullable|integer|min:1',
            'expected_close_date' => 'nullable|date',

            'notes'  => 'nullable|string',
            'status' => [
                'nullable',
                Rule::in([
                    Booking::STATUS_PENDING,
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_SCHEDULED,
                    Booking::STATUS_VEHICLE_RECEIVED,
                    Booking::STATUS_COMPLETED,
                    Booking::STATUS_CANCELED,
                ]),
            ],
        ]);

        try {
            DB::transaction(function () use ($data, $companyId, $request) {

                $slotExists = Booking::where('company_id', $companyId)
                    ->where('booking_date', $data['booking_date'])
                    ->where('slot', $data['slot'])
                    ->where('status', '!=', Booking::STATUS_CANCELED)
                    ->lockForUpdate()
                    ->exists();

                if ($slotExists) {
                    throw new \Exception('Slot already booked');
                }

                $data['company_id']       = $companyId;
                $data['pickup_required']  = $request->boolean('pickup_required');
                $data['is_archived']      = false;
                $data['state_changed_at'] = now();
                $data['state_changed_by'] = auth()->id();
                $data['status']           = $data['status'] ?? Booking::STATUS_PENDING;

                if (empty($data['vehicle_id']) && !empty($data['opportunity_id'])) {
                    $opportunity = Opportunity::where('company_id', $companyId)
                        ->where('id', $data['opportunity_id'])
                        ->first();

                    if ($opportunity?->vehicle_id) {
                        $data['vehicle_id'] = $opportunity->vehicle_id;
                    }
                }

                $booking = Booking::create($data);

                if ($booking->status === Booking::STATUS_SCHEDULED) {
                    $this->handleScheduledBooking($booking);
                }
            });

        } catch (\Throwable $e) {
            Log::error('[BookingController] Store failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['slot' => $e->getMessage() ?: 'Slot already booked'])
                ->withInput();
        }

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking created successfully.');
    }

    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->load([
            'client',
            'opportunity',
            'vehicleData.make',
            'vehicleData.model',
            'assignedUser'
        ]);

        $communications = Communication::where('booking_id', $booking->id)
            ->latest()
            ->paginate(10);

        return view('admin.bookings.show', compact('booking', 'communications'));
    }

    public function edit(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $companyId = auth()->user()->company_id;

        return view('admin.bookings.edit', [
            'booking'       => $booking,
            'clients'       => Client::where('company_id', $companyId)->get(),
            'opportunities' => Opportunity::where('company_id', $companyId)->get(),
            'vehicles'      => Vehicle::with(['make', 'model'])->where('company_id', $companyId)->get(),
            'users'         => User::where('company_id', $companyId)->get(),

            'vehicleMakes'  => VehicleMake::orderBy('name')->get(),
            'vehicleModels' => VehicleModel::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'service_type' => 'nullable|string|max:255',

            'booking_date' => 'required|date',
            'slot'         => 'required|in:morning,afternoon,evening,full_day',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'pickup_required'       => 'boolean',
            'pickup_address'        => 'nullable|string|max:255',
            'pickup_contact_number' => 'nullable|string|max:20',

            'priority'            => 'nullable|in:low,medium,high',
            'expected_duration'   => 'nullable|integer|min:1',
            'expected_close_date' => 'nullable|date',

            'notes'  => 'nullable|string',
            'status' => [
                'nullable',
                Rule::in([
                    Booking::STATUS_PENDING,
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_SCHEDULED,
                    Booking::STATUS_VEHICLE_RECEIVED,
                    Booking::STATUS_COMPLETED,
                    Booking::STATUS_CANCELED,
                ]),
            ],
        ]);

        try {
            DB::transaction(function () use ($data, $booking, $request) {

                $oldStatus = $booking->status;

                $slotExists = Booking::where('company_id', $booking->company_id)
                    ->where('booking_date', $data['booking_date'])
                    ->where('slot', $data['slot'])
                    ->where('status', '!=', Booking::STATUS_CANCELED)
                    ->where('id', '!=', $booking->id)
                    ->lockForUpdate()
                    ->exists();

                if ($slotExists) {
                    throw new \Exception('Slot already booked');
                }

                $data['pickup_required']  = $request->boolean('pickup_required');
                $data['state_changed_at'] = now();
                $data['state_changed_by'] = auth()->id();

                $booking->update($data);

                $freshBooking = $booking->fresh([
                    'client',
                    'opportunity.lead',
                    'vehicleData.make',
                    'vehicleData.model',
                    'assignedUser',
                ]);

                if (
                    $oldStatus !== Booking::STATUS_SCHEDULED
                    && $freshBooking->status === Booking::STATUS_SCHEDULED
                ) {
                    $this->handleScheduledBooking($freshBooking);
                }

                if ($freshBooking->status === Booking::STATUS_CONFIRMED) {
                    $this->markOpportunityAppointment($freshBooking);
                }

                if ($freshBooking->status === Booking::STATUS_VEHICLE_RECEIVED) {
                    Job::firstOrCreate(
                        [
                            'booking_id' => $freshBooking->id,
                        ],
                        [
                            'company_id'  => $freshBooking->company_id,
                            'client_id'   => $freshBooking->client_id,
                            'description' => $freshBooking->service_type ?? 'Service job',
                            'status'      => 'pending',
                            'assigned_to' => $freshBooking->assigned_to,
                        ]
                    );
                }
            });

        } catch (\Throwable $e) {
            Log::error('[BookingController] Update failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['slot' => $e->getMessage() ?: 'Slot already booked'])
                ->withInput();
        }

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking updated.');
    }

    public function archive(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->update(['is_archived' => true]);

        return back()->with('success', 'Booking archived.');
    }

    public function restore(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->update(['is_archived' => false]);

        return back()->with('success', 'Booking restored.');
    }

    protected function handleScheduledBooking(Booking $booking): void
    {
        try {
            $booking->loadMissing([
                'client',
                'opportunity.lead',
                'vehicleData.make',
                'vehicleData.model',
            ]);

            $this->markOpportunityAppointment($booking);

            $lead = $booking->opportunity?->lead;

            if (!$lead) {
                Log::warning('[BookingController] Scheduled notification skipped - no lead found', [
                    'booking_id' => $booking->id,
                ]);

                return;
            }

            $dateLabel = $booking->booking_date
                ? $booking->booking_date->format('D, d M Y')
                : 'your selected date';

            $slotLabel = $booking->slot_label ?? ucfirst((string) $booking->slot);

            SendWhatsAppFromTemplate::dispatch(
                companyId: $booking->company_id,
                leadId: $lead->id,
                toNumberE164: $lead->phone ?: $lead->phone_norm,
                templateName: 'booking_scheduled_v1',
                placeholders: [
                    $booking->client?->name ?: $lead->name ?: 'Customer',
                    $dateLabel,
                    $slotLabel,
                ],
                links: [],
                context: [
                    'company_id' => $booking->company_id,
                    'lead_id' => $lead->id,
                    'booking_id' => $booking->id,
                    'source' => 'manager_scheduled_booking',
                ],
                action: 'booking_scheduled'
            );

            Log::info('[BookingController] Scheduled booking WhatsApp dispatched', [
                'booking_id' => $booking->id,
                'lead_id' => $lead->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('[BookingController] Scheduled notification failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function markOpportunityAppointment(Booking $booking): void
    {
        if (!$booking->opportunity) {
            return;
        }

        $booking->opportunity->update([
            'stage' => Opportunity::STAGE_APPOINTMENT,
            'next_follow_up' => $booking->booking_date,
            'expected_close_date' => $booking->booking_date,
        ]);
    }

    protected function authorizeBooking(Booking $booking): void
    {
        abort_if($booking->company_id !== auth()->user()->company_id, 403);
    }
}