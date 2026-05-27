<?php

namespace App\Listeners;

use App\Events\BookingStatusUpdated;
use App\Events\OpportunityStatusUpdated;
use App\Models\CompanySetting;
use App\Models\Job\Booking;
use App\Services\WhatsApp\ManagerBookingNotifier;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendManagerBookingNotification
{
    public function handle(OpportunityStatusUpdated|BookingStatusUpdated $event): void
    {
        if ($event instanceof OpportunityStatusUpdated) {
            $this->handleOpportunityStatusUpdated($event);
            return;
        }

        if ($event instanceof BookingStatusUpdated) {
            $this->handleBookingStatusUpdated($event);
            return;
        }
    }

    protected function handleOpportunityStatusUpdated(OpportunityStatusUpdated $event): void
    {
        $status = strtolower(trim((string) $event->status));
        $status = str_replace(['-', ' '], '_', $status);

        if (! in_array($status, [
            'manager_confirmation_pending',
            'ready_for_booking',
        ], true)) {
            Log::info('[ManagerBookingNotification] skipped opportunity status', [
                'opportunity_id' => $event->opportunity->id ?? null,
                'status' => $status,
            ]);

            return;
        }

        Log::info('[ManagerBookingNotification] notifying manager', [
            'opportunity_id' => $event->opportunity->id ?? null,
            'status' => $status,
        ]);

        app(ManagerBookingNotifier::class)
            ->notify($event->opportunity);
    }

    protected function handleBookingStatusUpdated(BookingStatusUpdated $event): void
    {
        $status = strtolower(trim((string) $event->status));

        if ($status !== Booking::STATUS_SCHEDULED && $status !== 'scheduled') {
            return;
        }

        $booking = $event->booking->fresh([
            'client',
            'vehicleData.make',
            'vehicleData.model',
            'opportunity',
        ]);

        if (! $booking || ! $booking->company_id) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate protection
        |--------------------------------------------------------------------------
        | Booking confirmation should be sent from this listener only.
        | This lock prevents duplicate customer WhatsApp confirmations if the same
        | BookingStatusUpdated event is fired multiple times.
        |--------------------------------------------------------------------------
        */

        $lockKey = 'booking_confirmed_message_sent_' . $booking->company_id . '_' . $booking->id;

        if (! Cache::add($lockKey, true, now()->addDays(7))) {
            Log::info('[BookingScheduledConfirmation] duplicate skipped by lock', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
            ]);

            return;
        }

        $client = $booking->client;

        $phone = $client?->phone_norm
            ?? $client?->phone
            ?? $client?->whatsapp
            ?? $client?->whatsapp_number
            ?? null;

        if (! $phone) {
            Log::warning('[BookingScheduledConfirmation] no client phone found', [
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
            ]);

            Cache::forget($lockKey);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Template variables required by booking.confirmed
        |--------------------------------------------------------------------------
        | Expected variables:
        | name, vehicle, date_time, garage, location
        |--------------------------------------------------------------------------
        */

        $name = $client?->name ?: 'Customer';

        $vehicle = $booking->vehicle_label
            ?: $booking->opportunity?->vehicle_label
            ?: 'your vehicle';

        $date = $this->formatDate($booking->booking_date ?? null, 'confirmed date');

        $slot = $booking->slot_label
            ?? $this->formatSlot($booking->slot ?? null);

        $dateTime = trim($date . ', ' . $slot);

        $garage = $this->companySetting(
            companyId: (int) $booking->company_id,
            key: 'garage_name',
            fallback: config('app.name', 'Garage')
        );

        $location = $this->companySetting(
            companyId: (int) $booking->company_id,
            key: 'garage_location',
            fallback: 'Garage location will be shared by our team'
        );

        try {
            app(SendWhatsAppMessage::class)->fireEvent(
                (int) $booking->company_id,
                'booking.confirmed',
                (string) $phone,
                [
                    /*
                    |--------------------------------------------------------------------------
                    | Exact template variables
                    |--------------------------------------------------------------------------
                    */

                    'name' => $name,
                    'customer_name' => $name,

                    'vehicle' => $vehicle,
                    'car' => $vehicle,

                    'date_time' => $dateTime,
                    'booking_date_time' => $dateTime,

                    'garage' => $garage,
                    'garage_name' => $garage,

                    'location' => $location,
                    'garage_location' => $location,

                    /*
                    |--------------------------------------------------------------------------
                    | Existing/fallback variables
                    |--------------------------------------------------------------------------
                    */

                    'booking_id' => (int) $booking->id,
                    'booking_date' => $date,
                    'date' => $date,
                    'slot' => $slot,
                    'service_type' => $booking->service_type ?? 'Service',

                    /*
                    |--------------------------------------------------------------------------
                    | Context
                    |--------------------------------------------------------------------------
                    */

                    'company_id' => (int) $booking->company_id,
                    'event_key' => 'booking.confirmed',
                    'send_mode' => 'meta_template',
                    'action' => 'booking_confirmed',
                    'dedupe_key' => $lockKey,
                ]
            );

            Log::info('[BookingScheduledConfirmation] booking.confirmed fired', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'template_vars' => [
                    'name' => $name,
                    'vehicle' => $vehicle,
                    'date_time' => $dateTime,
                    'garage' => $garage,
                    'location' => $location,
                ],
            ]);
        } catch (\Throwable $e) {
            Cache::forget($lockKey);

            Log::error('[BookingScheduledConfirmation] failed', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function companySetting(int $companyId, string $key, string $fallback): string
    {
        try {
            $value = CompanySetting::query()
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->value('value');

            $value = trim((string) $value);

            return $value !== '' ? $value : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    protected function formatDate(mixed $date, string $fallback): string
    {
        if (! $date) {
            return $fallback;
        }

        try {
            if ($date instanceof Carbon) {
                return $date->format('d M Y');
            }

            return Carbon::parse($date)->format('d M Y');
        } catch (\Throwable) {
            return $fallback;
        }
    }

    protected function formatSlot(?string $slot): string
    {
        $slot = strtolower(trim((string) $slot));

        return match ($slot) {
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'evening' => 'Evening',
            'full_day' => 'Full Day',
            default => 'confirmed slot',
        };
    }
}