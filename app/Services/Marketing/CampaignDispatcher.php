<?php

namespace App\Services\Marketing;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignEnrollment;
use App\Models\Marketing\CampaignLog;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\Client\Lead;
use App\Jobs\SendWhatsAppFromTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

        if (!$campaign) {
            return;
        }

        if ((int) $campaign->company_id !== (int) $enrollment->company_id) {
            Log::warning('[CampaignDispatcher] Campaign company mismatch', [
                'company_id' => $enrollment->company_id,
                'campaign_id' => $campaign->id,
                'campaign_company_id' => $campaign->company_id,
                'enrollment_id' => $enrollment->id,
            ]);
            return;
        }

        $step = $campaign->steps->firstWhere('step_order', $enrollment->current_step);

        if (!$step) {
            $enrollment->update(['status' => 'completed']);
            return;
        }

        switch ($step->action) {

            case 'send_template':

                $tpl = WhatsAppTemplate::where('company_id', $enrollment->company_id)
                    ->find($step->template_id);

                if ($tpl) {

                    $phone = $this->resolvePhone($enrollment);
                    $leadId = $this->resolveLeadId($enrollment);

                    if (!$phone || !$leadId) {
                        Log::warning('[CampaignDispatcher] Missing phone or lead', [
                            'company_id' => $enrollment->company_id,
                            'enrollment_id' => $enrollment->id
                        ]);
                        return;
                    }

                    SendWhatsAppFromTemplate::dispatch(
                        companyId:    $enrollment->company_id,
                        leadId:       $leadId,
                        toNumberE164: $phone,
                        templateName: $tpl->name,
                        placeholders: $this->resolveVars($enrollment),
                        links:        [],
                        context: [
                            'campaign_id'   => $campaign->id,
                            'enrollment_id' => $enrollment->id,
                        ],
                        action: 'campaign'
                    );

                    CampaignLog::create([
                        'company_id'    => $enrollment->company_id,
                        'campaign_id'   => $campaign->id,
                        'enrollment_id' => $enrollment->id,
                        'message'       => "Sent template {$tpl->name}",
                    ]);
                }

                $this->advance($enrollment, $campaign);

                break;


            case 'wait':

                $hours = (int) data_get($step->action_params, 'wait_hours', 24);

                $enrollment->update([
                    'next_run_at' => Carbon::now()->addHours($hours)
                ]);

                break;


            default:

                $this->advance($enrollment, $campaign);
        }
    }

    protected function advance(CampaignEnrollment $enrollment, Campaign $campaign): void
    {
        $next = $enrollment->current_step + 1;

        $hasNext = $campaign->steps->contains('step_order', $next);

        $enrollment->update([
            'current_step' => $next,
            'status'       => $hasNext ? 'in_progress' : 'completed',
            'next_run_at'  => $hasNext ? now()->addMinutes(1) : null,
        ]);
    }

    protected function resolvePhone(CampaignEnrollment $e): string
    {
        if (strtolower((string) $e->subject_type) === 'lead') {

            $lead = Lead::where('company_id', $e->company_id)
                ->find($e->subject_id);

            return $lead?->phone_norm ?: ($lead?->phone ?? '');
        }

        return '';
    }

    protected function resolveLeadId(CampaignEnrollment $e): ?int
    {
        if (strtolower((string) $e->subject_type) === 'lead') {
            $lead = Lead::where('company_id', $e->company_id)
                ->find($e->subject_id);

            return $lead?->id;
        }

        return null;
    }

    protected function resolveVars(CampaignEnrollment $e): array
    {
        if (strtolower((string) $e->subject_type) === 'lead') {

            $lead = Lead::where('company_id', $e->company_id)
                ->find($e->subject_id);

            return [
                $lead?->name ?: 'there',
                config('app.name', 'GarageCRM'),
            ];
        }

        return [
            'there',
            config('app.name', 'GarageCRM'),
        ];
    }
}