<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use App\Services\Booking\BookingActionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    protected const CAPACITY_STATUSES = [
        Booking::STATUS_PENDING,
        Booking::STATUS_SCHEDULED,
    ];

    public function __construct(
        protected BookingActionService $bookingActionService
    ) {}

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        $bookings = Booking::with([
                'client',
                'opportunity',
                'vehicleData.make',
                'vehicleData.model',
                'assignedUser',
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('service_type', 'like', "%{$q}%")
                        ->orWhere('slot', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%")
                        ->orWhereHas('client', function ($clientQuery) use ($q) {
                            $clientQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%")
                                ->orWhere('whatsapp', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicleData.make', function ($makeQuery) use ($q) {
                            $makeQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicleData.model', function ($modelQuery) use ($q) {
                            $modelQuery->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByRaw("FIELD(status, 'pending', 'scheduled', 'reschedule_required', 'converted_to_job', 'lost')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'pending' => $this->countByStatus($companyId, 'pending'),
            'scheduled' => $this->countByStatus($companyId, 'scheduled'),
            'reschedule_required' => $this->countByStatus($companyId, 'reschedule_required'),
            'converted_to_job' => $this->countByStatus($companyId, 'converted_to_job'),
            'lost' => $this->countByStatus($companyId, 'lost'),
        ];

        return view('manager.bookings.index', [
            'bookings' => $bookings,
            'q' => $q,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->load([
            'client',
            'opportunity',
            'vehicleData.make',
            'vehicleData.model',
            'assignedUser',
        ]);

        $job = null;

        if (class_exists(\App\Models\Job\Job::class)) {
            $job = \App\Models\Job\Job::where('company_id', $booking->company_id)
                ->where('booking_id', $booking->id)
                ->first();
        }

        return view('manager.bookings.show', [
            'booking' => $booking,
            'job' => $job,
        ]);
    }

    public function confirm(Booking $booking)
    {
        $this->authorizeBooking($booking);

        try {
            $this->bookingActionService->confirm($booking, (int) auth()->id());

            return back()->with('success', 'Booking confirmed successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'booking' => $e->getMessage() ?: 'Unable to confirm booking.',
            ]);
        }
    }

    public function reject(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $data = $request->validate([
            'lost_reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->bookingActionService->reject(
                $booking,
                (int) auth()->id(),
                $data['lost_reason'],
                $data['notes'] ?? null
            );

            return back()->with('success', 'Booking rejected/lost successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'booking' => $e->getMessage() ?: 'Unable to reject booking.',
            ]);
        }
    }

    public function reschedule(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        if (in_array((string) $booking->status, [Booking::STATUS_CONVERTED_TO_JOB, Booking::STATUS_LOST], true)) {
            return back()->withErrors([
                'booking' => 'Converted or lost bookings cannot be rescheduled.',
            ]);
        }

        $data = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'slot' => ['required', Rule::in(['morning', 'afternoon', 'evening', 'full_day'])],
            'booking_time' => ['nullable', 'date_format:H:i'],
            'reschedule_reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->ensureSlotAvailable(
                companyId: (int) $booking->company_id,
                bookingDate: $data['booking_date'],
                slot: $data['slot'],
                excludeBookingId: (int) $booking->id
            );

            $this->bookingActionService->requestReschedule(
                $booking,
                (int) auth()->id(),
                $data['reschedule_reason'],
                [
                    'booking_date' => $data['booking_date'],
                    'slot' => $data['slot'],
                    'notes' => $data['notes'] ?? null,
                ]
            );

            return back()->with('success', 'Booking marked as rescheduling required.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors([
                'booking' => $e->getMessage() ?: 'Unable to reschedule booking.',
            ]);
        }
    }

    public function convertToJob(Booking $booking)
    {
        $this->authorizeBooking($booking);

        try {
            $job = $this->bookingActionService->convertToJob($booking, (int) auth()->id());

            return redirect()
                ->route('manager.jobs.show', $job)
                ->with('success', $job->wasRecentlyCreated
                    ? 'Booking converted and job created.'
                    : 'Booking already converted. Opening existing job.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'booking' => $e->getMessage() ?: 'Unable to convert booking to job.',
            ]);
        }
    }

    protected function authorizeBooking(Booking $booking): void
    {
        abort_if((int) $booking->company_id !== $this->companyId(), 403);
    }

    protected function countByStatus(int $companyId, string $status): int
    {
        return (int) Booking::where('company_id', $companyId)
            ->where('is_archived', false)
            ->where('status', $status)
            ->count();
    }

    protected function ensureSlotAvailable(
        int $companyId,
        string $bookingDate,
        string $slot,
        ?int $excludeBookingId = null
    ): void {
        $baseQuery = Booking::where('company_id', $companyId)
            ->whereDate('booking_date', $bookingDate)
            ->where('is_archived', false)
            ->whereIn('status', self::CAPACITY_STATUSES);

        if ($excludeBookingId) {
            $baseQuery->where('id', '!=', $excludeBookingId);
        }

        $fullDayExists = (clone $baseQuery)
            ->where('slot', 'full_day')
            ->exists();

        if ($slot !== 'full_day' && $fullDayExists) {
            throw ValidationException::withMessages([
                'slot' => 'A full-day booking already exists for this date.',
            ]);
        }

        if ($slot === 'full_day') {
            if ((clone $baseQuery)->exists()) {
                throw ValidationException::withMessages([
                    'slot' => 'This date already has bookings. Full-day booking would overbook the day.',
                ]);
            }

            return;
        }

        $capacity = $this->slotCapacity($companyId, $slot);
        $slotBookingCount = (clone $baseQuery)
            ->where('slot', $slot)
            ->count();

        if ($slotBookingCount >= $capacity) {
            throw ValidationException::withMessages([
                'slot' => "The {$slot} slot is already full for this date. Capacity: {$capacity}.",
            ]);
        }
    }

    protected function slotCapacity(int $companyId, string $slot): int
    {
        $defaults = [
            'morning' => 3,
            'afternoon' => 3,
            'evening' => 3,
            'full_day' => 1,
        ];

        $default = $defaults[$slot] ?? 1;

        if (! Schema::hasTable('company_settings')) {
            return $default;
        }

        foreach (["booking_slot_capacity_{$slot}", "slot_capacity_{$slot}"] as $key) {
            $value = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->value('value');

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return $default;
    }
}
