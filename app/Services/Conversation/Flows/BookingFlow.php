<?php

namespace App\Services\Conversation\Flows;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Services\Booking\BookingService;
use App\Services\Leads\LeadConversionService;
use App\Services\Conversation\ConversationGuard;
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
        $normalizedText = $this->normalizeTimeslotText($text);

        $date = $this->bookingService->parsePreferredDateTime($normalizedText);

        /*
        |--------------------------------------------------------------------------
        | Invalid date/time
        |--------------------------------------------------------------------------
        */
        if (!$date instanceof Carbon) {
            return $this->retryTimeslot(
                lead: $lead,
                reason: 'Invalid date/time received: ' . $text
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
                reason: 'Past date/time received: ' . $text
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Company Working Hours Protection
        |--------------------------------------------------------------------------
        | If customer selects a time outside garage working hours, do not proceed
        | to confirmation. Ask them to choose a valid time.
        |--------------------------------------------------------------------------
        */
        $workingHoursViolation = $this->bookingService->workingHoursViolation($lead, $date);

        if ($workingHoursViolation) {
            return $this->retryTimeslot(
                lead: $lead,
                reason: $workingHoursViolation
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
            'date' => $date->toIso8601String(),
            'slot' => $slot,
            'raw_text' => $text,
            'captured_at' => now()->toIso8601String(),
        ];

        $lead->conversation_data = $data;
        $lead->conversation_state = 'confirm_booking';
        $lead->save();

        return [
            'template' => 'confirm_booking_v1',
            'placeholders' => [
                $this->getVehicleLabel($lead),
                $date->format('d M Y, h:i A'),
            ],
            'action' => 'confirm_booking',
            'context' => [
                'pending_date' => $date->toIso8601String(),
                'slot' => $slot,
            ],
        ];
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
            $lead->save();

            return [
                'template' => 'ask_preferred_time_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'change_timeslot',
                'context' => [
                    'reason' => 'User rejected pending booking time',
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | If user sends another date/time while in confirm state
        |--------------------------------------------------------------------------
        */
        $maybeDate = $this->bookingService->parsePreferredDateTime(
            $this->normalizeTimeslotText($text)
        );

        if ($maybeDate instanceof Carbon && !$this->looksLikeConfirmation($input)) {
            return $this->handleTimeslot($lead, $text);
        }

        /*
        |--------------------------------------------------------------------------
        | Confirmation unclear
        |--------------------------------------------------------------------------
        */
        if (!$this->looksLikeConfirmation($input)) {
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

        if (!$pending || empty($pending['date'])) {
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
            $lead->save();

            return [
                'template' => 'ask_preferred_time_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'retry_timeslot',
                'context' => [
                    'reason' => 'Pending booking time is already in the past',
                ],
            ];
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
            $lead->save();

            return [
                'template' => 'ask_preferred_time_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'retry_timeslot',
                'context' => [
                    'reason' => $workingHoursViolation,
                    'working_hours_message' => $this->bookingService->workingHoursMessage($lead),
                ],
            ];
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
        if (!$this->isSlotAvailable($lead, $date, $slot)) {
            unset($data['pending_booking']);

            $lead->conversation_state = 'awaiting_timeslot';
            $lead->conversation_data = $data;
            $lead->save();

            return [
                'template' => 'ask_preferred_time_retry_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'retry_timeslot',
                'context' => [
                    'reason' => 'Slot unavailable',
                    'date' => $date->toDateString(),
                    'slot' => $slot,
                ],
            ];
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
                $lead->save();

                return [
                    'template' => 'ask_preferred_time_v1',
                    'placeholders' => [$lead->name ?: 'there'],
                    'action' => 'retry_timeslot',
                    'context' => [
                        'reason' => $e->getMessage(),
                        'working_hours_message' => $this->bookingService->workingHoursMessage($lead),
                    ],
                ];
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
                'stage' => Opportunity::STAGE_MANAGER_CONFIRMATION_PENDING,
                'next_follow_up' => $date->toDateString(),
                'expected_close_date' => $date->toDateString(),
                'notes' => $this->appendNote(
                    $lead->opportunity->notes,
                    'Booking requested via WhatsApp for ' . $date->format('Y-m-d H:i')
                ),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Notify manager via WhatsApp template
        |--------------------------------------------------------------------------
        | This uses manager_attention_required_v1 and force_template=true inside
        | ManagerNotificationService, so it works even if the manager has no
        | active 24-hour session.
        |--------------------------------------------------------------------------
        */
        try {
            $this->managerNotificationService->notifyForLead(
                lead: $lead,
                reason: 'Booking confirmed by customer and awaiting manager approval',
                preferredAt: $date,
                bookingId: (int) $booking->id,
                extra: [
                    'slot' => $slot,
                    'source' => 'booking_flow',
                    'customer_action' => 'confirmed_booking_request',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[BookingFlow] Manager notification failed', [
                'lead_id' => $lead->id,
                'booking_id' => $booking->id ?? null,
                'error' => $e->getMessage(),
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
        $lead->save();

        return [
            'template' => 'manager_handoff_v1',
            'placeholders' => [],
            'action' => 'booking_handoff',
            'context' => [
                'booking_id' => $booking->id,
                'date' => $date->toDateString(),
                'time' => $date->format('H:i'),
                'slot' => $slot,
                'reason' => 'Booking confirmed by user and sent to manager',
            ],
        ];
    }

    /**
     * Retry timeslot capture
     */
    protected function retryTimeslot(Lead $lead, string $reason): array
    {
        $data = $this->conversationData($lead);

        $attempts = (int) ($data['timeslot_attempts'] ?? 0);
        $attempts++;

        $data['timeslot_attempts'] = $attempts;

        $lead->conversation_state = 'awaiting_timeslot';
        $lead->conversation_data = $data;
        $lead->save();

        if ($attempts >= 3) {
            return $this->guard->escalateToManager(
                $lead,
                'Timeslot capture failed multiple times. ' . $reason
            );
        }

        return [
            'template' => 'ask_preferred_time_v1',
            'placeholders' => [$lead->name ?: 'there'],
            'action' => 'retry_timeslot',
            'context' => [
                'reason' => $reason,
                'attempts' => $attempts,
            ],
        ];
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
        $lead->save();

        if ($attempts >= 2) {
            return $this->guard->escalateToManager($lead, $reason);
        }

        $pending = $data['pending_booking'] ?? null;

        $pendingDate = !empty($pending['date'])
            ? Carbon::parse($pending['date'])
            : null;

        return [
            'template' => 'confirm_booking_v1',
            'placeholders' => [
                $this->getVehicleLabel($lead),
                $pendingDate
                    ? $pendingDate->format('d M Y, h:i A')
                    : 'the selected date/time',
            ],
            'action' => 'confirm_booking',
            'context' => [
                'reason' => $reason,
                'attempts' => $attempts,
            ],
        ];
    }

    /**
     * SLOT CHECK
     */
    protected function isSlotAvailable(Lead $lead, Carbon $date, ?string $slot = null): bool
    {
        $slot = $slot ?: $this->bookingService->inferSlotFromTime($date, 'Morning');

        return !Booking::where('company_id', $lead->company_id)
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
    protected function normalizeTimeslotText(string $text): string
    {
        $text = trim($text);
        $lower = strtolower($text);

        /*
        |--------------------------------------------------------------------------
        | Handle "tomorrow at 10" / "today at 4"
        |--------------------------------------------------------------------------
        */
        if (preg_match('/\b(today|tomorrow)\s+(?:at\s+)?(\d{1,2})\b/i', $lower, $m)) {
            $day = $m[1];
            $hour = (int) $m[2];

            if (!str_contains($lower, 'am') && !str_contains($lower, 'pm')) {
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

            if (!str_contains($lower, 'am') && !str_contains($lower, 'pm')) {
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

            if (!str_contains($lower, 'am') && !str_contains($lower, 'pm')) {
                $suffix = ($hour >= 8 && $hour <= 11) ? 'am' : 'pm';

                return "{$day} {$hour}{$suffix}";
            }
        }

        return $text;
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