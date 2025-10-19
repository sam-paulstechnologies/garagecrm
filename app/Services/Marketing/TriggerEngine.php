<?php

namespace App\Services\Marketing;

use App\Models\Marketing\{Trigger, Campaign};
use App\Models\Client\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TriggerEngine
{
    /** Return active triggers for an event within a company */
    public function for(string $event, int $companyId): Collection
    {
        return Trigger::where('company_id', $companyId)
            ->where('event', $event)
            ->where('status', 'active')
            ->get();
    }

    /** Evaluate conditions against a payload array (lead, booking, etc.) */
    public function passes(array $conditions = null, array $payload = []): bool
    {
        if (empty($conditions)) return true;
        foreach ($conditions as $c) {
            $field = data_get($payload, $c['field'] ?? null);
            $op    = $c['op'] ?? '=';
            $val   = $c['value'] ?? null;

            $ok = match ($op) {
                '=', '==' => $field == $val,
                '!='      => $field != $val,
                'in'      => in_array($field, (array) $val, true),
                'not_in'  => !in_array($field, (array) $val, true),
                'contains'=> is_string($field) && is_string($val) && str_contains($field, $val),
                '>'       => $field > $val,
                '<'       => $field < $val,
                default   => false,
            };
            if (!$ok) return false;
        }
        return true;
    }

    /**
     * NEW: Run all active triggers for a newly created lead.
     * Uses your existing `for()` + `passes()` APIs.
     */
    public function runForLead(Lead $lead): void
    {
        $companyId = (int) ($lead->company_id ?? 1);

        $triggers = $this->for('lead.created', $companyId);

        Log::info('[TriggerEngine] lead.created: checking triggers', [
            'lead_id'    => $lead->id,
            'company_id' => $companyId,
            'count'      => $triggers->count(),
        ]);

        if ($triggers->isEmpty()) return;

        // Build payload once for condition checks
        $payload = [
            'lead_id'     => $lead->id,
            'lead_status' => $lead->status,
            'source'      => $lead->source,
            'phone'       => $lead->phone,
            'email'       => $lead->email,
        ];

        /** @var CampaignDispatcher $dispatcher */
        $dispatcher = app(CampaignDispatcher::class);

        foreach ($triggers as $t) {
            $conditions = is_array($t->conditions) ? $t->conditions : (json_decode($t->conditions ?? '[]', true) ?: []);
            if (!$this->passes($conditions, $payload)) {
                Log::info('[TriggerEngine] condition mismatch', ['trigger_id' => $t->id, 'conditions' => $conditions]);
                continue;
            }

            $campaign = Campaign::find($t->campaign_id);
            if (!$campaign) {
                Log::warning('[TriggerEngine] campaign not found for trigger', ['trigger_id' => $t->id, 'campaign_id' => $t->campaign_id]);
                continue;
            }

            Log::info('[TriggerEngine] matched; enrolling lead into campaign', [
                'trigger_id'  => $t->id,
                'campaign_id' => $campaign->id,
                'lead_id'     => $lead->id,
            ]);

            $enrollment = $dispatcher->enroll($companyId, $campaign, 'lead', $lead->id);

            // kick off step 1 immediately
            $dispatcher->tick($enrollment);
        }
    }
}
