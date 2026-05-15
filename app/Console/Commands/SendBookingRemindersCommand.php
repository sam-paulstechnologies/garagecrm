<?php

namespace App\Console\Commands;

use App\Models\Job\Booking;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendBookingRemindersCommand extends Command
{
    protected $signature = 'bookings:send-reminders 
                            {--type=all : all, 24h, day-of}
                            {--dry-run : Show what would be sent without sending}';

    protected $description = 'Send WhatsApp booking reminders 24 hours before and on the day of booking.';

    public function handle(): int
    {
        $type = strtolower((string) $this->option('type'));
        $dryRun = (bool) $this->option('dry-run');

        if (! in_array($type, ['all', '24h', 'day-of'], true)) {
            $this->error('Invalid type. Use all, 24h, or day-of.');
            return self::FAILURE;
        }

        $sent = 0;

        if (in_array($type, ['all', '24h'], true)) {
            $sent += $this->sendReminderForWindow(
                eventKey: 'booking.reminder_24h',
                action: 'booking_reminder_24h',
                lockPrefix: 'booking_reminder_24h_sent',
                label: '24h reminder',
                dryRun: $dryRun,
                windowStart: now()->addHours(23),
                windowEnd: now()->addHours(25),
                fallbackDate: now()->addDay()->toDateString()
            );
        }

        if (in_array($type, ['all', 'day-of'], true)) {
            $sent += $this->sendReminderForWindow(
                eventKey: 'booking.reminder_day_of',
                action: 'booking_reminder_day_of',
                lockPrefix: 'booking_reminder_day_of_sent',
                label: 'day-of reminder',
                dryRun: $dryRun,
                windowStart: now()->startOfDay(),
                windowEnd: now()->endOfDay(),
                fallbackDate: now()->toDateString()
            );
        }

        $this->info("Booking reminders processed. Sent: {$sent}");

        return self::SUCCESS;
    }

    protected function sendReminderForWindow(
        string $eventKey,
        string $action,
        string $lockPrefix,
        string $label,
        bool $dryRun,
        Carbon $windowStart,
        Carbon $windowEnd,
        string $fallbackDate
    ): int {
        $sent = 0;

        /*
        |--------------------------------------------------------------------------
        | Query strategy
        |--------------------------------------------------------------------------
        |
        | We keep the query broad and then filter booking datetime in PHP.
        | Why:
        | - Existing DB may only have booking_date.
        | - Some builds may have booking_time, scheduled_at, start_at, etc.
        | - This keeps the command schema-safe.
        |
        */

        Booking::query()
            ->where('status', Booking::STATUS_SCHEDULED)
            ->where('is_archived', false)
            ->whereDate('booking_date', $fallbackDate)
            ->with([
                'client',
                'vehicleData.make',
                'vehicleData.model',
                'opportunity',
            ])
            ->orderBy('booking_date')
            ->chunkById(100, function ($bookings) use (
                &$sent,
                $eventKey,
                $action,
                $lockPrefix,
                $label,
                $dryRun,
                $windowStart,
                $windowEnd
            ) {
                foreach ($bookings as $booking) {
                    $bookingAt = $this->bookingDateTime($booking);

                    /*
                    |--------------------------------------------------------------------------
                    | Time-window protection
                    |--------------------------------------------------------------------------
                    | If we can calculate actual booking datetime, enforce the reminder window.
                    | If not, fallback to date-based behavior.
                    |--------------------------------------------------------------------------
                    */

                    if ($bookingAt instanceof Carbon) {
                        if ($bookingAt->lt($windowStart) || $bookingAt->gt($windowEnd)) {
                            Log::debug("[BookingReminder] skipped: outside {$label} window", [
                                'booking_id' => $booking->id,
                                'company_id' => $booking->company_id,
                                'event_key' => $eventKey,
                                'booking_at' => $bookingAt->toDateTimeString(),
                                'window_start' => $windowStart->toDateTimeString(),
                                'window_end' => $windowEnd->toDateTimeString(),
                            ]);

                            continue;
                        }
                    }

                    if ($this->alreadySent($booking, $eventKey, $action)) {
                        Log::info("[BookingReminder] skipped: persistent marker already sent", [
                            'booking_id' => $booking->id,
                            'company_id' => $booking->company_id,
                            'event_key' => $eventKey,
                        ]);

                        continue;
                    }

                    $client = $booking->client;

                    $phone = $client?->phone_norm
                        ?? $client?->phone
                        ?? $client?->whatsapp
                        ?? $client?->whatsapp_number
                        ?? null;

                    $phone = trim((string) $phone);

                    if ($phone === '') {
                        Log::warning("[BookingReminder] skipped: no client phone", [
                            'booking_id' => $booking->id,
                            'event_key' => $eventKey,
                            'client_id' => $booking->client_id,
                        ]);

                        continue;
                    }

                    $payload = $this->payload(
                        booking: $booking,
                        phone: $phone,
                        eventKey: $eventKey,
                        action: $action,
                        bookingAt: $bookingAt
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Dry Run
                    |--------------------------------------------------------------------------
                    | Important: dry-run should NOT create cache locks or DB markers.
                    |--------------------------------------------------------------------------
                    */

                    if ($dryRun) {
                        $this->line("[DRY RUN] {$label}: booking {$booking->id} → {$phone}");
                        $sent++;
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Runtime Send Lock
                    |--------------------------------------------------------------------------
                    */

                    $lockKey = "{$lockPrefix}_" . (int) $booking->company_id . '_' . (int) $booking->id;

                    if (! Cache::add($lockKey, true, now()->addDays(30))) {
                        Log::info("[BookingReminder] skipped: already sent/locked", [
                            'booking_id' => $booking->id,
                            'company_id' => $booking->company_id,
                            'event_key' => $eventKey,
                            'lock_key' => $lockKey,
                        ]);

                        continue;
                    }

                    try {
                        app(SendWhatsAppMessage::class)->fireEvent(
                            (int) $booking->company_id,
                            $eventKey,
                            (string) $phone,
                            $payload
                        );

                        $this->markSent($booking, $eventKey, $action);

                        $sent++;

                        Log::info("[BookingReminder] {$label} sent", [
                            'booking_id' => $booking->id,
                            'company_id' => $booking->company_id,
                            'phone' => $phone,
                            'event_key' => $eventKey,
                            'booking_at' => $bookingAt?->toDateTimeString(),
                        ]);
                    } catch (\Throwable $e) {
                        Cache::forget($lockKey);

                        Log::error("[BookingReminder] {$label} failed", [
                            'booking_id' => $booking->id,
                            'company_id' => $booking->company_id,
                            'phone' => $phone,
                            'event_key' => $eventKey,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $sent;
    }

    protected function payload(Booking $booking, string $phone, string $eventKey, string $action, ?Carbon $bookingAt): array
    {
        $client = $booking->client;

        $dateLabel = $bookingAt
            ? $bookingAt->format('d M Y')
            : optional($booking->booking_date)->format('d M Y');

        $timeLabel = $bookingAt
            ? $bookingAt->format('h:i A')
            : $this->bookingTimeLabel($booking);

        return [
            /*
            |--------------------------------------------------------------------------
            | Template variables
            |--------------------------------------------------------------------------
            */

            'name' => $client?->name ?? 'Customer',
            'customer_name' => $client?->name ?? 'Customer',

            'booking_id' => (int) $booking->id,
            'booking_date' => $dateLabel,
            'date' => $dateLabel,
            'booking_time' => $timeLabel,
            'time' => $timeLabel,
            'slot' => $booking->slot_label ?? $booking->slot ?? '-',
            'service_type' => $booking->service_type ?? 'Service',
            'vehicle' => $booking->vehicle_label ?? 'your vehicle',

            /*
            |--------------------------------------------------------------------------
            | Reschedule instruction
            |--------------------------------------------------------------------------
            |
            | The actual button should be configured in the WhatsApp template.
            | These values are provided in case the template needs text variables.
            |
            */

            'reschedule_text' => 'Reply RESCHEDULE if you would like to reschedule this booking.',
            'reschedule_keyword' => 'RESCHEDULE',

            /*
            |--------------------------------------------------------------------------
            | Context variables
            |--------------------------------------------------------------------------
            */

            'company_id' => (int) $booking->company_id,
            'client_id' => $booking->client_id ? (int) $booking->client_id : null,
            'opportunity_id' => $booking->opportunity_id ? (int) $booking->opportunity_id : null,
            'phone' => $phone,

            'event_key' => $eventKey,
            'send_mode' => 'meta_template',
            'action' => $action,
            'source' => 'send_booking_reminders_command',

            'booking_at' => $bookingAt?->toIso8601String(),
        ];
    }

    protected function bookingDateTime(Booking $booking): ?Carbon
    {
        /*
        |--------------------------------------------------------------------------
        | Preferred full datetime columns
        |--------------------------------------------------------------------------
        */

        foreach ([
            'scheduled_at',
            'starts_at',
            'start_at',
            'booking_at',
            'appointment_at',
            'preferred_at',
        ] as $column) {
            try {
                if (
                    Schema::hasColumn($booking->getTable(), $column)
                    && ! empty($booking->{$column})
                ) {
                    return Carbon::parse($booking->{$column});
                }
            } catch (\Throwable $e) {
                Log::debug('[BookingReminder] datetime column parse skipped', [
                    'booking_id' => $booking->id,
                    'column' => $column,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | booking_date + time column
        |--------------------------------------------------------------------------
        */

        $date = $booking->booking_date ?? null;

        if (! $date) {
            return null;
        }

        foreach ([
            'booking_time',
            'preferred_time',
            'time',
            'start_time',
        ] as $column) {
            try {
                if (
                    Schema::hasColumn($booking->getTable(), $column)
                    && ! empty($booking->{$column})
                ) {
                    return Carbon::parse(
                        Carbon::parse($date)->toDateString() . ' ' . $booking->{$column}
                    );
                }
            } catch (\Throwable $e) {
                Log::debug('[BookingReminder] date + time parse skipped', [
                    'booking_id' => $booking->id,
                    'column' => $column,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback:
        | Date-only booking means we cannot do exact 24h time filtering.
        | Return start of day so day-of works and 24h remains date-based fallback.
        |--------------------------------------------------------------------------
        */

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function bookingTimeLabel(Booking $booking): string
    {
        foreach ([
            'booking_time',
            'preferred_time',
            'time',
            'start_time',
        ] as $column) {
            try {
                if (
                    Schema::hasColumn($booking->getTable(), $column)
                    && ! empty($booking->{$column})
                ) {
                    return Carbon::parse($booking->{$column})->format('h:i A');
                }
            } catch (\Throwable $e) {
                // Ignore and fallback to slot.
            }
        }

        return (string) ($booking->slot_label ?? $booking->slot ?? '-');
    }

    protected function alreadySent(Booking $booking, string $eventKey, string $action): bool
    {
        try {
            $table = $booking->getTable();

            $columnMap = [
                'booking.reminder_24h' => [
                    'reminder_24h_sent_at',
                    'booking_reminder_24h_sent_at',
                    'wa_24h_reminder_sent_at',
                ],
                'booking.reminder_day_of' => [
                    'reminder_day_of_sent_at',
                    'booking_reminder_day_of_sent_at',
                    'wa_day_of_reminder_sent_at',
                ],
            ];

            foreach ($columnMap[$eventKey] ?? [] as $column) {
                if (Schema::hasColumn($table, $column) && ! empty($booking->{$column})) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::debug('[BookingReminder] persistent marker check skipped', [
                'booking_id' => $booking->id ?? null,
                'event_key' => $eventKey,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Message log fallback
        |--------------------------------------------------------------------------
        */

        try {
            if (Schema::hasTable('message_logs')) {
                return \DB::table('message_logs')
                    ->where('company_id', (int) $booking->company_id)
                    ->where('direction', 'out')
                    ->where('channel', 'whatsapp')
                    ->where('created_at', '>=', now()->subDays(45))
                    ->where(function ($query) use ($booking) {
                        $query->where('meta->booking_id', (int) $booking->id)
                            ->orWhere('body', 'like', '%' . $booking->id . '%');
                    })
                    ->where(function ($query) use ($eventKey, $action) {
                        $query->where('template', $eventKey)
                            ->orWhere('meta->event_key', $eventKey)
                            ->orWhere('meta->action', $action);
                    })
                    ->exists();
            }
        } catch (\Throwable $e) {
            Log::debug('[BookingReminder] message log duplicate check skipped', [
                'booking_id' => $booking->id ?? null,
                'event_key' => $eventKey,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    protected function markSent(Booking $booking, string $eventKey, string $action): void
    {
        try {
            $table = $booking->getTable();

            $columnMap = [
                'booking.reminder_24h' => [
                    'reminder_24h_sent_at',
                    'booking_reminder_24h_sent_at',
                    'wa_24h_reminder_sent_at',
                ],
                'booking.reminder_day_of' => [
                    'reminder_day_of_sent_at',
                    'booking_reminder_day_of_sent_at',
                    'wa_day_of_reminder_sent_at',
                ],
            ];

            $updates = [];

            foreach ($columnMap[$eventKey] ?? [] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $updates[$column] = now();
                    break;
                }
            }

            if (Schema::hasColumn($table, 'last_whatsapp_reminder_at')) {
                $updates['last_whatsapp_reminder_at'] = now();
            }

            if (Schema::hasColumn($table, 'last_whatsapp_reminder_event')) {
                $updates['last_whatsapp_reminder_event'] = $eventKey;
            }

            if (! empty($updates)) {
                $booking->forceFill($updates)->save();
            }
        } catch (\Throwable $e) {
            Log::warning('[BookingReminder] marker update failed', [
                'booking_id' => $booking->id ?? null,
                'company_id' => $booking->company_id ?? null,
                'event_key' => $eventKey,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}