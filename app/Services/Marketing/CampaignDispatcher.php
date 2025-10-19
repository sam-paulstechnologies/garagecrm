<?php

namespace App\Services\Marketing;

use App\Models\Marketing\{Campaign, CampaignEnrollment, CampaignLog};
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Jobs\SendWhatsAppFromTemplate;
use Carbon\Carbon;

class CampaignDispatcher
{
    public function enroll(int $companyId, Campaign $campaign, $subjectType, $subjectId): CampaignEnrollment
    {
        return CampaignEnrollment::firstOrCreate(
            [
                'company_id'   => $companyId,
                'campaign_id'  => $campaign->id,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId
            ],
            [
                'status'       => 'in_progress',
                'current_step' => 1,
                'next_run_at'  => now()
            ]
        );
    }

    public function tick(CampaignEnrollment $enrollment): void
    {
        $campaign = $enrollment->campaign()->with('steps')->first();
        $step     = $campaign->steps->firstWhere('step_order', $enrollment->current_step);
        if (!$step) { $enrollment->update(['status' => 'completed']); return; }

        switch ($step->action) {
            case 'send_template':
                $tpl = WhatsAppTemplate::find($step->template_id);
                if ($tpl) {
                    SendWhatsAppFromTemplate::dispatch(
                        companyId:    $enrollment->company_id,
                        templateId:   $tpl->id,
                        toNumber:     $this->resolvePhone($enrollment),
                        placeholders: $this->resolveVars($enrollment)
                    );

                    CampaignLog::create([
                        'company_id'   => $enrollment->company_id,
                        'campaign_id'  => $campaign->id,
                        'enrollment_id'=> $enrollment->id,
                        'message'      => "Sent template {$tpl->name}",
                    ]);
                }
                $this->advance($enrollment, $campaign);
                break;

            case 'wait':
                $hours = (int) data_get($step->action_params, 'wait_hours', 24);
                $enrollment->update(['next_run_at' => Carbon::now()->addHours($hours)]);
                break;

            default:
                $this->advance($enrollment, $campaign);
        }
    }

    protected function advance(CampaignEnrollment $enrollment, Campaign $campaign): void
    {
        $next    = $enrollment->current_step + 1;
        $hasNext = $campaign->steps->contains('step_order', $next);

        $enrollment->update([
            'current_step' => $next,
            'status'       => $hasNext ? 'in_progress' : 'completed',
            'next_run_at'  => $hasNext ? now()->addMinutes(1) : null,
        ]);
    }

    /** Resolve destination phone based on subject (Lead, later Client, etc.) */
    protected function resolvePhone(CampaignEnrollment $e): string
    {
        if (strtolower((string) $e->subject_type) === 'lead') {
            $lead = \App\Models\Client\Lead::find($e->subject_id);
            // Try phone first, then a whatsapp_number column if you use one
            return $lead?->phone ?: ($lead?->whatsapp_number ?? '');
        }
        return '';
    }

    /** Resolve template variables */
    protected function resolveVars(CampaignEnrollment $e): array
    {
        if (strtolower((string) $e->subject_type) === 'lead') {
            $lead = \App\Models\Client\Lead::find($e->subject_id);
            return [
                'name'        => $lead?->name ?: 'there',
                'garage_name' => config('app.name', 'GarageCRM'),
            ];
        }
        return [
            'name'        => 'there',
            'garage_name' => config('app.name', 'GarageCRM'),
        ];
    }
}
