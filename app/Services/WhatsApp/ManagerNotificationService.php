<?php

namespace App\Services\WhatsApp;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\System\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ManagerNotificationService
{
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Event Keys
    |--------------------------------------------------------------------------
    |
    | Manager alerts are proactive system-to-manager messages.
    | They must use approved Meta templates through DB mapping.
    */

    public const EVENT_MANAGER_ATTENTION = 'manager.attention_required';
    public const EVENT_MANAGER_BOOKING_CONFIRMATION = 'manager.booking_confirmation_required';
    public const EVENT_MANAGER_RESCHEDULE_REQUESTED = 'manager.customer_reschedule_requested';

    public function notifyForLead(
        Lead $lead,
        string $reason,
        ?Carbon $preferredAt = null,
        ?int $bookingId = null,
        ?array $extra = []
    ): void {
        $lead->refresh();

        $company = $this->companyForLead($lead);

        if (! $company) {
            Log::warning('[ManagerNotification] Company missing, notification skipped', [
                'lead_id'    => $lead->id,
                'company_id' => $lead->company_id,
                'reason'     => $reason,
            ]);

            return;
        }

        if ((int) $lead->company_id !== (int) $company->id) {
            Log::warning('[ManagerNotification] Lead company mismatch, notification skipped', [
                'lead_id'         => $lead->id,
                'lead_company_id' => $lead->company_id,
                'company_id'      => $company->id,
                'reason'          => $reason,
            ]);

            return;
        }

        $managerPhone = $this->managerPhone($company);

        if (! $managerPhone) {
            Log::warning('[ManagerNotification] Manager phone missing, notification skipped', [
                'lead_id'    => $lead->id,
                'company_id' => $company->id,
                'reason'     => $reason,
            ]);

            return;
        }

        $safeReason = $this->safeText($reason, 'Manager attention required');

        /*
        |--------------------------------------------------------------------------
        | Duplicate Manager Alert Lock
        |--------------------------------------------------------------------------
        |
        | Prevent repeated manager WhatsApp alerts for the same lead/reason/booking.
        | This is intentionally cache-based until the durable automation lock table
        | is added.
        */

        $lockKey = $this->managerLockKey(
            companyId: (int) $company->id,
            leadId: (int) $lead->id,
            reason: $safeReason,
            bookingId: $bookingId,
            extra: $extra ?? []
        );

        if (! Cache::add($lockKey, true, now()->addMinutes(15))) {
            Log::info('[ManagerNotification] Duplicate manager alert skipped by lock', [
                'lead_id'    => $lead->id,
                'company_id' => $company->id,
                'reason'     => $safeReason,
                'booking_id' => $bookingId,
                'lock_key'   => $lockKey,
            ]);

            return;
        }

        $customerName   = $this->customerName($lead);
        $customerPhone  = $this->customerPhone($lead);
        $vehicleLabel   = $this->vehicleLabel($lead);
        $preferredLabel = $this->preferredDateTimeLabel($preferredAt);
        $lastMessage    = $this->lastCustomerMessage($lead);
        $leadUrl        = $this->leadUrl($lead);
        $bookingUrl     = $bookingId ? $this->bookingUrl($lead, $bookingId) : null;

        $eventKey = $this->eventKeyFromExtra($extra ?? [], $bookingId);

        try {
            /** @var SendWhatsAppMessage $sender */
            $sender = app(SendWhatsAppMessage::class);

            $sender->fireEvent(
                (int) $company->id,
                $eventKey,
                (string) $managerPhone,
                array_merge([
                    /*
                    |--------------------------------------------------------------------------
                    | Template variables
                    |--------------------------------------------------------------------------
                    */

                    'customer_name'      => $customerName,
                    'name'               => $customerName,
                    'lead_name'          => $customerName,

                    'customer_phone'     => $customerPhone,
                    'phone'              => $customerPhone,

                    'reason'             => $safeReason,
                    'vehicle'            => $vehicleLabel,
                    'vehicle_label'      => $vehicleLabel,

                    'preferred_datetime' => $preferredLabel,
                    'preferred_time'     => $preferredLabel,

                    'booking_id'         => $bookingId ? (string) $bookingId : '-',
                    'last_message'       => $lastMessage,
                    'lead_url'           => $leadUrl ?: '-',
                    'booking_url'        => $bookingUrl ?: '-',

                    /*
                    |--------------------------------------------------------------------------
                    | Context variables
                    |--------------------------------------------------------------------------
                    */

                    'company_id'         => (int) $company->id,
                    'lead_id'            => (int) $lead->id,
                    'client_id'          => $lead->client_id ? (int) $lead->client_id : null,
                    'recipient_type'     => 'manager',
                    'manager_phone'      => $managerPhone,
                    'opportunity_id'     => $lead->opportunity?->id,

                    'source'             => 'manager_notification_service',
                    'event_key'          => $eventKey,
                    'fallback_event_key' => self::EVENT_MANAGER_ATTENTION,
                    'action'             => 'manager_attention',
                    'send_mode'          => 'meta_template',
                    'lock_key'           => $lockKey,
                ], $extra ?? [])
            );

            Log::info('[ManagerNotification] Manager WhatsApp event fired', [
                'lead_id'       => $lead->id,
                'company_id'    => $company->id,
                'manager_phone' => $managerPhone,
                'event_key'     => $eventKey,
                'reason'        => $safeReason,
                'booking_id'    => $bookingId,
                'lock_key'      => $lockKey,
            ]);
        } catch (\Throwable $e) {
            Cache::forget($lockKey);

            /*
            |--------------------------------------------------------------------------
            | Fallback behavior
            |--------------------------------------------------------------------------
            |
            | We do not send hardcoded customer messages here because this is a
            | proactive manager alert. If template send fails, we log it clearly.
            | Existing app notification/email paths can still be used elsewhere.
            */

            Log::error('[ManagerNotification] Manager WhatsApp event failed', [
                'lead_id'       => $lead->id,
                'company_id'    => $company->id,
                'manager_phone' => $managerPhone,
                'event_key'     => $eventKey,
                'reason'        => $safeReason,
                'booking_id'    => $bookingId,
                'lock_key'      => $lockKey,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    public function notifyForBooking(
        Booking $booking,
        string $reason = 'Booking requires manager confirmation',
        ?array $extra = []
    ): void {
        $booking->refresh();

        $lead = $this->leadForBooking($booking);

        if (! $lead) {
            Log::warning('[ManagerNotification] Lead missing for booking, notification skipped', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'reason'     => $reason,
            ]);

            return;
        }

        if ((int) $lead->company_id !== (int) $booking->company_id) {
            Log::warning('[ManagerNotification] Booking lead company mismatch, notification skipped', [
                'booking_id'         => $booking->id,
                'booking_company_id' => $booking->company_id,
                'lead_id'            => $lead->id,
                'lead_company_id'    => $lead->company_id,
                'reason'             => $reason,
            ]);

            return;
        }

        $preferredAt = $this->bookingDateTime($booking);

        $this->notifyForLead(
            lead: $lead,
            reason: $reason,
            preferredAt: $preferredAt,
            bookingId: (int) $booking->id,
            extra: array_merge([
                'event_key'      => self::EVENT_MANAGER_BOOKING_CONFIRMATION,
                'booking_status' => $booking->status,
                'booking_slot'   => $booking->slot,
                'booking_date'   => $booking->booking_date,
                'booking_time'   => $this->bookingTimeLabel($booking),
                'source'         => 'manager_notification_service.booking',
            ], $extra ?? [])
        );
    }

    protected function companyForLead(Lead $lead): ?Company
    {
        return Company::find($lead->company_id);
    }

    protected function managerPhone(Company $company): ?string
    {
        /*
        |--------------------------------------------------------------------------
        | Manager Phone Resolution
        |--------------------------------------------------------------------------
        |
        | Keep the existing behavior first:
        | - company.manager_phone
        | - company.phone
        |
        | Then normalize UAE local numbers.
        */

        $phone = null;

        foreach ([
            'manager_phone',
            'whatsapp_manager_phone',
            'phone',
            'whatsapp_phone',
        ] as $field) {
            try {
                if (
                    Schema::hasColumn($company->getTable(), $field)
                    && ! empty($company->{$field})
                ) {
                    $phone = $company->{$field};
                    break;
                }
            } catch (\Throwable $e) {
                // Continue fallback.
            }
        }

        $phone = trim((string) $phone);
        $phone = preg_replace('/^whatsapp:/i', '', $phone);
        $phone = preg_replace('/\D+/', '', $phone);

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone;
    }

    protected function customerName(Lead $lead): string
    {
        return $this->safeText(
            $lead->name ?: $lead->client?->name,
            'Customer'
        );
    }

    protected function customerPhone(Lead $lead): string
    {
        return $this->safeText(
            $lead->phone_norm
                ?: $lead->phone
                ?: $lead->whatsapp
                ?: $lead->whatsapp_number
                ?: $lead->client?->phone,
            'Not available'
        );
    }

    protected function vehicleLabel(Lead $lead): string
    {
        $lead->loadMissing([
            'opportunity.vehicle.make',
            'opportunity.vehicle.model',
            'vehicleMake',
            'vehicleModel',
        ]);

        if ($lead->opportunity?->vehicle_label) {
            return $this->safeText($lead->opportunity->vehicle_label, 'Vehicle not captured');
        }

        if ($lead->vehicle_label) {
            return $this->safeText($lead->vehicle_label, 'Vehicle not captured');
        }

        $make = $lead->vehicleMake?->name ?: $lead->other_make;
        $model = $lead->vehicleModel?->name ?: $lead->other_model;

        if (! $make && $lead->opportunity?->vehicle) {
            $make = $lead->opportunity->vehicle->make?->name;
            $model = $lead->opportunity->vehicle->model?->name;
        }

        $label = trim(($make ?? '') . ' ' . ($model ?? ''));

        return $this->safeText($label, 'Vehicle not captured');
    }

    protected function preferredDateTimeLabel(?Carbon $preferredAt): string
    {
        if (! $preferredAt) {
            return 'Not selected';
        }

        return $preferredAt->format('d M Y, h:i A');
    }

    protected function leadForBooking(Booking $booking): ?Lead
    {
        $booking->loadMissing([
            'opportunity.lead',
        ]);

        if ($booking->opportunity?->lead) {
            $lead = $booking->opportunity->lead;

            return (int) $lead->company_id === (int) $booking->company_id ? $lead : null;
        }

        if (! empty($booking->lead_id)) {
            return Lead::where('company_id', $booking->company_id)
                ->find($booking->lead_id);
        }

        if ($booking->opportunity_id) {
            $opportunity = Opportunity::where('company_id', $booking->company_id)
                ->with('lead')
                ->find($booking->opportunity_id);

            if ($opportunity?->lead && (int) $opportunity->lead->company_id === (int) $booking->company_id) {
                return $opportunity->lead;
            }
        }

        return null;
    }

    protected function bookingDateTime(Booking $booking): ?Carbon
    {
        foreach ([
            'scheduled_at',
            'starts_at',
            'start_at',
            'booking_at',
            'appointment_at',
            'preferred_at',
        ] as $field) {
            try {
                if (
                    Schema::hasColumn($booking->getTable(), $field)
                    && ! empty($booking->{$field})
                ) {
                    return Carbon::parse($booking->{$field});
                }
            } catch (\Throwable $e) {
                // Continue fallback.
            }
        }

        if (! empty($booking->booking_date) && ! empty($booking->booking_time)) {
            return Carbon::parse($booking->booking_date . ' ' . $booking->booking_time);
        }

        if (! empty($booking->booking_date)) {
            return Carbon::parse($booking->booking_date);
        }

        return null;
    }

    protected function bookingTimeLabel(Booking $booking): string
    {
        foreach ([
            'booking_time',
            'preferred_time',
            'time',
            'start_time',
        ] as $field) {
            try {
                if (
                    Schema::hasColumn($booking->getTable(), $field)
                    && ! empty($booking->{$field})
                ) {
                    return Carbon::parse($booking->{$field})->format('h:i A');
                }
            } catch (\Throwable $e) {
                // Continue fallback.
            }
        }

        return (string) ($booking->slot_label ?? $booking->slot ?? '-');
    }

    protected function lastCustomerMessage(Lead $lead): string
    {
        $data = $lead->conversation_data ?? [];
        $data = is_array($data) ? $data : [];

        $message = $data['last_user_message']
            ?? $data['last_customer_message']
            ?? $data['last_message']
            ?? null;

        return $this->safeText($message, 'Not available');
    }

    protected function leadUrl(Lead $lead): ?string
    {
        try {
            if (function_exists('route')) {
                return route('admin.leads.show', $lead->id);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function bookingUrl(Lead $lead, int $bookingId): ?string
    {
        try {
            if (function_exists('route')) {
                return route('admin.bookings.show', $bookingId);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function eventKeyFromExtra(array $extra, ?int $bookingId = null): string
    {
        $eventKey = trim((string) ($extra['event_key'] ?? ''));

        if ($eventKey !== '') {
            return $eventKey;
        }

        $source = strtolower(trim((string) ($extra['source'] ?? '')));

        if (str_contains($source, 'reschedule')) {
            return self::EVENT_MANAGER_RESCHEDULE_REQUESTED;
        }

        if ($bookingId) {
            return self::EVENT_MANAGER_BOOKING_CONFIRMATION;
        }

        return self::EVENT_MANAGER_ATTENTION;
    }

    protected function managerLockKey(
        int $companyId,
        int $leadId,
        string $reason,
        ?int $bookingId,
        array $extra
    ): string {
        $eventKey = $this->eventKeyFromExtra($extra, $bookingId);

        return 'manager_notification:' . sha1(json_encode([
            'company_id' => $companyId,
            'lead_id' => $leadId,
            'booking_id' => $bookingId,
            'event_key' => $eventKey,
            'reason' => mb_substr($reason, 0, 120),
            'source' => $extra['source'] ?? null,
            'customer_action' => $extra['customer_action'] ?? null,
        ]));
    }

    protected function safeText(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        return mb_substr($value, 0, 250);
    }
}