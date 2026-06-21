<?php

namespace App\Services\Calendar;

use App\Models\Job\Booking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class CalendarEventBuilder
{
    public const VISIBLE_STATUSES = [
        Booking::STATUS_PENDING,
        Booking::STATUS_SCHEDULED,
        Booking::STATUS_RESCHEDULE_REQUIRED,
    ];

    public function build(int $companyId, Carbon $start, Carbon $end, array $filters = []): Collection
    {
        return Booking::query()
            ->with(['client', 'assignedUser'])
            ->where('company_id', $companyId)
            ->where(function (Builder $query) {
                $query->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->get()
            ->map(fn (Booking $booking) => $this->bookingEvent($booking))
            ->sortBy('start')
            ->values();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? 'all';
        if (in_array($status, self::VISIBLE_STATUSES, true)) {
            $query->where('status', $status);
        }

        $assignedUser = $filters['assigned_user'] ?? 'all';
        if ($assignedUser === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif (ctype_digit((string) $assignedUser)) {
            $query->where('assigned_to', (int) $assignedUser);
        }

        $slot = $filters['slot'] ?? 'all';
        if (in_array($slot, ['morning', 'afternoon', 'evening', 'full_day'], true)) {
            $query->where('slot', $slot);
        }
    }

    private function bookingEvent(Booking $booking): array
    {
        $start = $this->bookingStart($booking);
        $isFullDay = strtolower((string) $booking->slot) === 'full_day';
        $end = $isFullDay
            ? null
            : $start->copy()->addMinutes(max((int) $booking->expected_duration, 120));
        $status = strtolower((string) $booking->status);
        $statusLabel = $this->statusLabel($status);
        $slotLabel = ucwords(str_replace('_', ' ', (string) ($booking->slot ?: 'No slot')));
        $service = $booking->service_type ?: $booking->name ?: 'Booking #' . $booking->id;
        $client = $booking->client?->name ?: 'No client';
        $color = $this->statusColor($status);

        return [
            'id' => 'booking:' . $booking->id,
            'type' => 'booking',
            'title' => $statusLabel . ': ' . $client . ' - ' . $service,
            'start' => $start->toIso8601String(),
            'end' => $end?->toIso8601String(),
            'url' => Route::has('admin.bookings.show') ? route('admin.bookings.show', $booking) : null,
            'status' => $status,
            'status_label' => $statusLabel,
            'assigned_to' => $booking->assigned_to,
            'client_name' => $booking->client?->name,
            'phone' => $booking->client?->phone_norm ?: $booking->client?->phone,
            'source_model' => Booking::class,
            'source_id' => $booking->id,
            'booking_id' => $booking->id,
            'slot' => $booking->slot,
            'slot_label' => $slotLabel,
            'service_type' => $booking->service_type,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#ffffff',
            'allDay' => $isFullDay,
            'extendedProps' => [
                'type' => 'booking',
                'status' => $status,
                'status_label' => $statusLabel,
                'assigned_to' => $booking->assigned_to,
                'assigned_user' => $booking->assignedUser?->name,
                'client_name' => $booking->client?->name,
                'phone' => $booking->client?->phone_norm ?: $booking->client?->phone,
                'source_model' => Booking::class,
                'source_id' => $booking->id,
                'booking_id' => $booking->id,
                'slot' => $booking->slot,
                'slot_label' => $slotLabel,
                'service_type' => $booking->service_type,
            ],
        ];
    }

    private function bookingStart(Booking $booking): Carbon
    {
        $start = Carbon::parse($booking->booking_date);

        return match (strtolower((string) $booking->slot)) {
            'morning' => $start->setTime(9, 0),
            'afternoon' => $start->setTime(13, 0),
            'evening' => $start->setTime(16, 0),
            'full_day' => $start->startOfDay(),
            default => $start->setTime(10, 0),
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING => 'Manager Confirmation',
            Booking::STATUS_SCHEDULED => 'Booking Confirmed',
            Booking::STATUS_RESCHEDULE_REQUIRED => 'Rescheduling Required',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING => '#f59e0b',
            Booking::STATUS_SCHEDULED => '#16a34a',
            Booking::STATUS_RESCHEDULE_REQUIRED => '#dc2626',
            default => '#64748b',
        };
    }
}
