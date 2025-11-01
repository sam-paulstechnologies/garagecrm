<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\Job\Booking;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Job\Job;

class BookingController extends Controller
{
    /** List active (non-archived) bookings */
    public function index()
    {
        $bookings = Booking::with(['client', 'opportunity', 'vehicleData', 'assignedUser'])
            ->where('company_id', auth()->user()->company_id)
            ->where('is_archived', false)
            ->latest()
            ->paginate(20);

        return view('admin.bookings.index', compact('bookings'));
    }

    /** Show create form */
    public function create()
    {
        $companyId     = auth()->user()->company_id;
        $clients       = Client::where('company_id', $companyId)->get();
        $opportunities = Opportunity::where('company_id', $companyId)->get();
        $users         = User::where('company_id', $companyId)->get();

        $vehicles = Schema::hasColumn('vehicles', 'company_id')
            ? Vehicle::where('company_id', $companyId)->get()
            : Vehicle::all();

        $vehicleMakes  = class_exists(VehicleMake::class) ? VehicleMake::all() : collect();
        $vehicleModels = class_exists(VehicleModel::class) ? VehicleModel::all() : collect();

        return view('admin.bookings.create', compact(
            'clients','opportunities','vehicles','users','vehicleMakes','vehicleModels'
        ));
    }

