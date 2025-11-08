<?php

namespace App\Services\Ai;

use App\Models\Client\Lead;
use Carbon\Carbon;

class ReplyPlanner
{
    public function decide(Lead $lead, string $incomingText, array $nlp, float $fallbackThreshold = 0.60): array
    {
        $companyId = (int) ($lead->company_id ?? 1);

        $policy  = new AiPolicyService($companyId);
        $context = new AiContextService($companyId);

        $threshold = $policy->confidence() ?: $fallbackThreshold;

        $conf      = (float) ($nlp['confidence'] ?? 0.0);
        $intent    = strtolower((string) ($nlp['intent'] ?? 'fallback'));
        $sentiment = strtolower((string) ($nlp['sentiment'] ?? 'neutral'));

        // 0) Forbidden gate: forbidden intent or topic match → manager handoff with policy reply
        if ($this->isForbidden($intent, $incomingText, $policy)) {
            return $this->handoff($lead, 'policy_forbidden', $policy->policyReply());
        }

        // 1) Low confidence → optional escalation
        if ($conf < $threshold && $context->escalateOnLowConfidence()) {
            return $this->handoff($lead, 'low_confidence');
        }

        // 2) Negative sentiment → optional escalation
        if (in_array($sentiment, ['negative','very_negative'], true) && $context->escalateOnNegativeSentiment()) {
            return $this->handoff($lead, 'negative_sentiment');
        }

        // 3) Route by state + intent (existing logic)
        $state = $this->normalizeState($lead->conversation_state);

        if ($state === 'awaiting_vehicle') {
            if ($this->hasVehicleNow($incomingText)) {
                return [
                    'action'    => 'collect_timeslot',
                    'template'  => 'ask_preferred_time_v1',
                    'placeholders' => [$lead->name ?: 'there'],
                    'links'     => [],
                    'new_state' => 'awaiting_timeslot',
                ];
            }
            return [
                'action'    => 'collect_vehicle',
                'template'  => 'ask_make_model_v1',
                'placeholders' => [],
                'links'     => [],
                'new_state' => 'awaiting_vehicle',
            ];
        }

        if ($state === 'awaiting_timeslot') {
            [$dt, $slot] = $this->parseDateTime($incomingText);
            if ($dt) {
                return [
                    'action'    => 'confirmed',
                    'template'  => 'booking_confirmed_v1',
                    'placeholders' => [
                        'BK-REF',
                        $dt->format('D, d M Y'),
                        $dt->format('H:i'),
                    ],
                    'links'     => [],
                    'new_state' => 'idle',
                ];
            }
            return [
                'action'    => 'collect_timeslot',
                'template'  => 'ask_preferred_time_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'links'     => [],
                'new_state' => 'awaiting_timeslot',
            ];
        }

        // Idle routing by intent sets
        $handle  = $policy->intentsHandle();
        $handoff = $policy->intentsHandoff();

        if (in_array($intent, ['booking','reschedule'], true) || in_array($intent, $handle, true)) {
            $needsMake  = empty($lead->vehicle_make_id)  && empty($lead->other_make);
            $needsModel = empty($lead->vehicle_model_id) && empty($lead->other_model);

            if ($needsMake || $needsModel) {
                return [
                    'action'    => 'collect_vehicle',
                    'template'  => 'ask_make_model_v1',
                    'placeholders' => [],
                    'links'     => [],
                    'new_state' => 'awaiting_vehicle',
                ];
            }
            return [
                'action'    => 'collect_timeslot',
                'template'  => 'ask_preferred_time_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'links'     => [],
                'new_state' => 'awaiting_timeslot',
            ];
        }

        if (in_array($intent, $handoff, true)) {
            return $this->handoff($lead, 'intent_handoff');
        }

        // Generic ack
        return [
            'action'    => 'initial',
            'template'  => 'lead_acknowledgment_v2',
            'placeholders' => [],
            'links'     => [],
            'new_state' => $state,
        ];
    }

    /* ------------ gates/helpers ------------ */

    protected function isForbidden(string $intent, string $text, AiPolicyService $policy): bool
    {
        if (in_array($intent, $policy->intentsForbidden(), true)) return true;

        $topics = $policy->forbiddenTopics();
        if (!$topics) return false;

        $t = mb_strtolower($text);
        foreach ($topics as $topic) {
            $topic = trim(mb_strtolower($topic));
            if ($topic !== '' && str_contains($t, $topic)) return true;
        }
        return false;
    }

    protected function handoff(Lead $lead, string $reason, ?string $policyText = null): array
    {
        return [
            'action'       => 'manager_handoff',
            // keep template stable; the sender can replace body with $policyText if provided
            'template'     => 'visit_handoff_v1',
            'placeholders' => [],
            'links'        => [],
            'new_state'    => $this->normalizeState($lead->conversation_state),
            'policy_text'  => $policyText,  // optional for sender to use instead of template
            'handoff_reason' => $reason,
        ];
    }

    protected function normalizeState(?string $state): string
    {
        $s = strtolower((string)$state);
        return in_array($s, ['awaiting_vehicle','awaiting_timeslot','idle'], true) ? $s : 'idle';
    }

    protected function hasVehicleNow(string $text): bool
    {
        $t = mb_strtolower($text);
        return (bool) preg_match('/\b(audi|bmw|chevrolet|chevy|chrysler|dodge|ford|gmc|honda|hyundai|jeep|kia|lexus|mazda|mercedes|mg|mini|mitsubishi|nissan|opel|peugeot|renault|skoda|ssangyong|suzuki|tesla|toyota|volkswagen|vw|volvo)\b/i', $t);
    }

    protected function parseDateTime(string $text): array
    {
        $t = mb_strtolower($text);
        $slot = null;

        if (preg_match('/\b(morning|am|8am|9am|10am|11am)\b/i', $t)) $slot = 'Morning';
        if (preg_match('/\b(afternoon|pm|2pm|3pm|4pm|5pm|6pm)\b/i', $t)) $slot = 'Afternoon';

        if (preg_match('/\btomorrow\b/i', $t))       $base = now()->addDay()->startOfDay();
        elseif (preg_match('/\btoday\b/i', $t))      $base = now()->startOfDay();
        elseif (preg_match('/\b(mon|tue|wed|thu|fri|sat|sun)(day)?\b/i', $t, $m)) $base = Carbon::parse('next '.$m[0])->startOfDay();
        else {
            try { $parsed = Carbon::parse($text); $base = $parsed->copy()->startOfDay();
                  if (!$slot && (int)$parsed->format('H') >= 12) $slot = 'Afternoon';
                  if (!$slot && (int)$parsed->format('H') > 0 && (int)$parsed->format('H') < 12) $slot = 'Morning';
            } catch (\Throwable) { $base = null; }
        }

        if (!$base) return [null, ''];

        $time = $slot === 'Afternoon' ? '15:00' : '10:00';
        if (preg_match('/\b([01]?\d|2[0-3]):?([0-5]\d)?\s*(am|pm)?\b/i', $t, $tm)) {
            try {
                $candidate = Carbon::parse($tm[0]);
                $time = $candidate->format('H:i');
                if (!$slot) $slot = ((int)$candidate->format('H') >= 12) ? 'Afternoon' : 'Morning';
            } catch (\Throwable) {}
        }

        $dt = Carbon::parse($base->format('Y-m-d') . ' ' . $time);
        if (!$slot) $slot = (intval($dt->format('H')) >= 12) ? 'Afternoon' : 'Morning';

        return [$dt, $slot];
    }
}
