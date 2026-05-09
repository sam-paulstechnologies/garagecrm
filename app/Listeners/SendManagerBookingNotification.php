<?php

namespace App\Listeners;

use App\Events\BookingStatusUpdated;
use App\Events\OpportunityStatusUpdated;
use App\Models\CompanySetting;
use App\Models\Job\Booking;
use App\Services\WhatsApp\ManagerBookingNotifier;
use App\Services\WhatsApp\SendWhatsAppMessage;
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

        if (! in_array($status, [
            'manager_confirmation_pending',
            'ready_for_booking',
            'ready for booking',
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
        if ($event->status !== Booking::STATUS_SCHEDULED) {
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
        | Current flow may dispatch BookingStatusUpdated more than once.
        | This prevents duplicate booking confirmation WhatsApp messages.
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
        | Template variables required by booking_confirmed_by_manager_v1
        |--------------------------------------------------------------------------
        | DB variables:
        | ["name", "vehicle", "date_time", "garage", "location"]
        |--------------------------------------------------------------------------
        */

        $name = $client?->name ?: 'Customer';

        $vehicle = $booking->vehicle_label
            ?: $booking->opportunity?->vehicle_label
            ?: 'your vehicle';

        $date = optional($booking->booking_date)->format('d M Y') ?: 'confirmed date';

        $slot = $booking->slot_label ?: 'confirmed slot';

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
                ]
            );

            Log::info('[BookingScheduledConfirmation] booking.confirmed fired', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'phone' => $phone,
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
}