    /** Persist a new booking */
    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id'             => 'nullable|exists:clients,id',
            'client_name'           => 'required_without:client_id|string|max:255',
            'client_phone'          => 'nullable|string|max:20',
            'opportunity_id'        => 'nullable|exists:opportunities,id',
            'vehicle_id'            => 'nullable|exists:vehicles,id',
            'name'                  => 'required|string|max:255',
            'service_type'          => 'nullable|string|max:255',
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
            'status'                => 'nullable|string|max:40',
        ]);

        // Create client if needed
        if (empty($data['client_id'])) {
            $client = Client::create([
                'company_id' => $companyId,
                'name'       => $data['client_name'],
                'phone'      => $data['client_phone'] ?? null,
            ]);
            $data['client_id'] = $client->id;
        }

        // Auto expected close date: date + expected_duration
        if (empty($data['expected_close_date']) && !empty($data['date']) && !empty($data['expected_duration'])) {
            $data['expected_close_date'] = Carbon::parse($data['date'])
                ->addDays((int) $data['expected_duration'])
                ->toDateString();
        }

        $data['company_id']      = $companyId;
        $data['pickup_required'] = $request->boolean('pickup_required');
        $data['is_archived']     = false;

        // Normalize status snake case
        $status = $request->filled('status')
            ? strtolower(str_replace([' ', '-'], '_', trim($request->input('status'))))
            : null;
        if ($status !== null) {
            $data['status'] = $status;
        } else {
            unset($data['status']);
        }

        // Store original date for checks & messaging
        $slotDateForCheck = $data['date'];

        // Map to your DB columns (booking_date OR scheduled_at)
        if (Schema::hasColumn('bookings', 'booking_date')) {
            $data['booking_date'] = $data['date'];
            unset($data['date']);
        } elseif (Schema::hasColumn('bookings', 'scheduled_at')) {
            $data['scheduled_at'] = Carbon::parse($slotDateForCheck)->startOfDay()->toDateTimeString();
            unset($data['date']);
        }

        // Slot availability guard
        if (!Booking::isSlotAvailable($slotDateForCheck, $data['slot'], $companyId)) {
            return back()->withErrors(['slot' => 'The selected slot is already booked.'])->withInput();
        }

        DB::transaction(function () use (&$booking, $data, $status, $slotDateForCheck) {
            $booking = Booking::create($data);

            // Create Job automatically if status moved to vehicle_received at creation
            if ($status === 'vehicle_received') {
                $this->createJobFromBooking($booking, array_merge($data, ['date' => $slotDateForCheck]));
            }

            // 🔔 WhatsApp Confirmation
            $this->sendBookingConfirmationWhatsApp($booking, $slotDateForCheck, $data['slot']);

            // ⏰ Schedule reminder if appropriate
            $this->maybeScheduleReminder($booking, $slotDateForCheck, $data['slot']);
        });

        return redirect()->route('admin.bookings.index')->with('success', 'Booking created.');
    }

    /** Show one booking (+ communications) */
    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $communications = Communication::query()
            ->forCompany(auth()->user()->company_id)
            ->where('booking_id', $booking->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.bookings.show', compact('booking', 'communications'));
    }

    /** Edit form */
    public function edit(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $companyId     = auth()->user()->company_id;
        $clients       = Client::where('company_id', $companyId)->get();
        $opportunities = Opportunity::where('company_id', $companyId)->get();
        $users         = User::where('company_id', $companyId)->get();

        $vehicles = Schema::hasColumn('vehicles', 'company_id')
            ? Vehicle::where('company_id', $companyId)->get()
            : Vehicle::all();

        $vehicleMakes  = class_exists(VehicleMake::class) ? VehicleMake::all() : collect();
        $vehicleModels = class_exists(VehicleModel::class) ? VehicleModel::all() : collect();

        return view('admin.bookings.edit', compact(
            'booking','clients','opportunities','vehicles','users','vehicleMakes','vehicleModels'
        ));
    }

    /** Persist changes */
    public function update(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id'             => 'nullable|exists:clients,id',
            'client_name'           => 'required_without:client_id|string|max:255',
            'client_phone'          => 'nullable|string|max:20',
            'opportunity_id'        => 'nullable|exists:opportunities,id',
            'vehicle_id'            => 'nullable|exists:vehicles,id',
            'name'                  => 'required|string|max:255',
            'service_type'          => 'nullable|string|max:255',
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
            'status'                => 'nullable|string|max:40',
        ]);

        if (empty($data['client_id'])) {
            $client = Client::create([
                'company_id' => $companyId,
                'name'       => $data['client_name'],
                'phone'      => $data['client_phone'] ?? null,
            ]);
            $data['client_id'] = $client->id;
        }

        if (empty($data['expected_close_date']) && !empty($data['date']) && !empty($data['expected_duration'])) {
            $data['expected_close_date'] = Carbon::parse($data['date'])
                ->addDays((int) $data['expected_duration'])
                ->toDateString();
        }

        $data['pickup_required'] = $request->boolean('pickup_required');

        $status = $request->filled('status')
            ? strtolower(str_replace([' ', '-'], '_', trim($request->input('status'))))
            : null;
        if ($status !== null) {
            $data['status'] = $status;
        } else {
            unset($data['status']);
        }

        $slotDateForCheck = $data['date'];

        if (Schema::hasColumn('bookings', 'booking_date')) {
            $data['booking_date'] = $data['date'];
            unset($data['date']);
        } elseif (Schema::hasColumn('bookings', 'scheduled_at')) {
            $data['scheduled_at'] = Carbon::parse($slotDateForCheck)->startOfDay()->toDateTimeString();
            unset($data['date']);
        }

        if (!Booking::isSlotAvailable($slotDateForCheck, $data['slot'], $booking->company_id, $booking->id)) {
            return back()->withErrors(['slot' => 'The selected slot is already booked.'])->withInput();
        }

        DB::transaction(function () use ($booking, $data, $status, $slotDateForCheck) {
            $booking->update($data);

            if ($status === 'vehicle_received') {
                $this->createJobFromBooking($booking, array_merge($data, ['date' => $slotDateForCheck]));
            }

            // 🔔 WhatsApp Confirmation on updates too
            $this->sendBookingConfirmationWhatsApp($booking, $slotDateForCheck, $data['slot']);

            // ⏰ Update reminder schedule if date/slot changed or none set
            $this->maybeScheduleReminder($booking, $slotDateForCheck, $data['slot']);

            // 🎉 Feedback flow on completion
            if (in_array($status, ['completed','done'], true)) {
                $this->sendPostCompletionFlow($booking);
            }
        });

        return redirect()->route('admin.bookings.index')->with('success', 'Booking updated.');
    }

    /** Delete a booking */
    public function destroy(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking deleted.');
    }

    /** Archive */
    public function archive(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->is_archived = true;
        $booking->save();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking archived.');
    }

    /** Restore */
    public function restore(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->is_archived = false;
        $booking->save();

        return redirect()->route('admin.bookings.archived')->with('success', 'Booking restored.');
    }

    /** List archived bookings */
    public function archived()
    {
        $bookings = Booking::with(['client', 'opportunity', 'vehicleData', 'assignedUser'])
            ->where('company_id', auth()->user()->company_id)
            ->where('is_archived', true)
            ->latest()
            ->paginate(20);

        return view('admin.bookings.archived', compact('bookings'));
    }

    /** Company scoping guard */
    protected function authorizeBooking(Booking $booking): void
    {
        abort_if($booking->company_id !== auth()->user()->company_id, 403);
    }

    /** Create a Job when status becomes vehicle_received */
    private function createJobFromBooking(Booking $booking, array $data): void
    {
        $lookup = [
            'company_id' => $booking->company_id,
            'booking_id' => $booking->id,
        ];

        $payload = [
            'client_id' => $data['client_id'] ?? $booking->client_id,
        ];

        if (Schema::hasColumn('jobs', 'opportunity_id') && !empty($data['opportunity_id'])) {
            $payload['opportunity_id'] = $data['opportunity_id'];
        }
        if (Schema::hasColumn('jobs', 'title')) {
            $payload['title'] = $data['name'] ?? ('Job for Booking #'.$booking->id);
        }
        if (Schema::hasColumn('jobs', 'service_type') && !empty($data['service_type'])) {
            $payload['service_type'] = $data['service_type'];
        }
        if (Schema::hasColumn('jobs', 'assigned_to') && !empty($data['assigned_to'])) {
            $payload['assigned_to'] = $data['assigned_to'];
        }
        if (Schema::hasColumn('jobs', 'priority') && !empty($data['priority'])) {
            $payload['priority'] = $data['priority'];
        }
        if (Schema::hasColumn('jobs', 'notes') && !empty($data['notes'])) {
            $payload['notes'] = $data['notes'];
        }
        if (Schema::hasColumn('jobs', 'start_date') && !empty($data['date'])) {
            $payload['start_date'] = $data['date'];
        }
        if (Schema::hasColumn('jobs', 'expected_duration') && !empty($data['expected_duration'])) {
            $payload['expected_duration'] = $data['expected_duration'];
        }
        if (Schema::hasColumn('jobs', 'expected_close_date') && !empty($data['expected_close_date'])) {
            $payload['expected_close_date'] = $data['expected_close_date'];
        }
        if (Schema::hasColumn('jobs', 'description')) {
            $desc = $data['notes']
                ?? (!empty($data['service_type']) && !empty($data['name']) ? ($data['service_type'].' — '.$data['name']) : null)
                ?? ($data['service_type'] ?? null)
                ?? ('Auto-created from Booking #'.$booking->id);

            $payload['description'] = $desc;
        }

        Job::firstOrCreate($lookup, $payload);
    }

    /* ==============================================
     | WhatsApp Helpers
     ============================================== */

    private function sendBookingConfirmationWhatsApp(Booking $booking, string $originalDate, string $slot): void
    {
        try {
            $client = $booking->client;
            if (!$client || !$client->phone) return;

            $companyId = $booking->company_id;
            $leadId    = optional($booking->opportunity)->lead_id;

            [$dateStr, $timeStr] = $this->formatBookingWhen($booking, $originalDate, $slot);

            SendWhatsAppFromTemplate::dispatch(
                companyId:    $companyId,
                leadId:       $leadId ?? 0,
                toNumberE164: $client->phone,
                templateName: 'booking_confirmation',
                placeholders: [$dateStr, $timeStr],
                links:        [],
                context:      ['company_id' => $companyId, 'lead_id' => $leadId],
                action:       'initial'
            )->delay(now()->addSeconds(3));
        } catch (\Throwable $e) {
            \Log::error('[Booking][WA] confirmation send failed: '.$e->getMessage(), ['booking_id' => $booking->id]);
        }
    }

    private function maybeScheduleReminder(Booking $booking, string $originalDate, string $slot): void
    {
        try {
            // only if not already scheduled/sent
            if (!is_null($booking->reminder_sent_at)) return;

            $client = $booking->client;
            if (!$client || !$client->phone) return;

            $companyId = $booking->company_id;
            $leadId    = optional($booking->opportunity)->lead_id;

            $when = $this->computeReminderTime($booking, $originalDate, $slot);
            if ($when->isPast()) return;

            [$dateStr, $timeStr] = $this->formatBookingWhen($booking, $originalDate, $slot);

            // mark right away (simple guard against duplicates)
            $booking->reminder_sent_at = $when; // "scheduled for"
            $booking->save();

            SendWhatsAppFromTemplate::dispatch(
                companyId:    $companyId,
                leadId:       $leadId ?? 0,
                toNumberE164: $client->phone,
                templateName: 'visit_reminder_v1',
                placeholders: [$dateStr, $timeStr],
                links:        [],
                context:      ['company_id' => $companyId, 'lead_id' => $leadId],
                action:       'reminder'
            )->delay($when);
        } catch (\Throwable $e) {
            \Log::error('[Booking][WA] schedule reminder failed: '.$e->getMessage(), ['booking_id' => $booking->id]);
        }
    }

    private function sendPostCompletionFlow(Booking $booking): void
    {
        try {
            $client = $booking->client;
            if (!$client || !$client->phone) return;

            $companyId = $booking->company_id;
            $leadId    = optional($booking->opportunity)->lead_id;

            // Step 1: Thank you / feedback ping
            SendWhatsAppFromTemplate::dispatch(
                companyId:    $companyId,
                leadId:       $leadId ?? 0,
                toNumberE164: $client->phone,
                templateName: 'visit_feedback_v1',
                placeholders: [],
                links:        [],
                context:      ['company_id' => $companyId, 'lead_id' => $leadId],
                action:       'feedback'
            )->delay(now()->addMinutes(2));

            // Step 2: (Optional) review ask later
            SendWhatsAppFromTemplate::dispatch(
                companyId:    $companyId,
                leadId:       $leadId ?? 0,
                toNumberE164: $client->phone,
                templateName: 'review_request_v1',
                placeholders: [],
                links:        [],
                context:      ['company_id' => $companyId, 'lead_id' => $leadId],
                action:       'feedback'
            )->delay(now()->addHours(4));
        } catch (\Throwable $e) {
            \Log::error('[Booking][WA] post-completion flow failed: '.$e->getMessage(), ['booking_id' => $booking->id]);
        }
    }

    /** Returns [readableDate, readableTimeOrSlot] */
    private function formatBookingWhen(Booking $booking, string $originalDate, string $slot): array
    {
        // Prefer normalized columns if present
        $date = null;
        if (Schema::hasColumn('bookings', 'booking_date') && $booking->booking_date) {
            $date = Carbon::parse($booking->booking_date);
        } elseif (Schema::hasColumn('bookings', 'scheduled_at') && $booking->scheduled_at) {
            $date = Carbon::parse($booking->scheduled_at);
        } else {
            $date = Carbon::parse($originalDate);
        }

        $dateStr = $date->format('D, d M Y');
        $timeStr = $this->slotLabel($slot);

        return [$dateStr, $timeStr];
    }

    private function computeReminderTime(Booking $booking, string $originalDate, string $slot): Carbon
    {
        if (Schema::hasColumn('bookings', 'booking_date') && $booking->booking_date) {
            $date = Carbon::parse($booking->booking_date);
        } elseif (Schema::hasColumn('bookings', 'scheduled_at') && $booking->scheduled_at) {
            $date = Carbon::parse($booking->scheduled_at);
        } else {
            $date = Carbon::parse($originalDate);
        }

        // 30 minutes before the slot start
        $slotStart = match ($slot) {
            'morning'   => $date->copy()->setTime(8, 0),
            'afternoon' => $date->copy()->setTime(12, 0),
            'evening'   => $date->copy()->setTime(16, 0),
            'full_day'  => $date->copy()->setTime(9, 0),
            default     => $date->copy()->setTime(9, 0),
        };

        return $slotStart->subMinutes(30);
    }

    private function slotLabel(string $slot): string
    {
        return match ($slot) {
            'morning'   => 'Morning (8:00–12:00)',
            'afternoon' => 'Afternoon (12:00–4:00)',
            'evening'   => 'Evening (4:00–11:59)',
            'full_day'  => 'Full Day',
            default     => ucfirst(str_replace('_', ' ', $slot)),
        };
    }
}
