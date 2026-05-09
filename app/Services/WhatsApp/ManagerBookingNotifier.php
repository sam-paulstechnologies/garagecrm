<?php

namespace App\Services\WhatsApp;

use App\Models\Client\Opportunity;
use App\Models\User;
use App\Services\Booking\BookingLinkGenerator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ManagerBookingNotifier
{
    protected string $eventKey = 'manager.attention_required';

    public function notify(Opportunity $opportunity): void
    {
        $opportunity = $opportunity->fresh([
            'client',
            'assignee',
            'vehicle',
            'vehicleMake',
            'vehicleModel',
        ]);

        if (! $opportunity) {
            Log::warning('[ManagerBookingNotifier] Opportunity not found after refresh');
            return;
        }

        $manager = $this->resolveManagerUser($opportunity);
        $managerPhone = $this->phoneFromUser($manager) ?: $this->cleanPhone(config('whatsapp.default_manager'));

        if (! $managerPhone) {
            Log::warning('[ManagerBookingNotifier] No manager phone found', [
                'opportunity_id' => $opportunity->id,
                'company_id' => $opportunity->company_id,
                'assigned_to' => $opportunity->assigned_to,
            ]);

            return;
        }

        $clientName = $opportunity->client?->name ?? 'Customer';
        $clientPhone = $opportunity->client?->phone_norm
            ?? $opportunity->client?->phone
            ?? $opportunity->client?->whatsapp
            ?? '-';

        $vehicle = $opportunity->vehicle_label ?? 'Vehicle not specified';
        $preferredDateTime = $this->preferredDateTime($opportunity);
        $link = app(BookingLinkGenerator::class)->generate($opportunity);

        try {
            app(SendWhatsAppMessage::class)->fireEvent(
                (int) $opportunity->company_id,
                $this->eventKey,
                (string) $managerPhone,
                [
                    /*
                    |--------------------------------------------------------------------------
                    | Template variables for manager_attention_required_v1
                    |--------------------------------------------------------------------------
                    | Your Meta template expects 5 variables.
                    |--------------------------------------------------------------------------
                    */

                    'customer_name' => $clientName,
                    'name' => $clientName,

                    'phone' => $clientPhone,
                    'customer_phone' => $clientPhone,

                    'vehicle' => $vehicle,
                    'car' => $vehicle,

                    'preferred_time' => $preferredDateTime,
                    'preferred_datetime' => $preferredDateTime,
                    'date_time' => $preferredDateTime,

                    'reason' => "Booking confirmation required. Open link: {$link}",

                    /*
                    |--------------------------------------------------------------------------
                    | Context
                    |--------------------------------------------------------------------------
                    */

                    'company_id' => (int) $opportunity->company_id,
                    'opportunity_id' => (int) $opportunity->id,
                    'lead_id' => $opportunity->lead_id ? (int) $opportunity->lead_id : null,
                    'client_id' => $opportunity->client_id ? (int) $opportunity->client_id : null,
                    'source' => 'manager_booking_notifier',
                    'action' => 'manager_booking_confirmation_required',
                    'event_key' => $this->eventKey,
                    'booking_link' => $link,
                    'send_mode' => 'meta_template',
                ]
            );

            Log::info('[ManagerBookingNotifier] Manager template notification fired', [
                'opportunity_id' => $opportunity->id,
                'company_id' => $opportunity->company_id,
                'manager_phone' => $managerPhone,
                'event_key' => $this->eventKey,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ManagerBookingNotifier] Failed to fire manager template notification', [
                'opportunity_id' => $opportunity->id,
                'company_id' => $opportunity->company_id,
                'manager_phone' => $managerPhone,
                'event_key' => $this->eventKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function resolveManagerUser(Opportunity $opportunity): ?User
    {
        if ($opportunity->assignee && $this->phoneFromUser($opportunity->assignee)) {
            return $opportunity->assignee;
        }

        if ($opportunity->assigned_to) {
            $assignedUser = User::query()
                ->where('company_id', $opportunity->company_id)
                ->where('id', $opportunity->assigned_to)
                ->first();

            if ($assignedUser && $this->phoneFromUser($assignedUser)) {
                return $assignedUser;
            }
        }

        $query = User::query()
            ->where('company_id', $opportunity->company_id)
            ->whereIn('role', ['manager', 'admin']);

        if (Schema::hasColumn('users', 'phone')) {
            $query->whereNotNull('phone');
        }

        return $query
            ->orderByRaw("FIELD(role, 'manager', 'admin')")
            ->first();
    }

    protected function phoneFromUser(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (['phone', 'mobile', 'contact_number'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                continue;
            }

            if ($phone = $this->cleanPhone($user->{$column} ?? null)) {
                return $phone;
            }
        }

        return null;
    }

    protected function preferredDateTime(Opportunity $opportunity): string
    {
        $date = $opportunity->expected_close_date
            ? $opportunity->expected_close_date->format('d M Y')
            : null;

        if ($date) {
            return $date;
        }

        return 'Check booking request';
    }

    protected function cleanPhone(mixed $value): ?string
    {
        $phone = trim((string) $value);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone) ?: '';

        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (str_starts_with($phone, '05') && strlen($phone) === 10) {
            return '+971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '5') && strlen($phone) === 9) {
            return '+971' . $phone;
        }

        if (! str_starts_with($phone, '+') && preg_match('/^\d{8,15}$/', $phone)) {
            return '+' . $phone;
        }

        return $phone;
    }
}