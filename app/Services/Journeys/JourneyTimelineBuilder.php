<?php

namespace App\Services\Journeys;

use App\Enums\TimelineEventType;
use App\Models\AutomationLog;
use App\Models\JourneyAction;
use App\Models\JourneyEnrollment;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Support\Str;

class JourneyTimelineBuilder
{
    public function build(JourneyEnrollment $enrollment): array
    {
        $companyId = (int) $enrollment->company_id;
        $journeyId = (int) $enrollment->journey_id;

        $ctx   = is_array($enrollment->context) ? $enrollment->context : [];
        $phone = $ctx['phone'] ?? null;

        $items = [];

        /* ------------------------------------------------------------------
         | 1) Enrollment created
         ------------------------------------------------------------------ */
        $items[] = $this->event(
            TimelineEventType::ENROLLMENT,
            'Journey enrolled',
            'Enrollment created',
            $enrollment->created_at,
            ['journey_id' => $journeyId]
        );

        /* ------------------------------------------------------------------
         | 2) Steps snapshot
         ------------------------------------------------------------------ */
        $steps = $enrollment->journey?->steps ?? collect();

        foreach ($steps as $step) {
            $pos      = (int) ($step->position ?? 0);
            $executed = (int) $enrollment->current_step_position >= $pos;

            $body = $executed ? 'Executed / passed' : 'Pending';

            if (($step->type ?? null) === 'WAIT' && isset($ctx['_wake_at'])) {
                $body .= ' • Wake at: '.$ctx['_wake_at'];
            }

            $items[] = $this->event(
                $executed ? TimelineEventType::STEP_DONE : TimelineEventType::STEP_PENDING,
                "{$step->type} (Step {$pos})",
                $this->safeJson($step->config) ?: $body,
                $executed ? $enrollment->updated_at : null,
                [
                    'position' => $pos,
                    'type'     => $step->type,
                    'config'   => $step->config ?? [],
                ]
            );
        }

        /* ------------------------------------------------------------------
         | 3) Automation logs
         ------------------------------------------------------------------ */
        $automationLogs = AutomationLog::query()
            ->where('company_id', $companyId)
            ->where('automation_type', 'journey')
            ->where('action', 'STEP_EXECUTED')
            ->where(function ($q) use ($journeyId) {
                $q->whereRaw("JSON_EXTRACT(meta,'$.journey_id') = ?", [$journeyId])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta,'$.journey_id')) = ?", [(string)$journeyId]);
            })
            ->where('entity_type', $enrollment->enrollable_type)
            ->where('entity_id', $enrollment->enrollable_id)
            ->orderBy('created_at')
            ->get();

        foreach ($automationLogs as $log) {
            $items[] = $this->event(
                TimelineEventType::AUTOMATION,
                'Automation log',
                'STEP_EXECUTED'.(($s = data_get($log->meta,'step')) ? " • step={$s}" : ''),
                $log->created_at,
                ['id'=>$log->id,'meta'=>$log->meta]
            );
        }

        /* ------------------------------------------------------------------
         | 4) Manual Journey Actions (Phase 9D)
         ------------------------------------------------------------------ */
        $actions = JourneyAction::query()
            ->where('company_id', $companyId)
            ->where('enrollment_id', $enrollment->id)
            ->orderBy('created_at')
            ->get();

        $actorNames = User::whereIn('id', $actions->pluck('actor_user_id'))
            ->pluck('name','id')
            ->toArray();

        foreach ($actions as $a) {
            $items[] = $this->event(
                TimelineEventType::ACTION,
                'Manual action • '.strtoupper($a->action),
                "By: ".($actorNames[$a->actor_user_id] ?? 'User#'.$a->actor_user_id)
                    .(!empty($a->payload) ? "\n".json_encode($a->payload, JSON_PRETTY_PRINT) : ''),
                $a->created_at,
                ['payload'=>$a->payload]
            );
        }

        /* ------------------------------------------------------------------
         | 5) WhatsApp messages
         ------------------------------------------------------------------ */
        if ($phone) {
            $digits = $this->digits($phone);

            $wa = WhatsAppMessage::query()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($phone,$digits) {
                    $q->where('to',$phone)
                      ->orWhereRaw("REGEXP_REPLACE(`to`,'[^0-9]','') = ?",[$digits]);
                })
                ->orderBy('created_at')
                ->limit(200)
                ->get();

            foreach ($wa as $m) {
                $items[] = $this->event(
                    TimelineEventType::WHATSAPP,
                    'WhatsApp '.(($m->direction ?? 'out') === 'in' ? 'Inbound' : 'Outbound'),
                    $this->waBody($m),
                    $m->created_at,
                    ['status'=>$m->status,'to'=>$m->to]
                );
            }
        }

        /* ------------------------------------------------------------------
         | 6) Communications
         ------------------------------------------------------------------ */
        $commQuery = Communication::query()
            ->where('company_id', $companyId)
            ->orderBy('communication_date')
            ->limit(200);

        $etype = (string)$enrollment->enrollable_type;
        $eid   = (int)$enrollment->enrollable_id;

        if     (Str::contains($etype,'Lead'))        $commQuery->where('lead_id',$eid);
        elseif (Str::contains($etype,'Opportunity')) $commQuery->where('opportunity_id',$eid);
        elseif (Str::contains($etype,'Booking'))     $commQuery->where('booking_id',$eid);
        elseif (Str::contains($etype,'Client'))      $commQuery->where('client_id',$eid);
        else $commQuery = null;

        if ($commQuery) {
            foreach ($commQuery->get() as $c) {
                $items[] = $this->event(
                    TimelineEventType::COMMUNICATION,
                    'Communication • '.strtoupper($c->type),
                    (string)$c->content,
                    $c->communication_date,
                    ['id'=>$c->id]
                );
            }
        }

        usort($items, fn($a,$b)=>($a['at_ts']??PHP_INT_MAX)<=>($b['at_ts']??PHP_INT_MAX));

        return $items;
    }

    /* ---------------- Helpers ---------------- */

    private function event(string $type,string $title,string $body,$at=null,array $meta=[]): array
    {
        return [
            'type'=>$type,
            'title'=>$title,
            'body'=>$body,
            'at'=>$at,
            'at_ts'=>$at?->timestamp,
            'meta'=>$meta,
        ];
    }

    private function digits(?string $v): string
    {
        return preg_replace('/\D+/','',(string)$v) ?: '';
    }

    private function safeJson($arr): string
    {
        return is_array($arr) && $arr ? json_encode($arr,JSON_UNESCAPED_SLASHES) : '';
    }

    private function waBody(WhatsAppMessage $m): string
    {
        $p = is_array($m->payload)?$m->payload:[];
        return $p['body'] ?? $p['message'] ?? '(no body captured)';
    }
}
