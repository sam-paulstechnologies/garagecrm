<?php

namespace App\Services\Ai;

use App\Models\MessageLog;
use App\Models\Client\Lead;
use Illuminate\Support\Str;

class ActionSuggestService
{
    /**
     * Build a short manager-facing summary and 3–5 quick actions
     * from the last few messages + AI fields.
     */
    public function forLead(Lead $lead): array
    {
        $msgs = MessageLog::query()
            ->where('company_id', $lead->company_id)
            ->where('lead_id', $lead->id)
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id','direction','body','ai_intent','ai_confidence','ai_propensity_score','created_at']);

        $lastIn  = $msgs->firstWhere('direction', 'in');
        $intent  = (string) ($lastIn?->ai_intent ?? 'general_question');
        $conf    = (float)  ($lastIn?->ai_confidence ?? 0);
        $prop    = (int)    ($lastIn?->ai_propensity_score ?? 0);

        // Minimal one-liner summary
        $summary = $lastIn
            ? sprintf(
                'Last msg %s — intent: %s (%.0f%%), propensity: %d.',
                $lastIn->created_at?->diffForHumans() ?? 'recently',
                Str::of($intent)->replace('_',' ')->lower(),
                $conf * 100,
                $prop
            )
            : 'No recent inbound messages.';

        // Quick actions (UI consumes `label`, `action`, `payload`)
        $actions = [];

        // Booking action first when intent or propensity call for it
        if (in_array($intent, ['booking','reschedule'], true) || $prop >= 60) {
            $actions[] = [
                'label'   => 'Quick Booking',
                'action'  => 'quick_booking',
                'payload' => [], // date/time chosen in next step UI
            ];
        }

        // Follow-up nudge
        $actions[] = [
            'label'   => 'Follow-up in 24h',
            'action'  => 'schedule_followup',
            'payload' => ['hours' => 24],
        ];

        // Suggest reply (short)
        $actions[] = [
            'label'   => 'Suggest Reply',
            'action'  => 'suggest_reply',
            'payload' => [], // will call NlpService->replyText()
        ];

        // Template fallback
        $actions[] = [
            'label'   => 'Send Acknowledgment',
            'action'  => 'send_template',
            'payload' => ['template' => 'lead_acknowledgment_v2'],
        ];

        return [
            'summary' => $summary,
            'intent'  => $intent,
            'confidence' => $conf,
            'propensity' => $prop,
            'actions' => $actions,
        ];
    }
}
