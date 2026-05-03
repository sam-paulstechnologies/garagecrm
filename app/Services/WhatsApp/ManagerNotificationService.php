<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\System\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ManagerNotificationService
{
    public const TEMPLATE_MANAGER_ATTENTION = 'manager_attention_required_v1';

    public function notifyForLead(
        Lead $lead,
        string $reason,
        ?Carbon $preferredAt = null,
        ?int $bookingId = null,
        ?array $extra = []
    ): void {
        $lead->refresh();

        $company = $this->companyForLead($lead);

        if (!$company) {
            Log::warning('[ManagerNotification] Company missing, notification skipped', [
                'lead_id' => $lead->id,
                'company_id' => $lead->company_id,
                'reason' => $reason,
            ]);

            return;
        }

        $managerPhone = $this->managerPhone($company);

        if (!$managerPhone) {
            Log::warning('[ManagerNotification] Manager phone missing, notification skipped', [
                'lead_id' => $lead->id,
                'company_id' => $company->id,
                'reason' => $reason,
            ]);

            return;
        }

        $placeholders = [
            $this->customerName($lead),
            $this->customerPhone($lead),
            $this->safeText($reason, 'Manager attention required'),
            $this->vehicleLabel($lead),
            $this->preferredDateTimeLabel($preferredAt),
        ];

        SendWhatsAppFromTemplate::dispatch(
            companyId: (int) $company->id,
            leadId: (int) $lead->id,
            toNumberE164: $managerPhone,
            templateName: self::TEMPLATE_MANAGER_ATTENTION,
            placeholders: $placeholders,
            links: [],
            context: array_merge([
                'force_template' => true,
                'recipient_type' => 'manager',
                'manager_phone' => $managerPhone,
                'reason' => $reason,
                'booking_id' => $bookingId,
                'opportunity_id' => $lead->opportunity?->id,
                'source' => 'manager_notification_service',
            ], $extra ?? []),
            action: 'manager_attention'
        );

        Log::info('[ManagerNotification] Manager WhatsApp notification queued', [
            'lead_id' => $lead->id,
            'company_id' => $company->id,
            'manager_phone' => $managerPhone,
            'template' => self::TEMPLATE_MANAGER_ATTENTION,
            'reason' => $reason,
            'booking_id' => $bookingId,
        ]);
    }

    public function notifyForBooking(
        Booking $booking,
        string $reason = 'Booking requires manager confirmation',
        ?array $extra = []
    ): void {
        $booking->refresh();

        $lead = $this->leadForBooking($booking);

        if (!$lead) {
            Log::warning('[ManagerNotification] Lead missing for booking, notification skipped', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'reason' => $reason,
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
                'booking_slot' => $booking->slot,
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
        if (!$preferredAt) {
            return 'Not selected';
        }

        return $preferredAt->format('d M Y, h:i A');
    }

    protected function leadForBooking(Booking $booking): ?Lead
    {
        if ($booking->opportunity?->lead) {
            return $booking->opportunity->lead;
        }

        if (!empty($booking->lead_id)) {
            return Lead::find($booking->lead_id);
        }

        if ($booking->opportunity_id) {
            $opportunity = Opportunity::find($booking->opportunity_id);

            if ($opportunity?->lead) {
                return $opportunity->lead;
            }
        }

        return null;
    }

    protected function bookingDateTime(Booking $booking): ?Carbon
    {
        if (!empty($booking->scheduled_at)) {
            return Carbon::parse($booking->scheduled_at);
        }

        if (!empty($booking->booking_date) && !empty($booking->booking_time)) {
            return Carbon::parse($booking->booking_date . ' ' . $booking->booking_time);
        }

        if (!empty($booking->booking_date)) {
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