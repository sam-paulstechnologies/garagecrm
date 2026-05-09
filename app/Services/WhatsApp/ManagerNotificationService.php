<?php

namespace App\Services\WhatsApp;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\System\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ManagerNotificationService
{
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Event Key
    |--------------------------------------------------------------------------
    |
    | Manager alerts are proactive system-to-manager messages.
    | They must use approved Meta templates through DB mapping.
    |
    | Expected DB event key:
    |   manager.attention_required
    |
    | Recommended Meta template:
    |   manager_attention_required_v1
    |
    */

    public const EVENT_MANAGER_ATTENTION = 'manager.attention_required';

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

        $customerName   = $this->customerName($lead);
        $customerPhone  = $this->customerPhone($lead);
        $safeReason     = $this->safeText($reason, 'Manager attention required');
        $vehicleLabel   = $this->vehicleLabel($lead);
        $preferredLabel = $this->preferredDateTimeLabel($preferredAt);

        try {
            /** @var SendWhatsAppMessage $sender */
            $sender = app(SendWhatsAppMessage::class);

            /*
            |--------------------------------------------------------------------------
            | fireEvent signature
            |--------------------------------------------------------------------------
            |
            | Actual method:
            | fireEvent(int $companyId, string $eventKey, string $toE164, array $vars = [])
            |
            | Use positional parameters only.
            |
            */

            $sender->fireEvent(
                (int) $company->id,
                self::EVENT_MANAGER_ATTENTION,
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

                    /*
                    |--------------------------------------------------------------------------
                    | Context variables
                    |--------------------------------------------------------------------------
                    */

                    'company_id'         => (int) $company->id,
                    'lead_id'            => (int) $lead->id,
                    'recipient_type'     => 'manager',
                    'manager_phone'      => $managerPhone,
                    'opportunity_id'     => $lead->opportunity?->id,
                    'source'             => 'manager_notification_service',
                    'event_key'          => self::EVENT_MANAGER_ATTENTION,
                    'action'             => 'manager_attention',
                    'send_mode'          => 'meta_template',
                ], $extra ?? [])
            );

            Log::info('[ManagerNotification] Manager WhatsApp event fired', [
                'lead_id'       => $lead->id,
                'company_id'    => $company->id,
                'manager_phone' => $managerPhone,
                'event_key'     => self::EVENT_MANAGER_ATTENTION,
                'reason'        => $safeReason,
                'booking_id'    => $bookingId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ManagerNotification] Manager WhatsApp event failed', [
                'lead_id'       => $lead->id,
                'company_id'    => $company->id,
                'manager_phone' => $managerPhone,
                'event_key'     => self::EVENT_MANAGER_ATTENTION,
                'reason'        => $safeReason,
                'booking_id'    => $bookingId,
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
                'booking_status' => $booking->status,
                'booking_slot'   => $booking->slot,
            ], $extra ?? [])
        );
    }

    protected function companyForLead(Lead $lead): ?Company
    {
        return Company::find($lead->company_id);
    }

    protected function managerPhone(Company $company): ?string
    {
        $phone = $company->manager_phone ?: $company->phone;

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
            $lead->phone_norm ?: $lead->phone ?: $lead->client?->phone,
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

        $label = trim(($lead->other_make ?? '') . ' ' . ($lead->other_model ?? ''));

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
                ->find($booking->opportunity_id);

            if ($opportunity?->lead && (int) $opportunity->lead->company_id === (int) $booking->company_id) {
                return $opportunity->lead;
            }
        }

        return null;
    }

    protected function bookingDateTime(Booking $booking): ?Carbon
    {
        if (! empty($booking->scheduled_at)) {
            return Carbon::parse($booking->scheduled_at);
        }

        if (! empty($booking->booking_date) && ! empty($booking->booking_time)) {
            return Carbon::parse($booking->booking_date . ' ' . $booking->booking_time);
        }

        if (! empty($booking->booking_date)) {
            return Carbon::parse($booking->booking_date);
        }

        return null;
    }

    protected function safeText(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        return mb_substr($value, 0, 250);
    }
}