<?php

namespace App\Observers;

use App\Models\Booking\Booking;
use App\Events\BookingStatusUpdated;

class BookingObserver
{
    // Consider both "status" and schedule changes for reschedule
    protected const STATUS_FIELDS = ['status'];
    protected const DATE_FIELDS   = ['date', 'scheduled_at', 'start_time'];

    public function updated(Booking $booking): void
    {
        $statusChanged = collect(self::STATUS_FIELDS)->first(fn($f) => $booking->isDirty($f) || $booking->wasChanged($f));
        $dateChanged   = collect(self::DATE_FIELDS)->first(fn($f) => $booking->isDirty($f) || $booking->wasChanged($f));

        if ($statusChanged) {
            $new = $booking->getAttribute($statusChanged);
            event(new BookingStatusUpdated($booking, strtolower((string) $new)));
            return;
        }

        if ($dateChanged) {
            event(new BookingStatusUpdated($booking, 'rescheduled'));
        }
    }
}
