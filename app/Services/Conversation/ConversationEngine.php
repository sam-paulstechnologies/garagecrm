<?php

namespace App\Services\Conversation;

use App\Models\Client\Lead;
use App\Services\Conversation\Flows\VehicleFlow;
use App\Services\Conversation\Flows\BookingFlow;

class ConversationEngine
{
    public function __construct(
        protected ConversationGuard $guard,
        protected IntentResolver $intentResolver,
        protected VehicleFlow $vehicleFlow,
        protected BookingFlow $bookingFlow
    ) {}

    public function handle(Lead $lead, string $text, ?array $nlp = null): array
    {
        $text = trim($text);

        /*
        |--------------------------------------------------------------------------
        | 0. Hard stop if human takeover is active
        |--------------------------------------------------------------------------
        */
        $lead->refresh();

        if ($lead->conversation_state === 'human') {
            return $this->skip();
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Timeout reset
        |--------------------------------------------------------------------------
        */
        if (
            $lead->conversation_updated_at &&
            now()->diffInMinutes($lead->conversation_updated_at) > 30
        ) {
            $lead->clearConversation();
            $lead->refresh();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Duplicate message protection
        |--------------------------------------------------------------------------
        */
        if ($this->guard->isDuplicateMessage($lead, $text)) {
            return $this->skip();
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Profanity / urgent handoff
        |--------------------------------------------------------------------------
        */
        if ($this->guard->containsProfanity($text)) {
            return $this->guard->escalateToManager($lead, 'Profanity: ' . $text);
        }

        $lower = strtolower($text);

        if (
            str_contains($lower, 'manager') ||
            str_contains($lower, 'complaint') ||
            str_contains($lower, 'emergency')
        ) {
            return $this->guard->escalateToManager($lead, $text);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Resolve current state + intent
        |--------------------------------------------------------------------------
        */
        $lead->refresh();

        $state = $lead->conversation_state ?: 'idle';

        $intent = $nlp['intent'] ?? null;
        $confidence = (float) ($nlp['confidence'] ?? 0);

        if (!$intent || $confidence < 0.6) {
            $intent = $this->intentResolver->resolve($text);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Capture service type wherever possible
        |--------------------------------------------------------------------------
        */
        $this->intentResolver->captureServiceType($lead, $text);
        $lead->refresh();

        /*
        |--------------------------------------------------------------------------
        | 6. State-based flows always win
        |--------------------------------------------------------------------------
        */
        if ($state === 'awaiting_vehicle') {
            return $this->vehicleFlow->handle($lead, $text);
        }

        if ($state === 'awaiting_timeslot') {
            return $this->bookingFlow->handleTimeslot($lead, $text);
        }

        if ($state === 'confirm_booking') {
            return $this->bookingFlow->confirmBooking($lead, $text);
        }

        if ($state === 'awaiting_general_enquiry') {
            return $this->guard->escalateToManager(
                $lead,
                'General enquiry: ' . $text
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Intent entry points
        |--------------------------------------------------------------------------
        */

        if ($intent === 'manager' || $intent === 'complaint' || $intent === 'emergency') {
            return $this->guard->escalateToManager($lead, $text);
        }

        if ($intent === 'gratitude') {
            return [
                'template' => 'gratitude_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'gratitude',
                'context' => [],
            ];
        }

        if ($intent === 'greeting') {
            return [
                'template' => 'ask_intent_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'start',
                'context' => [],
            ];
        }

        if ($intent === 'booking') {
            return $this->vehicleFlow->start($lead, $text);
        }

        if (in_array($intent, ['general', 'price', 'general_enquiry'], true)) {
            $data = $this->conversationData($lead);

            $data['general_enquiry_attempts'] =
                (int) ($data['general_enquiry_attempts'] ?? 0) + 1;

            $lead->conversation_state = 'awaiting_general_enquiry';
            $lead->conversation_data = $data;
            $lead->save();

            return [
                'template' => 'ask_general_enquiry_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'collect_general_enquiry',
                'context' => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Fallback
        |--------------------------------------------------------------------------
        */
        $data = $this->conversationData($lead);

        $data['fallback_count'] = (int) ($data['fallback_count'] ?? 0) + 1;

        $lead->conversation_data = $data;
        $lead->save();

        if ($data['fallback_count'] >= 2) {
            return $this->guard->escalateToManager(
                $lead,
                'Fallback triggered multiple times'
            );
        }

        return [
            'template' => 'ask_intent_v1',
            'placeholders' => [$lead->name ?: 'there'],
            'action' => 'retry',
            'context' => [],
        ];
    }

    protected function conversationData(Lead $lead): array
    {
        $data = $lead->conversation_data ?? [];

        return is_array($data) ? $data : [];
    }

    private function skip(): array
    {
        return [
            'action' => 'skip',
            'template' => null,
            'placeholders' => [],
            'context' => [],
        ];
    }
}