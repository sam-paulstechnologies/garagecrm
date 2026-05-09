<?php

namespace App\Services\Marketing;

use App\Models\Client\Lead;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignEnrollment;
use App\Models\Marketing\CampaignLog;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\WhatsApp\SendWhatsAppMessage;
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
                'subject_id'   => $subjectId,
            ],
            [
                'status'       => 'in_progress',
                'current_step' => 1,
                'next_run_at'  => now(),
            ]
        );
    }

    public function tick(CampaignEnrollment $enrollment): void
    {
        $campaign = $enrollment->campaign()->with('steps')->first();

        if (! $campaign) {
            return;
        }

        if ((int) $campaign->company_id !== (int) $enrollment->company_id) {
            Log::warning('[CampaignDispatcher] Campaign company mismatch', [
                'company_id'          => $enrollment->company_id,
                'campaign_id'         => $campaign->id,
                'campaign_company_id' => $campaign->company_id,
                'enrollment_id'       => $enrollment->id,
            ]);

            return;
        }

        $step = $campaign->steps->firstWhere('step_order', $enrollment->current_step);

        if (! $step) {
            $enrollment->update(['status' => 'completed']);

            return;
        }

        switch ($step->action) {
            case 'send_template':
                $this->sendTemplateStep($enrollment, $campaign, $step);
                $this->advance($enrollment, $campaign);
                break;

            case 'wait':
                $hours = (int) data_get($step->action_params, 'wait_hours', 24);

                $enrollment->update([
                    'next_run_at' => Carbon::now()->addHours($hours),
                ]);

                break;

            default:
                $this->advance($enrollment, $campaign);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WhatsApp campaign send
    |--------------------------------------------------------------------------
    |
    | Campaign messages are proactive / delayed automation.
    | Therefore they must go through SendWhatsAppMessage::fireEvent()
    | using DB mapping + approved Meta templates.
    |
    */

    protected function sendTemplateStep(CampaignEnrollment $enrollment, Campaign $campaign, $step): void
    {
        $template = WhatsAppTemplate::where('company_id', $enrollment->company_id)
            ->find($step->template_id);

        if (! $template) {
            Log::warning('[CampaignDispatcher] WhatsApp template missing', [
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'step_id'       => $step->id ?? null,
                'template_id'   => $step->template_id ?? null,
            ]);

            CampaignLog::create([
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'message'       => 'Skipped WhatsApp step: template missing',
            ]);

            return;
        }

        $lead = $this->resolveLead($enrollment);
        $phone = $this->resolvePhone($enrollment, $lead);

        if (! $phone || ! $lead) {
            Log::warning('[CampaignDispatcher] Missing phone or lead', [
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'subject_type'  => $enrollment->subject_type,
                'subject_id'    => $enrollment->subject_id,
            ]);

            CampaignLog::create([
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'message'       => 'Skipped WhatsApp step: missing phone or lead',
            ]);

            return;
        }

        $eventKey = $this->resolveEventKey($step, $template);

        if (! $eventKey) {
            Log::warning('[CampaignDispatcher] Missing WhatsApp event key', [
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'step_id'       => $step->id ?? null,
                'template_id'   => $template->id,
                'template_name' => $template->name,
            ]);

            CampaignLog::create([
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'message'       => "Skipped WhatsApp step: missing event key for template {$template->name}",
            ]);

            return;
        }

        $vars = $this->resolveVars($enrollment, $lead);

        try {
            app(SendWhatsAppMessage::class)->fireEvent(
                (int) $enrollment->company_id,
                (string) $eventKey,
                (string) $phone,
                array_merge($vars, [
                    'company_id'    => (int) $enrollment->company_id,
                    'campaign_id'   => (int) $campaign->id,
                    'enrollment_id' => (int) $enrollment->id,
                    'step_id'       => $step->id ?? null,
                    'lead_id'       => (int) $lead->id,
                    'event_key'     => (string) $eventKey,
                    'template_id'   => (int) $template->id,
                    'template_name' => $template->name,
                    'source'        => 'campaign_dispatcher',
                    'action'        => 'campaign_send_template',
                    'send_mode'     => 'meta_template',
                ])
            );

            Log::info('[CampaignDispatcher] WhatsApp campaign event fired', [
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'lead_id'       => $lead->id,
                'event_key'     => $eventKey,
                'template_id'   => $template->id,
                'template_name' => $template->name,
            ]);

            CampaignLog::create([
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'message'       => "Fired WhatsApp event {$eventKey} using template {$template->name}",
            ]);
        } catch (\Throwable $e) {
            Log::error('[CampaignDispatcher] WhatsApp campaign event failed', [
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'lead_id'       => $lead->id,
                'event_key'     => $eventKey,
                'template_id'   => $template->id,
                'template_name' => $template->name,
                'error'         => $e->getMessage(),
            ]);

            CampaignLog::create([
                'company_id'    => $enrollment->company_id,
                'campaign_id'   => $campaign->id,
                'enrollment_id' => $enrollment->id,
                'message'       => "Failed WhatsApp event {$eventKey}: " . $e->getMessage(),
            ]);
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

    protected function resolveLead(CampaignEnrollment $enrollment): ?Lead
    {
        $subjectType = $this->normalizeSubjectType($enrollment->subject_type);

        $allowedTypes = [
            'lead',
            'client.lead',
            'app.models.client.lead',
            $this->normalizeSubjectType(Lead::class),
        ];

        if (! in_array($subjectType, $allowedTypes, true)) {
            return null;
        }

        return Lead::where('company_id', $enrollment->company_id)
            ->find($enrollment->subject_id);
    }

    protected function resolvePhone(CampaignEnrollment $enrollment, ?Lead $lead = null): string
    {
        $lead = $lead ?: $this->resolveLead($enrollment);

        if (! $lead) {
            return '';
        }

        $phone = $lead->phone_norm ?: ($lead->phone ?? '');

        return trim((string) $phone);
    }

    protected function resolveEventKey($step, WhatsAppTemplate $template): ?string
    {
        $params = $step->action_params ?? [];

        /*
        |--------------------------------------------------------------------------
        | Preferred source: step action params
        |--------------------------------------------------------------------------
        */

        foreach ([
            'event_key',
            'whatsapp_event_key',
            'mapping_key',
            'trigger_key',
        ] as $key) {
            $value = data_get($params, $key);

            if (! empty($value)) {
                return trim((string) $value);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Optional source: template event_key column
        |--------------------------------------------------------------------------
        */

        if (isset($template->event_key) && trim((string) $template->event_key) !== '') {
            return trim((string) $template->event_key);
        }

        /*
        |--------------------------------------------------------------------------
        | Last fallback: template name
        |--------------------------------------------------------------------------
        */

        if (trim((string) $template->name) !== '') {
            return trim((string) $template->name);
        }

        return null;
    }

    protected function resolveVars(CampaignEnrollment $enrollment, ?Lead $lead = null): array
    {
        $lead = $lead ?: $this->resolveLead($enrollment);

        $name = $lead?->name ?: 'there';
        $phone = $lead?->phone_norm ?: ($lead?->phone ?? '');
        $appName = config('app.name', 'GarageCRM');

        return [
            /*
            |--------------------------------------------------------------------------
            | Numeric placeholders
            |--------------------------------------------------------------------------
            */

            0 => $name,
            1 => $appName,

            /*
            |--------------------------------------------------------------------------
            | Named placeholders
            |--------------------------------------------------------------------------
            */

            'name'          => $name,
            'customer_name' => $name,
            'lead_name'     => $name,
            'phone'         => $phone,
            'app_name'      => $appName,
        ];
    }

    protected function normalizeSubjectType(?string $subjectType): string
    {
        $subjectType = strtolower(trim((string) $subjectType));

        /*
        |--------------------------------------------------------------------------
        | Normalize namespace separators
        |--------------------------------------------------------------------------
        |
        | DB may store:
        |   lead
        |   App\Models\Client\Lead
        |   App\\Models\\Client\\Lead
        |
        */

        $subjectType = str_replace('\\\\', '\\', $subjectType);
        $subjectType = str_replace('\\', '.', $subjectType);

        return trim($subjectType, '.');
    }
}