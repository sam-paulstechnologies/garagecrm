<?php

namespace App\Services\Conversation;

use App\Models\Client\Lead;
use App\Services\Conversation\Flows\BookingFlow;
use App\Services\Conversation\Flows\VehicleFlow;

class ConversationEngine
{
    public function __construct(
        protected ConversationGuard $guard,
        protected IntentResolver $intentResolver,
        protected VehicleFlow $vehicleFlow,
        protected BookingFlow $bookingFlow
    ) {}

    public function handle(Lead $lead, string $text, ?array $nlp = null, array $context = []): array
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

        if ($this->guard->isDuplicateMessage($lead, $text, $context)) {
            return $this->skip();
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Profanity / urgent / manager handoff
        |--------------------------------------------------------------------------
        */

        if ($this->guard->containsProfanity($text)) {
            return $this->guard->escalateToManager($lead, 'Profanity: ' . $text);
        }

        $lower = strtolower($text);

        if ($this->looksLikeImmediateManagerEscalation($lower)) {
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
        $lowConfidence = ! $intent || $confidence < 0.6;

        if ($lowConfidence) {
            $intent = $this->intentResolver->resolve($text);
        }

        $menuIntent = $this->detectMenuSelection($text);

        if ($menuIntent) {
            $intent = $menuIntent;
            $lowConfidence = false;
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
        |
        | ProcessInboundWhatsApp sends the returned body as an inbound/session
        | message inside the 24-hour WhatsApp customer service window.
        |
        | The template/event key is still returned as a hint so the sender can try
        | mapped rendering first and keep the hardcoded body as fallback.
        |
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

        if ($state === 'awaiting_reschedule_confirmation') {
            return $this->bookingFlow->handleRescheduleConfirmation($lead, $text);
        }

        if ($state === 'awaiting_reschedule_datetime') {
            return $this->bookingFlow->handleRescheduleTimeslot($lead, $text);
        }

        if ($state === 'confirm_reschedule') {
            return $this->bookingFlow->confirmReschedule($lead, $text);
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

        if (in_array($intent, ['manager', 'complaint', 'emergency'], true)) {
            return $this->guard->escalateToManager($lead, $text);
        }

        if ($intent === 'gratitude') {
            $this->resetFallbackCount($lead);

            return $this->sessionResponse(
                template: 'gratitude_v1',
                action: 'gratitude',
                body: "You're welcome " . ($lead->name ?: 'there') . ". Happy to help.",
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'event_key' => 'lead.gratitude',
                ]
            );
        }

        if ($intent === 'greeting') {
            $this->resetFallbackCount($lead);

            return $this->sessionResponse(
                template: 'lead.intent_menu',
                action: 'start',
                body: $this->intentQuestionBody($lead),
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'event_key' => 'lead.intent_menu',
                ]
            );
        }

        if ($intent === 'booking') {
            $this->resetFallbackCount($lead);

            return $this->vehicleFlow->start($lead, $text);
        }

        if (in_array($intent, ['general', 'price', 'general_enquiry'], true)) {
            $this->resetFallbackCount($lead);

            $data = $this->conversationData($lead);

            $data['general_enquiry_attempts'] =
                (int) ($data['general_enquiry_attempts'] ?? 0) + 1;

            $lead->conversation_state = 'awaiting_general_enquiry';
            $lead->conversation_data = $data;
            $lead->conversation_updated_at = now();
            $lead->save();

            return $this->sessionResponse(
                template: 'lead.general_enquiry.ask',
                action: 'collect_general_enquiry',
                body: 'Sure ' . ($lead->name ?: 'there') . '. How can I help?',
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'event_key' => 'lead.general_enquiry.ask',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Fallback
        |--------------------------------------------------------------------------
        */

        $data = $this->conversationData($lead);

        $data['fallback_count'] = (int) ($data['fallback_count'] ?? 0) + 1;
        $data['last_fallback_text'] = $text;
        $data['last_fallback_at'] = now()->toIso8601String();

        if ($lowConfidence) {
            $data['last_low_confidence_at'] = now()->toIso8601String();
            $data['last_low_confidence_score'] = $confidence;
            $data['last_low_confidence_intent'] = $nlp['intent'] ?? null;
        }

        $lead->conversation_data = $data;
        $lead->conversation_updated_at = now();
        $lead->save();

        if ($data['fallback_count'] >= 2) {
            return $this->guard->escalateToManager(
                $lead,
                'Fallback triggered multiple times. Last message: ' . $text
            );
        }

        return $this->sessionResponse(
            template: 'system.fallback_first',
            action: 'retry',
            body: $this->intentQuestionBody($lead),
            placeholders: [$lead->name ?: 'there'],
            context: [
                'event_key' => 'system.fallback_first',
                'fallback_count' => $data['fallback_count'],
                'low_confidence' => $lowConfidence,
                'confidence' => $confidence,
                'resolved_intent' => $intent,
            ]
        );
    }

    protected function looksLikeImmediateManagerEscalation(string $lower): bool
    {
        $needles = [
            'manager',
            'speak to manager',
            'talk to manager',
            'complaint',
            'complain',
            'emergency',
            'urgent',
            'asap',
            'immediately',
            'right now',
            'breakdown',
            'stuck',
            'stranded',
            'accident',
        ];

        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function detectMenuSelection(string $text): ?string
    {
        $input = strtolower(trim($text));
        $input = preg_replace('/\s+/', ' ', $input) ?: $input;

        return match (true) {
            in_array($input, ['1', 'one', 'service', 'book service', 'book a service', 'booking'], true) => 'booking',
            in_array($input, ['2', 'two', 'general', 'general enquiry', 'general inquiry', 'enquiry', 'inquiry', 'ask question', 'ask a question'], true) => 'general_enquiry',
            in_array($input, ['3', 'three', 'manager', 'speak to manager', 'speak to the manager', 'talk to manager', 'talk to the manager'], true) => 'manager',
            default => null,
        };
    }

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
            | This response is consumed by ProcessInboundWhatsApp.
            | Because this is an inbound/session flow, body/text is sent from the app
            | within the 24-hour WhatsApp customer service window.
            |
            | template is kept only as a compatibility/logging hint.
            |
            */

            'body' => $body,
            'text' => $body,
            'message' => $body,

            'template' => $template,
            'template_hint' => $template,

            'placeholders' => $placeholders,
            'action' => $action,

            'context' => array_merge([
                'send_mode' => 'session_message',
                'template_hint' => $template,
                'event_key' => $template,
            ], $context),
        ];
    }

    protected function intentQuestionBody(Lead $lead): string
    {
        $name = $lead->name ?: 'there';

        return "Hi {$name}, how can we help you today?\n\n"
            . "1. Service\n"
            . "2. General Enquiry\n"
            . "3. Speak to the manager";
    }

    protected function resetFallbackCount(Lead $lead): void
    {
        $data = $this->conversationData($lead);

        if (! array_key_exists('fallback_count', $data)) {
            return;
        }

        $data['fallback_count'] = 0;

        $lead->conversation_data = $data;
        $lead->conversation_updated_at = now();
        $lead->save();
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
            'template_hint' => null,
            'placeholders' => [],
            'context' => [],
        ];
    }
}
