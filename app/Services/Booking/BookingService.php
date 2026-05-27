<?php

namespace App\Services\Booking;

use App\Models\Client\Lead;
use App\Models\Job\Booking;
use App\Models\System\Company;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BookingService
{
    public function __construct(
        protected BookingStateService $stateService
    ) {}

    public function create(Lead $lead, Carbon $scheduledAt, string $slot = 'morning'): Booking
    {
        return DB::transaction(function () use ($lead, $scheduledAt, $slot) {

            $lead->refresh();

            if ($scheduledAt->isPast()) {
                throw new \Exception('Cannot create booking for a past date/time.');
            }

            /*
            |--------------------------------------------------------------------------
            | Company Working Hours Protection
            |--------------------------------------------------------------------------
            | Prevent bookings outside garage working hours configured in Launch Setup.
            */
            $workingHoursViolation = $this->workingHoursViolation($lead, $scheduledAt);

            if ($workingHoursViolation) {
                throw new \Exception($workingHoursViolation);
            }

            $opportunity = $lead->opportunity;
            $conversationData = $this->conversationData($lead);

            $serviceType = $opportunity?->service_type
                ?: $lead->getMemoryValue('service_type')
                ?: ($conversationData['service_type'] ?? null);

            $slot = $this->inferSlotFromTime($scheduledAt, $slot);
            $vehicleId = $this->resolveVehicleId($lead);

            $data = [
                'company_id'     => $lead->company_id,
                'client_id'      => $lead->client_id,
                'opportunity_id' => $opportunity?->id,
                'service_type'   => $serviceType,
                'slot'           => $slot,
                'notes'          => $this->buildNotes($lead, $scheduledAt, $serviceType),
            ];

            if (Schema::hasColumn('bookings', 'lead_id')) {
                $data['lead_id'] = $lead->id;
            }

            if (Schema::hasColumn('bookings', 'scheduled_at')) {
                $data['scheduled_at'] = $scheduledAt;
            }

            if (Schema::hasColumn('bookings', 'booking_date')) {
                $data['booking_date'] = $scheduledAt->toDateString();
            }

            if (Schema::hasColumn('bookings', 'booking_time')) {
                $data['booking_time'] = $scheduledAt->format('H:i:s');
            }

            if (Schema::hasColumn('bookings', 'name')) {
                $data['name'] = $lead->name ?: 'WhatsApp Lead';
            }

            if (Schema::hasColumn('bookings', 'vehicle_id')) {
                $data['vehicle_id'] = $vehicleId;
            }

            if (Schema::hasColumn('bookings', 'status')) {
                $data['status'] = Booking::STATUS_PENDING;
            }

            if (Schema::hasColumn('bookings', 'is_archived')) {
                $data['is_archived'] = false;
            }

            if (Schema::hasColumn('bookings', 'expected_close_date')) {
                $data['expected_close_date'] = $scheduledAt->toDateString();
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate Booking Protection
            |--------------------------------------------------------------------------
            | Same company + client + same date + same slot should not create duplicate.
            */

            $existing = Booking::query()
                ->where('company_id', $lead->company_id)
                ->where('client_id', $lead->client_id)
                ->when(
                    $opportunity?->id && Schema::hasColumn('bookings', 'opportunity_id'),
                    fn ($q) => $q->where('opportunity_id', $opportunity->id)
                )
                ->when(
                    Schema::hasColumn('bookings', 'booking_date'),
                    fn ($q) => $q->whereDate('booking_date', $scheduledAt->toDateString())
                )
                ->when(
                    Schema::hasColumn('bookings', 'slot'),
                    fn ($q) => $q->where('slot', $slot)
                )
                ->when(
                    Schema::hasColumn('bookings', 'is_archived'),
                    fn ($q) => $q->where('is_archived', false)
                )
                ->when(
                    Schema::hasColumn('bookings', 'status'),
                    fn ($q) => $q->where('status', '!=', Booking::STATUS_CANCELED)
                )
                ->latest()
                ->first();

            if ($existing) {
                Log::info('[BookingService] Existing booking reused', [
                    'booking_id' => $existing->id,
                    'lead_id'    => $lead->id,
                    'client_id'  => $lead->client_id,
                    'date'       => $scheduledAt->toDateString(),
                    'slot'       => $slot,
                ]);

                return $existing;
            }

            $booking = Booking::create($data);

            Log::info('[BookingService] Booking created', [
                'booking_id' => $booking->id,
                'lead_id'    => $lead->id,
                'client_id'  => $lead->client_id,
                'date'       => $scheduledAt->toDateString(),
                'slot'       => $slot,
            ]);

            return $booking;
        });
    }

    public function confirm(Booking $booking): Booking
    {
        return $this->stateService->transition($booking, Booking::STATUS_CONFIRMED);
    }

    public function cancel(Booking $booking): Booking
    {
        return $this->stateService->transition($booking, Booking::STATUS_CANCELED);
    }

    public function parsePreferredDateTime(string $text): ?Carbon
    {
        $original = trim($text);

        try {
            if ($original === '') {
                return null;
            }

            $normalized = strtolower($original);
            $normalized = preg_replace('/(\d+)(st|nd|rd|th)\b/i', '$1', $normalized);
            $normalized = preg_replace('/\s+/', ' ', $normalized);

            [$parsedHour, $parsedMinute] = $this->extractPreferredTime($normalized);

            if (str_contains($normalized, 'tomorrow')) {
                return Carbon::tomorrow()->setTime($parsedHour, $parsedMinute);
            }

            if (str_contains($normalized, 'today')) {
                $date = Carbon::today()->setTime($parsedHour, $parsedMinute);

                return $date->isPast() ? null : $date;
            }

            /*
            |--------------------------------------------------------------------------
            | Weekday handling
            |--------------------------------------------------------------------------
            */

            $days = [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ];

            foreach ($days as $day) {
                if (preg_match('/\b(this|next)?\s*' . $day . '\b/', $normalized, $m)) {
                    $prefix = trim($m[1] ?? '');

                    if ($prefix === 'this') {
                        $date = Carbon::parse($day)->setTime($parsedHour, $parsedMinute);

                        if ($date->isPast()) {
                            $date = Carbon::parse("next {$day}")->setTime($parsedHour, $parsedMinute);
                        }

                        return $date;
                    }

                    $date = Carbon::parse("next {$day}")->setTime($parsedHour, $parsedMinute);

                    return $date->isPast() ? null : $date;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Direct date parsing
            |--------------------------------------------------------------------------
            */

            $parsed = Carbon::parse($original);

            if (
                $parsed->format('H:i:s') === '00:00:00'
                && !$this->hasExplicitTime($normalized)
            ) {
                $parsed->setTime($parsedHour, $parsedMinute);
            }

            if ($parsed->isPast()) {
                return null;
            }

            return $parsed;

        } catch (\Throwable $e) {
            Log::debug('[BookingService] Date parse failed', [
                'text' => $original,
                'err'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function isWithinCompanyWorkingHours(Lead $lead, Carbon $scheduledAt): bool
    {
        return $this->workingHoursViolation($lead, $scheduledAt) === null;
    }

    public function workingHoursMessage(Lead $lead): string
    {
        $company = $this->companyForLead($lead);
        $workingHours = $this->companyWorkingHours($company);

        $openTime = $workingHours['open_time'] ?? null;
        $closeTime = $workingHours['close_time'] ?? null;
        $weeklyOff = $workingHours['weekly_off'] ?? null;

        if ($openTime && $closeTime && $weeklyOff) {
            return "Our garage working hours are {$openTime} to {$closeTime}. Weekly off: {$weeklyOff}. Please choose a time within working hours.";
        }

        if ($openTime && $closeTime) {
            return "Our garage working hours are {$openTime} to {$closeTime}. Please choose a time within working hours.";
        }

        return 'Please choose a valid time during garage working hours.';
    }

    public function workingHoursViolation(Lead $lead, Carbon $scheduledAt): ?string
    {
        $company = $this->companyForLead($lead);
        $workingHours = $this->companyWorkingHours($company);

        if (empty($workingHours)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Weekly Off Check
        |--------------------------------------------------------------------------
        */
        $weeklyOff = strtolower(trim((string) ($workingHours['weekly_off'] ?? '')));

        if ($weeklyOff !== '') {
            $selectedDay = strtolower($scheduledAt->format('l'));

            if ($weeklyOff === $selectedDay) {
                return 'Garage is closed on ' . ucfirst($selectedDay) . '. Please choose another working day.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Opening / Closing Time Check
        |--------------------------------------------------------------------------
        */
        $openTimeText = trim((string) ($workingHours['open_time'] ?? ''));
        $closeTimeText = trim((string) ($workingHours['close_time'] ?? ''));

        if ($openTimeText === '' || $closeTimeText === '') {
            return null;
        }

        $openTime = $this->parseCompanyTime($scheduledAt, $openTimeText);
        $closeTime = $this->parseCompanyTime($scheduledAt, $closeTimeText);

        if (!$openTime || !$closeTime) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Normal same-day hours
        |--------------------------------------------------------------------------
        | Example: 8:00 AM to 5:00 PM
        */
        if ($closeTime->greaterThan($openTime)) {
            if ($scheduledAt->lessThan($openTime) || $scheduledAt->greaterThan($closeTime)) {
                return "Garage working hours are {$openTimeText} to {$closeTimeText}. Please choose a time within working hours.";
            }

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Overnight hours support
        |--------------------------------------------------------------------------
        | Example: 8:00 PM to 2:00 AM
        */
        if ($scheduledAt->greaterThanOrEqualTo($openTime) || $scheduledAt->lessThanOrEqualTo($closeTime)) {
            return null;
        }

        return "Garage working hours are {$openTimeText} to {$closeTimeText}. Please choose a time within working hours.";
    }

    protected function extractPreferredTime(string $text): array
    {
        $hour = 10;
        $minute = 0;

        /*
        |--------------------------------------------------------------------------
        | Explicit AM/PM
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $text, $m)) {
            $hour = (int) $m[1];
            $minute = isset($m[2]) ? (int) $m[2] : 0;
            $ampm = strtolower($m[3]);

            if ($hour < 1 || $hour > 12 || $minute > 59) {
                return [10, 0];
            }

            if ($ampm === 'pm' && $hour < 12) {
                $hour += 12;
            }

            if ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }

            return [$hour, $minute];
        }

        /*
        |--------------------------------------------------------------------------
        | 24-hour format
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $text, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        /*
        |--------------------------------------------------------------------------
        | Bare hour with context
        |--------------------------------------------------------------------------
        | Example: tomorrow 10, saturday at 4
        */

        if (preg_match('/\b(?:today|tomorrow|monday|tuesday|wednesday|thursday|friday|saturday|sunday)\s+(?:at\s+)?(\d{1,2})\b/i', $text, $m)) {
            $bareHour = (int) $m[1];

            if ($bareHour >= 8 && $bareHour <= 11) {
                return [$bareHour, 0];
            }

            if ($bareHour >= 1 && $bareHour <= 7) {
                return [$bareHour + 12, 0];
            }

            if ($bareHour === 12) {
                return [12, 0];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Day part words
        |--------------------------------------------------------------------------
        */

        if (str_contains($text, 'afternoon')) {
            return [15, 0];
        }

        if (str_contains($text, 'evening')) {
            return [18, 0];
        }

        if (str_contains($text, 'morning')) {
            return [10, 0];
        }

        return [$hour, $minute];
    }

    protected function hasExplicitTime(string $text): bool
    {
        return preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $text)
            || preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $text);
    }

    public function inferSlotFromTime(Carbon $dt, string $fallback = 'morning'): string
    {
        $hour = (int) $dt->format('H');

        if ($hour >= 6 && $hour < 14) {
            return 'morning';
        }

        if ($hour >= 14 && $hour <= 23) {
            return 'evening';
        }

        return $this->normalizeBookingSlot($fallback);
    }

    public function normalizeBookingSlot(?string $slot): string
    {
        $normalized = strtolower(trim((string) $slot));

        return match ($normalized) {
            'morning' => 'morning',
            'afternoon' => 'afternoon',
            'evening' => 'evening',
            'full_day', 'full day', 'fullday' => 'full_day',
            default => 'morning',
        };
    }

    protected function buildNotes(Lead $lead, Carbon $scheduledAt, ?string $serviceType): string
    {
        $lead->refresh();

        $lines = ['Created from conversation'];

        if ($serviceType) {
            $lines[] = 'Service: ' . $serviceType;
        }

        $lines[] = 'Preferred time: ' . $scheduledAt->toDateTimeString();

        $vehicle = $this->getVehicleLabel($lead);

        if ($vehicle !== '') {
            $lines[] = 'Vehicle: ' . $vehicle;
        }

        if ($lead->source) {
            $lines[] = 'Lead source: ' . $lead->source;
        }

        return implode("\n", $lines);
    }

    protected function getVehicleLabel(Lead $lead): string
    {
        $lead->refresh();

        $opportunity = $lead->opportunity;

        if ($opportunity?->vehicle_label) {
            return trim((string) $opportunity->vehicle_label);
        }

        if ($opportunity?->vehicle?->make) {
            return trim(
                ($opportunity->vehicle->make->name ?? '') . ' ' .
                ($opportunity->vehicle->model->name ?? '')
            );
        }

        if ($lead->vehicle_make_id || $lead->vehicle_model_id) {
            $make = $lead->vehicle_make_id
                ? VehicleMake::find($lead->vehicle_make_id)?->name
                : null;

            $model = $lead->vehicle_model_id
                ? VehicleModel::find($lead->vehicle_model_id)?->name
                : null;

            $label = trim(($make ?? '') . ' ' . ($model ?? ''));

            if ($label !== '') {
                return $label;
            }
        }

        $label = trim(($lead->other_make ?? '') . ' ' . ($lead->other_model ?? ''));

        return $label;
    }

    protected function resolveVehicleId(Lead $lead): ?int
    {
        $lead->refresh();

        $opportunity = $lead->opportunity;

        if ($opportunity?->vehicle_id) {
            return (int) $opportunity->vehicle_id;
        }

        $makeId = $lead->vehicle_make_id ?: $opportunity?->vehicle_make_id;
        $modelId = $lead->vehicle_model_id ?: $opportunity?->vehicle_model_id;
        $otherMake = $lead->other_make ?: $opportunity?->other_make;
        $otherModel = $lead->other_model ?: $opportunity?->other_model;

        $make = null;

        if ($makeId) {
            $make = VehicleMake::find($makeId);
        } elseif (!empty($otherMake)) {
            $make = VehicleMake::firstOrCreate([
                'name' => $this->formatVehicleName($otherMake),
            ]);
        }

        $model = null;

        if ($modelId) {
            $model = VehicleModel::find($modelId);
        } elseif ($make && !empty($otherModel)) {
            $model = VehicleModel::firstOrCreate([
                'make_id' => $make->id,
                'name'    => $this->formatVehicleName($otherModel),
            ]);
        }

        if (!$make) {
            return null;
        }

        $vehicle = Vehicle::query()
            ->where('company_id', $lead->company_id)
            ->where('client_id', $lead->client_id)
            ->where('make_id', $make->id)
            ->where('model_id', $model?->id)
            ->first();

        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'company_id' => $lead->company_id,
                'client_id'  => $lead->client_id,
                'make_id'    => $make->id,
                'model_id'   => $model?->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Sync vehicle back to opportunity and lead
        |--------------------------------------------------------------------------
        | This keeps the CRM clean after unknown text vehicles are converted into
        | vehicle_makes / vehicle_models / vehicles.
        */

        if ($opportunity) {
            $opportunity->update([
                'vehicle_id'       => $vehicle->id,
                'vehicle_make_id'  => $make->id,
                'vehicle_model_id' => $model?->id,
                'other_make'       => null,
                'other_model'      => null,
            ]);
        }

        $lead->update([
            'vehicle_make_id'  => $make->id,
            'vehicle_model_id' => $model?->id,
            'other_make'       => null,
            'other_model'      => null,
        ]);

        return (int) $vehicle->id;
    }

    protected function formatVehicleName(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        if (preg_match('/[a-z]/i', $value) && preg_match('/[0-9]/', $value)) {
            return strtoupper($value);
        }

        if (strlen($value) <= 3 && strtoupper($value) === $value) {
            return $value;
        }

        return ucfirst(strtolower($value));
    }

    protected function companyForLead(Lead $lead): ?Company
    {
        return Company::find($lead->company_id);
    }

    protected function companyWorkingHours(?Company $company): array
    {
        if (!$company) {
            return [];
        }

        $workingHours = $company->working_hours ?? [];

        if (is_array($workingHours)) {
            return $workingHours;
        }

        if (is_string($workingHours)) {
            $decoded = json_decode($workingHours, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function parseCompanyTime(Carbon $scheduledAt, string $time): ?Carbon
    {
        try {
            return Carbon::parse($scheduledAt->toDateString() . ' ' . $time);
        } catch (\Throwable $e) {
            Log::debug('[BookingService] Company working time parse failed', [
                'time' => $time,
                'date' => $scheduledAt->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function conversationData(Lead $lead): array
    {
        $data = $lead->conversation_data ?? [];

        return is_array($data) ? $data : [];
    }
}