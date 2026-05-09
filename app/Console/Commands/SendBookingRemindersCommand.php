<?php

namespace App\Console\Commands;

use App\Models\Job\Booking;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
            $sent += $this->sendReminderForDate(
                date: now()->addDay()->toDateString(),
                eventKey: 'booking.reminder_24h',
                action: 'booking_reminder_24h',
                lockPrefix: 'booking_reminder_24h_sent',
                label: '24h reminder',
                dryRun: $dryRun
            );
        }

        if (in_array($type, ['all', 'day-of'], true)) {
            $sent += $this->sendReminderForDate(
                date: now()->toDateString(),
                eventKey: 'booking.reminder_day_of',
                action: 'booking_reminder_day_of',
                lockPrefix: 'booking_reminder_day_of_sent',
                label: 'day-of reminder',
                dryRun: $dryRun
            );
        }

        $this->info("Booking reminders processed. Sent: {$sent}");

        return self::SUCCESS;
    }

    protected function sendReminderForDate(
        string $date,
        string $eventKey,
        string $action,
        string $lockPrefix,
        string $label,
        bool $dryRun
    ): int {
        $sent = 0;

        Booking::query()
            ->where('status', Booking::STATUS_SCHEDULED)
            ->where('is_archived', false)
            ->whereDate('booking_date', $date)
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
                $dryRun
            ) {
                foreach ($bookings as $booking) {
                    $client = $booking->client;

                    $phone = $client?->phone_norm
                        ?? $client?->phone
                        ?? $client?->whatsapp
                        ?? $client?->whatsapp_number
                        ?? null;

                    if (! $phone) {
                        Log::warning("[BookingReminder] skipped: no client phone", [
                            'booking_id' => $booking->id,
                            'event_key' => $eventKey,
                            'client_id' => $booking->client_id,
                        ]);

                        continue;
                    }

                    $payload = [
                        'name' => $client?->name ?? 'Customer',
                        'customer_name' => $client?->name ?? 'Customer',

                        'booking_id' => (int) $booking->id,
                        'booking_date' => optional($booking->booking_date)->format('d M Y'),
                        'date' => optional($booking->booking_date)->format('d M Y'),
                        'slot' => $booking->slot_label,
                        'service_type' => $booking->service_type ?? 'Service',
                        'vehicle' => $booking->vehicle_label ?? 'your vehicle',

                        'company_id' => (int) $booking->company_id,
                        'event_key' => $eventKey,
                        'send_mode' => 'meta_template',
                        'action' => $action,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | Dry Run
                    |--------------------------------------------------------------------------
                    | Important: dry-run should NOT create cache locks.
                    |--------------------------------------------------------------------------
                    */

                    if ($dryRun) {
                        $this->line("[DRY RUN] {$label}: booking {$booking->id} → {$phone}");
                        $sent++;
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Real Send Lock
                    |--------------------------------------------------------------------------
                    | Lock is created only when actually sending.
                    |--------------------------------------------------------------------------
                    */

                    $lockKey = "{$lockPrefix}_{$booking->id}";

                    if (! Cache::add($lockKey, true, now()->addDays(7))) {
                        Log::info("[BookingReminder] skipped: already sent/locked", [
                            'booking_id' => $booking->id,
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

                        $sent++;

                        Log::info("[BookingReminder] {$label} sent", [
                            'booking_id' => $booking->id,
                            'company_id' => $booking->company_id,
                            'phone' => $phone,
                            'event_key' => $eventKey,
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
}