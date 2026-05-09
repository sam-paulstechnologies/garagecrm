<?php

namespace App\Services\Conversation\Flows;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Services\Booking\BookingService;
use App\Services\Conversation\ConversationGuard;
use App\Services\Leads\LeadConversionService;
use App\Services\WhatsApp\ManagerNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingFlow
{
    public function __construct(
        protected BookingService $bookingService,
        protected LeadConversionService $leadConversionService,
        protected ConversationGuard $guard,
        protected ManagerNotificationService $managerNotificationService
    ) {}

    /**
     * STEP 1 → Capture time and ask confirmation
     */
    public function handleTimeslot(Lead $lead, string $text): array
    {
        $normalizedText = $this->normalizeTimeslotText($text, $lead);

        $date = $this->bookingService->parsePreferredDateTime($normalizedText);

        /*
        |--------------------------------------------------------------------------
        | Invalid date/time
        |--------------------------------------------------------------------------
        */

        if (! $date instanceof Carbon) {
            return $this->retryTimeslot(
                lead: $lead,
                reason: 'Invalid date/time received: ' . $text,
                customerMessage: 'I could not understand that date/time. Please share a clear preferred time, for example: Tuesday 10 AM.',
                selectedText: $text
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Past date/time protection
        |--------------------------------------------------------------------------
        */

        if ($date->isPast()) {
            return $this->retryTimeslot(
                lead: $lead,
                reason: 'Past date/time received: ' . $text,
                customerMessage: 'That time has already passed. Please choose a future date/time within garage working hours.',
                selectedText: $text,
                selectedAt: $date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Company Working Hours Protection
        |--------------------------------------------------------------------------
        | Outside-hours should guide the customer, not count as a failed attempt.
        |--------------------------------------------------------------------------
        */

        $workingHoursViolation = $this->bookingService->workingHoursViolation($lead, $date);

        if ($workingHoursViolation) {
            return $this->retryTimeslot(
                lead: $lead,
                reason: $workingHoursViolation,
                customerMessage: $this->outsideWorkingHoursMessage($lead, $date, $workingHoursViolation),
                selectedText: $text,
                selectedAt: $date,
                incrementAttempt: false
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Reset attempts + store pending booking
        |--------------------------------------------------------------------------
        */

        $data = $this->conversationData($lead);

        $slot = $this->bookingService->inferSlotFromTime($date, 'Morning');

        $data['timeslot_attempts'] = 0;
        $data['pending_booking'] = [
            'date'        => $date->toIso8601String(),
            'slot'        => $slot,
            'raw_text'    => $text,
            'captured_at' => now()->toIso8601String(),
        ];

        $lead->conversation_data = $data;
        $lead->conversation_state = 'confirm_booking';
        $lead->conversation_updated_at = now();
        $lead->save();

        $vehicleLabel = $this->getVehicleLabel($lead);
        $dateLabel = $date->format('d M Y, h:i A');

        return $this->sessionResponse(
            template: 'confirm_booking_v1',
            action: 'confirm_booking',
            body: $this->confirmBookingBody($vehicleLabel, $dateLabel),
            placeholders: [
                $vehicleLabel,
                $dateLabel,
            ],
            context: [
                'pending_date' => $date->toIso8601String(),
                'slot'         => $slot,
            ]
        );
    }

    /**
     * STEP 2 → Confirm booking
     */
    public function confirmBooking(Lead $lead, string $text): array
    {
        $input = strtolower(trim($text));

        /*
        |--------------------------------------------------------------------------
        | If user rejects/cancels confirmation
        |--------------------------------------------------------------------------
        */

        if ($this->looksLikeRejection($input)) {
            $data = $this->conversationData($lead);

            unset($data['pending_booking']);

            $lead->conversation_state = 'awaiting_timeslot';
            $lead->conversation_data = $data;
            $lead->conversation_updated_at = now();
            $lead->save();

            return $this->sessionResponse(
                template: 'ask_preferred_time_v1',
                action: 'change_timeslot',
                body: $this->askPreferredTimeBody($lead, 'No problem. Please share another preferred booking date and time.'),
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'reason' => 'User rejected pending booking time',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | If user sends another date/time while in confirm state
        |--------------------------------------------------------------------------
        */

        $maybeDate = $this->bookingService->parsePreferredDateTime(
            $this->normalizeTimeslotText($text, $lead)
        );

        if ($maybeDate instanceof Carbon && ! $this->looksLikeConfirmation($input)) {
            return $this->handleTimeslot($lead, $text);
        }

        /*
        |--------------------------------------------------------------------------
        | Confirmation unclear
        |--------------------------------------------------------------------------
        */

        if (! $this->looksLikeConfirmation($input)) {
            return $this->retryConfirmation(
                lead: $lead,
                reason: 'User did not confirm booking clearly: ' . $text
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate pending booking exists
        |--------------------------------------------------------------------------
        */

        $data = $this->conversationData($lead);

        $pending = $data['pending_booking'] ?? null;

        if (! $pending || empty($pending['date'])) {
            return $this->guard->escalateToManager(
                $lead,
                'Booking confirmation failed: missing pending booking data'
            );
        }

        $date = Carbon::parse($pending['date']);
        $slot = $pending['slot'] ?? $this->bookingService->inferSlotFromTime($date, 'Morning');

        /*
        |--------------------------------------------------------------------------
        | Past date protection again before creation
        |--------------------------------------------------------------------------
        */

        if ($date->isPast()) {
            unset($data['pending_booking']);

            $lead->conversation_state = 'awaiting_timeslot';
            $lead->conversation_data = $data;
            $lead->conversation_updated_at = now();
            $lead->save();

            return $this->sessionResponse(
                template: 'ask_preferred_time_v1',
                action: 'retry_timeslot',
                body: $this->askPreferredTimeBody($lead, 'The selected booking time is already in the past. Please share a new preferred date and time.'),
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'reason' => 'Pending booking time is already in the past',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Company Working Hours Protection
        |--------------------------------------------------------------------------
        | Re-check before booking creation in case pending data is stale.
        |--------------------------------------------------------------------------
        */

        $workingHoursViolation = $this->bookingService->workingHoursViolation($lead, $date);

        if ($workingHoursViolation) {
            unset($data['pending_booking']);

            $lead->conversation_state = 'awaiting_timeslot';
            $lead->conversation_data = $data;
            $lead->conversation_updated_at = now();
            $lead->save();

            return $this->retryTimeslot(
                lead: $lead,
                reason: $workingHoursViolation,
                customerMessage: $this->outsideWorkingHoursMessage($lead, $date, $workingHoursViolation),
                selectedText: $pending['raw_text'] ?? null,
                selectedAt: $date,
                incrementAttempt: false
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure client + opportunity before booking
        |--------------------------------------------------------------------------
        */

        try {
            $this->leadConversionService->ensureClientAndOpportunity($lead->id);
            $lead->refresh();
        } catch (\Throwable $e) {
            return $this->guard->escalateToManager(
                $lead,
                'Lead conversion before booking failed: ' . $e->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Slot validation
        |--------------------------------------------------------------------------
        */

        if (! $this->isSlotAvailable($lead, $date, $slot)) {
            unset($data['pending_booking']);

            $lead->conversation_state = 'awaiting_timeslot';
            $lead->conversation_data = $data;
            $lead->conversation_updated_at = now();
            $lead->save();

            $message = 'That slot is already unavailable. Please choose another date/time.';

            return $this->sessionResponse(
                template: 'ask_preferred_time_retry_v1',
                action: 'retry_timeslot',
                body: $this->askPreferredTimeBody($lead, $message),
                placeholders: [
                    $lead->name ?: 'there',
                    $message,
                ],
                context: [
                    'reason'           => 'Slot unavailable',
                    'customer_message' => $message,
                    'date'             => $date->toDateString(),
                    'slot'             => $slot,
                    'retry_signature'  => sha1('slot_unavailable|' . $date->toIso8601String() . '|' . $slot),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create booking
        |--------------------------------------------------------------------------
        */

        try {
            $booking = $this->bookingService->create($lead, $date, $slot);
        } catch (\Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Working Hours Retry
            |--------------------------------------------------------------------------
            | If BookingService rejects due to working hours, do not escalate.
            | Ask customer to select another time.
            |--------------------------------------------------------------------------
            */

            if (
                str_contains($e->getMessage(), 'working hours')
                || str_contains($e->getMessage(), 'Garage is closed')
            ) {
                unset($data['pending_booking']);

                $lead->conversation_state = 'awaiting_timeslot';
                $lead->conversation_data = $data;
                $lead->conversation_updated_at = now();
                $lead->save();

                return $this->retryTimeslot(
                    lead: $lead,
                    reason: $e->getMessage(),
                    customerMessage: $this->outsideWorkingHoursMessage($lead, $date, $e->getMessage()),
                    selectedText: $pending['raw_text'] ?? null,
                    selectedAt: $date,
                    incrementAttempt: false
                );
            }

            return $this->guard->escalateToManager(
                $lead,
                'Booking creation failed: ' . $e->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update opportunity
        |--------------------------------------------------------------------------
        */

        $lead->refresh();

        if ($lead->opportunity) {
            $lead->opportunity->update([
                'stage'               => Opportunity::STAGE_MANAGER_CONFIRMATION_PENDING,
                'next_follow_up'      => $date->toDateString(),
                'expected_close_date' => $date->toDateString(),
                'notes'               => $this->appendNote(
                    $lead->opportunity->notes,
                    'Booking requested via WhatsApp for ' . $date->format('Y-m-d H:i')
                ),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Notify manager via WhatsApp template
        |--------------------------------------------------------------------------
        |
        | This is proactive manager notification, so it must remain Meta-template
        | based through ManagerNotificationService.
        |
        |--------------------------------------------------------------------------
        */

        try {
            $this->managerNotificationService->notifyForLead(
                lead: $lead,
                reason: 'Booking confirmed by customer and awaiting manager approval',
                preferredAt: $date,
                bookingId: (int) $booking->id,
                extra: [
                    'slot'            => $slot,
                    'source'          => 'booking_flow',
                    'customer_action' => 'confirmed_booking_request',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[BookingFlow] Manager notification failed', [
                'lead_id'    => $lead->id,
                'booking_id' => $booking->id ?? null,
                'error'      => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear pending + handoff to manager
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | Do NOT save manager_confirmation_pending into leads.status.
        | That value belongs to opportunities.stage only.
        |--------------------------------------------------------------------------
        */

        unset($data['pending_booking']);

        $data['last_booking_at'] = now()->toIso8601String();
        $data['last_booking_id'] = $booking->id;
        $data['last_booking_slot'] = $slot;

        $lead->conversation_data = $data;
        $lead->conversation_state = 'human';
        $lead->conversation_updated_at = now();
        $lead->save();

        return $this->sessionResponse(
            template: 'manager_handoff_v1',
            action: 'booking_handoff',
            body: $this->bookingHandoffBody($lead),
            placeholders: [$lead->name ?: 'there'],
            context: [
                'booking_id' => $booking->id,
                'date'       => $date->toDateString(),
                'time'       => $date->format('H:i'),
                'slot'       => $slot,
                'reason'     => 'Booking confirmed by user and sent to manager',
            ]
        );
    }

    /**
     * Retry timeslot capture
     */
    protected function retryTimeslot(
        Lead $lead,
        string $reason,
        ?string $customerMessage = null,
        ?string $selectedText = null,
        ?Carbon $selectedAt = null,
        bool $incrementAttempt = true
    ): array {
        $data = $this->conversationData($lead);

        $attempts = (int) ($data['timeslot_attempts'] ?? 0);

        if ($incrementAttempt) {
            $attempts++;
        }

        $data['timeslot_attempts'] = $attempts;
        $data['last_timeslot_retry_reason'] = $reason;
        $data['last_timeslot_retry_message'] = $customerMessage;
        $data['last_timeslot_retry_selected_text'] = $selectedText;
        $data['last_timeslot_retry_selected_at'] = $selectedAt?->toIso8601String();
        $data['last_timeslot_retry_at'] = now()->toIso8601String();

        $lead->conversation_state = 'awaiting_timeslot';
        $lead->conversation_data = $data;
        $lead->conversation_updated_at = now();
        $lead->save();

        /*
        |--------------------------------------------------------------------------
        | Escalate only when this retry actually counted as a failed attempt.
        | Outside-hours retries pass incrementAttempt=false, so they keep guiding.
        |--------------------------------------------------------------------------
        */

        if ($incrementAttempt && $attempts >= 3) {
            return $this->guard->escalateToManager(
                $lead,
                'Timeslot capture failed multiple times. ' . $reason
            );
        }

        $message = $customerMessage ?: 'Please share your preferred date/time for the booking. Example: Tuesday 10 AM.';

        return $this->sessionResponse(
            template: 'ask_preferred_time_retry_v1',
            action: 'retry_timeslot',
            body: $this->askPreferredTimeBody($lead, $message),
            placeholders: [
                $lead->name ?: 'there',
                $message,
            ],
            context: [
                'reason'                => $reason,
                'customer_message'      => $message,
                'attempts'              => $attempts,
                'selected_text'         => $selectedText,
                'selected_at'           => $selectedAt?->toIso8601String(),
                'working_hours_message' => $this->bookingService->workingHoursMessage($lead),
                'retry_signature'       => sha1(
                    implode('|', [
                        $lead->id,
                        $reason,
                        $message,
                        $selectedText ?? '',
                        $selectedAt?->toIso8601String() ?? '',
                        now()->format('Y-m-d H:i:s'),
                    ])
                ),
            ]
        );
    }

    /**
     * Retry confirmation
     */
    protected function retryConfirmation(Lead $lead, string $reason): array
    {
        $data = $this->conversationData($lead);

        $attempts = (int) ($data['confirm_attempts'] ?? 0);
        $attempts++;

        $data['confirm_attempts'] = $attempts;

        $lead->conversation_data = $data;
        $lead->conversation_updated_at = now();
        $lead->save();

        if ($attempts >= 2) {
            return $this->guard->escalateToManager($lead, $reason);
        }

        $pending = $data['pending_booking'] ?? null;

        $pendingDate = ! empty($pending['date'])
            ? Carbon::parse($pending['date'])
            : null;

        $vehicleLabel = $this->getVehicleLabel($lead);
        $dateLabel = $pendingDate
            ? $pendingDate->format('d M Y, h:i A')
            : 'the selected date/time';

        return $this->sessionResponse(
            template: 'confirm_booking_v1',
            action: 'confirm_booking',
            body: "Please confirm your booking request.\n\n"
                . "Vehicle: {$vehicleLabel}\n"
                . "Preferred date/time: {$dateLabel}\n\n"
                . "Reply Yes to confirm or No to change.",
            placeholders: [
                $vehicleLabel,
                $dateLabel,
            ],
            context: [
                'reason'   => $reason,
                'attempts' => $attempts,
            ]
        );
    }

    /**
     * SLOT CHECK
     */
    protected function isSlotAvailable(Lead $lead, Carbon $date, ?string $slot = null): bool
    {
        $slot = $slot ?: $this->bookingService->inferSlotFromTime($date, 'Morning');

        return ! Booking::where('company_id', $lead->company_id)
            ->whereDate('booking_date', $date->toDateString())
            ->where('slot', $slot)
            ->whereNotIn('status', [
                Booking::STATUS_CANCELED,
            ])
            ->exists();
    }

    /**
     * Confirmation helper
     */
    protected function looksLikeConfirmation(string $input): bool
    {
        $input = strtolower(trim($input));

        return in_array($input, [
            'yes',
            'y',
            'ok',
            'okay',
            'confirm',
            'confirmed',
            'yeah',
            'yep',
            'sure',
            'done',
            'go ahead',
            'proceed',
            'approved',
            'correct',
            'right',
        ], true);
    }

    /**
     * Rejection / change helper
     */
    protected function looksLikeRejection(string $input): bool
    {
        $input = strtolower(trim($input));

        return in_array($input, [
            'no',
            'n',
            'not now',
            'cancel',
            'change',
            'different time',
            'another time',
            'reschedule',
            'wrong',
            'incorrect',
        ], true);
    }

    /**
     * Normalize common WhatsApp date/time phrases
     */
    protected function normalizeTimeslotText(string $text, ?Lead $lead = null): string
    {
        $text = trim($text);
        $lower = strtolower($text);

        /*
        |--------------------------------------------------------------------------
        | Handle "same day" / "same date" / "that day"
        |--------------------------------------------------------------------------
        | Example:
        | Customer: Wednesday 10 PM
        | Bot: outside working hours
        | Customer: How about 4 PM same day?
        | System: uses the previous selected date and changes only the time.
        |--------------------------------------------------------------------------
        */

        if (
            $lead
            && (
                str_contains($lower, 'same day')
                || str_contains($lower, 'same date')
                || str_contains($lower, 'that day')
                || str_contains($lower, 'same')
            )
        ) {
            $sameDayText = $this->normalizeSameDayTimeslotText($lead, $text);

            if ($sameDayText) {
                return $sameDayText;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Handle "tomorrow at 10" / "today at 4"
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(today|tomorrow)\s+(?:at\s+)?(\d{1,2})\b/i', $lower, $m)) {
            $day = $m[1];
            $hour = (int) $m[2];

            if (! str_contains($lower, 'am') && ! str_contains($lower, 'pm')) {
                $suffix = ($hour >= 8 && $hour <= 11) ? 'am' : 'pm';

                return "{$day} {$hour}{$suffix}";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Handle weekday bare hour
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\s+(?:at\s+)?(\d{1,2})\b/i', $lower, $m)) {
            $day = $m[1];
            $hour = (int) $m[2];

            if (! str_contains($lower, 'am') && ! str_contains($lower, 'pm')) {
                $suffix = ($hour >= 8 && $hour <= 11) ? 'am' : 'pm';

                return "{$day} {$hour}{$suffix}";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Handle "10 tomorrow"
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(\d{1,2})\s+(today|tomorrow)\b/i', $lower, $m)) {
            $hour = (int) $m[1];
            $day = $m[2];

            if (! str_contains($lower, 'am') && ! str_contains($lower, 'pm')) {
                $suffix = ($hour >= 8 && $hour <= 11) ? 'am' : 'pm';

                return "{$day} {$hour}{$suffix}";
            }
        }

        return $text;
    }

    /**
     * Normalize "same day" time from the last rejected/selected date
     */
    protected function normalizeSameDayTimeslotText(Lead $lead, string $text): ?string
    {
        $data = $this->conversationData($lead);

        $lastSelectedAt = $data['last_timeslot_retry_selected_at'] ?? null;

        if (! $lastSelectedAt) {
            return null;
        }

        try {
            $baseDate = Carbon::parse($lastSelectedAt);
        } catch (\Throwable) {
            return null;
        }

        $time = $this->extractTimeFromText($text);

        if (! $time) {
            return null;
        }

        [$hour, $minute] = $time;

        return $baseDate
            ->copy()
            ->setTime($hour, $minute)
            ->format('Y-m-d H:i:s');
    }

    /**
     * Extract time from text
     */
    protected function extractTimeFromText(string $text): ?array
    {
        $lower = strtolower(trim($text));

        /*
        |--------------------------------------------------------------------------
        | Explicit AM/PM: 4 PM, 4:30 PM
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $lower, $m)) {
            $hour = (int) $m[1];
            $minute = isset($m[2]) ? (int) $m[2] : 0;
            $ampm = strtolower($m[3]);

            if ($hour < 1 || $hour > 12 || $minute > 59) {
                return null;
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
        | 24-hour time: 16:00, 16:30
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $lower, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        /*
        |--------------------------------------------------------------------------
        | Bare hour near same-day wording:
        | "same day 4", "how about 4 same day"
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(?:same day|same date|that day|same)\s+(?:at\s+)?(\d{1,2})\b/i', $lower, $m)) {
            return $this->normalizeBareHour((int) $m[1]);
        }

        if (preg_match('/\b(\d{1,2})\s+(?:same day|same date|that day|same)\b/i', $lower, $m)) {
            return $this->normalizeBareHour((int) $m[1]);
        }

        return null;
    }

    /**
     * Normalize bare hour into likely customer intent
     */
    protected function normalizeBareHour(int $hour): ?array
    {
        if ($hour >= 8 && $hour <= 11) {
            return [$hour, 0];
        }

        if ($hour >= 1 && $hour <= 7) {
            return [$hour + 12, 0];
        }

        if ($hour === 12) {
            return [12, 0];
        }

        if ($hour >= 13 && $hour <= 23) {
            return [$hour, 0];
        }

        return null;
    }

    /**
     * Build customer-facing outside-hours message
     */
    protected function outsideWorkingHoursMessage(
        Lead $lead,
        Carbon $selectedAt,
        string $fallbackReason
    ): string {
        $workingHoursMessage = $this->cleanWorkingHoursMessage(
            (string) $this->bookingService->workingHoursMessage($lead)
        );

        $selected = $selectedAt->format('d M Y, h:i A');

        if ($workingHoursMessage !== '') {
            return "The selected time {$selected} is outside our garage working hours.\n\n"
                . "{$workingHoursMessage}\n\n"
                . "Please choose another time within working hours.";
        }

        return "The selected time {$selected} is outside our garage working hours.\n\n"
            . "Please choose another time within working hours.";
    }

    /**
     * Clean working-hours text to avoid duplicate instructions
     */
    protected function cleanWorkingHoursMessage(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            return '';
        }

        $message = preg_replace('/\s+/', ' ', $message);

        $message = preg_replace(
            '/\s*Please choose (a|another)?\s*time within working hours\.?$/i',
            '',
            $message
        );

        $message = preg_replace(
            '/\s*Please choose (a|another)?\s*slot within working hours\.?$/i',
            '',
            $message
        );

        return trim((string) $message);
    }

    /**
     * Vehicle label for confirmation message
     */
    protected function getVehicleLabel(Lead $lead): string
    {
        $lead->refresh();

        if ($lead->opportunity?->vehicle_label) {
            return $lead->opportunity->vehicle_label;
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

        return $label !== '' ? $label : 'your vehicle';
    }

    /**
     * Session response helper
     */
    protected function sessionResponse(
        string $template,
        string $action,
        string $body,
        array $placeholders = [],
        array $context = []
    ): array {
        return [
            /*
            |--------------------------------------------------------------------------
            | Important
            |--------------------------------------------------------------------------
            |
            | BookingFlow is used by ProcessInboundWhatsApp.
            | This is an inbound/session flow, so body/text/message should be sent
            | from the app inside the 24-hour WhatsApp customer service window.
            |
            | template is kept only as a compatibility/logging hint.
            |
            */

            'body'    => $body,
            'text'    => $body,
            'message' => $body,

            'template'      => $template,
            'template_hint' => $template,

            'placeholders' => $placeholders,
            'action'       => $action,

            'context' => array_merge([
                'send_mode'     => 'session_message',
                'template_hint' => $template,
            ], $context),
        ];
    }

    protected function confirmBookingBody(string $vehicleLabel, string $dateLabel): string
    {
        return "Please confirm your booking request.\n\n"
            . "Vehicle: {$vehicleLabel}\n"
            . "Preferred date/time: {$dateLabel}\n\n"
            . "Reply Yes to confirm or No to change.";
    }

    protected function askPreferredTimeBody(Lead $lead, ?string $intro = null): string
    {
        $name = $lead->name ?: 'there';

        $intro = $intro ?: "Thanks {$name}. Please share your preferred booking date and time.";

        return "{$intro}\n\nExample: Tomorrow morning or Friday 4 PM";
    }

    protected function bookingHandoffBody(Lead $lead): string
    {
        $name = $lead->name ?: 'there';

        return "Thanks {$name}. Your booking request has been shared with our service manager.\n\n"
            . "They will contact you shortly to confirm the slot.";
    }

    /**
     * Safe conversation data
     */
    protected function conversationData(Lead $lead): array
    {
        $data = $lead->conversation_data ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * Append note safely
     */
    protected function appendNote(?string $existing, string $line): string
    {
        $existing = trim((string) $existing);
        $line = trim($line);

        if ($existing === '') {
            return $line;
        }

        if (str_contains($existing, $line)) {
            return $existing;
        }

        return $existing . "\n" . $line;
    }
}