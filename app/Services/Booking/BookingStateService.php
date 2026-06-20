<?php

namespace App\Services\Booking;

use App\Events\BookingStatusUpdated;
use App\Models\Job\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BookingStateService
{
    /**
     * Allowed booking status transitions.
     */
    private const MAP = [
        Booking::STATUS_PENDING => [
            Booking::STATUS_SCHEDULED,
        ],

        Booking::STATUS_SCHEDULED => [
            Booking::STATUS_PENDING,
        ],

        Booking::STATUS_CONVERTED_TO_JOB => [],
        Booking::STATUS_LOST => [],
    ];

    public function transition(Booking $booking, string $to): Booking
    {
        $to = $this->normalizeStatus($to);

        return DB::transaction(function () use ($booking, $to) {
            /**
             * Re-read and lock the row inside transaction.
             * This prevents two requests from changing the same booking at once.
             */
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $from = $this->normalizeStatus((string) $lockedBooking->status);

            /**
             * Idempotent safety:
             * If already in requested status, do not dispatch duplicate events.
             */
            if ($from === $to) {
                return $lockedBooking->fresh();
            }

            $this->assertTransitionAllowed($from, $to);

            $now = Carbon::now();
            $actor = Auth::id();

            $lockedBooking->status = $to;

            if (Schema::hasColumn('bookings', 'state_changed_at')) {
                $lockedBooking->state_changed_at = $now;
            }

            if (Schema::hasColumn('bookings', 'state_changed_by')) {
                $lockedBooking->state_changed_by = $actor;
            }

            if ($to === Booking::STATUS_CONFIRMED && Schema::hasColumn('bookings', 'confirmed_at')) {
                $lockedBooking->confirmed_at = $now;
            }

            $lockedBooking->save();

            /**
             * Dispatch after transaction commit.
             *
             * This prevents WhatsApp/email/listeners from firing if DB rollback happens.
             * IMPORTANT:
             * BookingObserver.php should NOT dispatch the same BookingStatusUpdated event.
             * We will check that file next.
             */
            DB::afterCommit(function () use ($lockedBooking, $to) {
                event(new BookingStatusUpdated($lockedBooking->fresh(), $to));
            });

            return $lockedBooking->fresh();
        });
    }

    protected function assertTransitionAllowed(string $from, string $to): void
    {
        if (! array_key_exists($from, self::MAP) || ! in_array($to, self::MAP[$from], true)) {
            throw ValidationException::withMessages([
                'to' => "Illegal transition: {$from} → {$to}",
            ]);
        }
    }

    protected function normalizeStatus(string $status): string
    {
        return strtolower(trim($status));
    }
}
