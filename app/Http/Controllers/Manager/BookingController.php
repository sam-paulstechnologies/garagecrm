<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use App\Services\Booking\BookingActionService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
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
}
