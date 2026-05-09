<?php

namespace App\Services\Journeys;

use App\Models\AutomationLog;
use App\Models\Journey;
use App\Models\JourneyEnrollment;
use App\Models\JourneyStep;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Support\Facades\Log;

class JourneyEngine
{
    /**
     * Enroll entity into all active journeys matching trigger_key.
     */
    public function enrollForTrigger(
        int $companyId,
        string $triggerKey,
        object $enrollable,
        array $context = []
    ): void {
        $journeys = Journey::where('company_id', $companyId)
            ->where('trigger_key', $triggerKey)
            ->where('is_active', true)
            ->get();

        foreach ($journeys as $journey) {
            $enrollment = JourneyEnrollment::create([
                'company_id'            => $companyId,
                'journey_id'            => $journey->id,
                'enrollable_type'       => get_class($enrollable),
                'enrollable_id'         => $enrollable->id,
                'current_step_position' => 0,
                'status'                => 'active',
                'context'               => $context,
            ]);

            AutomationLog::create([
                'company_id'      => $companyId,
                'entity_type'     => get_class($enrollable),
                'entity_id'       => $enrollable->id,
                'automation_type' => 'journey',
                'action'          => 'ENROLLED',
                'meta'            => [
                    'journey_id' => $journey->id,
                    'trigger'    => $triggerKey,
                    'context'    => $context,
                ],
            ]);

            $this->advance($enrollment);
        }
    }

    /**
     * Execute next step.
     */
    public function advance(JourneyEnrollment $enr): void
    {
        if ($enr->status !== 'active') {
            return;
        }

        $enr->loadMissing('journey.steps');

        if (! $enr->journey || (int) $enr->journey->company_id !== (int) $enr->company_id) {
            $enr->update(['status' => 'stopped']);

            return;
        }

        $step = $enr->journey->steps
            ->firstWhere('position', $enr->current_step_position + 1);

        if (! $step) {
            $enr->update(['status' => 'completed']);

            AutomationLog::create([
                'company_id'      => $enr->company_id,
                'entity_type'     => $enr->enrollable_type,
                'entity_id'       => $enr->enrollable_id,
                'automation_type' => 'journey',
                'action'          => 'COMPLETED',
                'meta'            => [
                    'journey_id' => $enr->journey_id,
                ],
            ]);

            return;
        }

        match ($step->type) {
            'SEND_WHATSAPP' => $this->sendWhatsApp($enr, $step),
            'WAIT'          => $this->wait($enr, $step),
            'IF'            => $this->branch($enr, $step),
            'TAG'           => $this->tag($enr, $step),
            'STOP'          => $this->stop($enr),
            default         => $this->skip($enr),
        };
    }

    private function skip(JourneyEnrollment $enr): void
    {
        $enr->update([
            'current_step_position' => $enr->current_step_position + 1,
        ]);

        $this->advance($enr);
    }

    private function stop(JourneyEnrollment $enr): void
    {
        $enr->update(['status' => 'completed']);

        AutomationLog::create([
            'company_id'      => $enr->company_id,
            'entity_type'     => $enr->enrollable_type,
            'entity_id'       => $enr->enrollable_id,
            'automation_type' => 'journey',
            'action'          => 'STOPPED',
            'meta'            => [
                'journey_id' => $enr->journey_id,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | WHATSAPP
    |--------------------------------------------------------------------------
    |
    | Journey WhatsApp messages are usually delayed/proactive.
    |
    | Therefore they must NOT render template body locally and must NOT use
    | ProviderFactory directly.
    |
    | They should go through:
    |
    |   SendWhatsAppMessage::fireEvent()
    |
    | so provider, active status, approved Meta template, DB mapping, logs,
    | and template variables are handled in one place.
    |
    */

    private function sendWhatsApp(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $cfg = $step->config ?? [];
        $ctx = $enr->context ?? [];

        $to = $this->resolvePhone($ctx);

        if (! $to) {
            $this->logStep(
                enr: $enr,
                step: $step,
                action: 'WHATSAPP_SKIPPED',
                meta: [
                    'reason' => 'Missing phone in journey context',
                    'context_keys' => array_keys($ctx),
                ]
            );

            $this->skip($enr);

            return;
        }

        $template = $this->resolveTemplate($enr, $cfg);

        $eventKey = $this->resolveEventKey($cfg, $template);

        if (! $eventKey) {
            $this->logStep(
                enr: $enr,
                step: $step,
                action: 'WHATSAPP_SKIPPED',
                meta: [
                    'reason'      => 'Missing event_key for WhatsApp journey step',
                    'template_id' => $cfg['template_id'] ?? null,
                    'template'    => $template?->name,
                ]
            );

            $this->skip($enr);

            return;
        }

        try {
            app(SendWhatsAppMessage::class)->fireEvent(
                (int) $enr->company_id,
                (string) $eventKey,
                (string) $to,
                array_merge($ctx, [
                    /*
                    |--------------------------------------------------------------------------
                    | Journey context
                    |--------------------------------------------------------------------------
                    */

                    'company_id'      => (int) $enr->company_id,
                    'journey_id'      => (int) $enr->journey_id,
                    'enrollment_id'   => (int) $enr->id,
                    'step_id'         => (int) $step->id,
                    'step_position'   => (int) $step->position,
                    'event_key'       => (string) $eventKey,
                    'template_id'     => $template?->id,
                    'template_name'   => $template?->name,
                    'source'          => 'journey_engine',
                    'action'          => 'journey_send_whatsapp',
                    'send_mode'       => 'meta_template',
                ])
            );

            $this->logStep(
                enr: $enr,
                step: $step,
                action: 'WHATSAPP_EVENT_FIRED',
                meta: [
                    'event_key'     => $eventKey,
                    'to'            => $to,
                    'template_id'   => $template?->id,
                    'template_name' => $template?->name,
                ]
            );
        } catch (\Throwable $e) {
            $this->logStep(
                enr: $enr,
                step: $step,
                action: 'WHATSAPP_FAILED',
                meta: [
                    'event_key' => $eventKey,
                    'to'        => $to,
                    'error'     => $e->getMessage(),
                ]
            );

            Log::error('[JourneyEngine] WhatsApp journey step failed', [
                'company_id'    => $enr->company_id,
                'journey_id'    => $enr->journey_id,
                'enrollment_id' => $enr->id,
                'step_id'       => $step->id,
                'event_key'     => $eventKey,
                'to'            => $to,
                'error'         => $e->getMessage(),
            ]);
        }

        $this->skip($enr);
    }

    private function resolveTemplate(JourneyEnrollment $enr, array $cfg): ?WhatsAppTemplate
    {
        $templateId = $cfg['template_id'] ?? null;

        if (! $templateId) {
            return null;
        }

        return WhatsAppTemplate::where('company_id', $enr->company_id)
            ->find($templateId);
    }

    private function resolveEventKey(array $cfg, ?WhatsAppTemplate $template): ?string
    {
        /*
        |--------------------------------------------------------------------------
        | Preferred config keys
        |--------------------------------------------------------------------------
        |
        | New journey steps should store event_key directly.
        |
        */

        foreach ([
            'event_key',
            'whatsapp_event_key',
            'mapping_key',
            'trigger_key',
        ] as $key) {
            if (! empty($cfg[$key])) {
                return trim((string) $cfg[$key]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Template fallback
        |--------------------------------------------------------------------------
        |
        | If your whatsapp_templates table has an event_key column, this supports it.
        |
        */

        if ($template && isset($template->event_key) && trim((string) $template->event_key) !== '') {
            return trim((string) $template->event_key);
        }

        /*
        |--------------------------------------------------------------------------
        | Last fallback
        |--------------------------------------------------------------------------
        |
        | If an old journey only has template_id, we fallback to template name.
        | This will only work if your SendWhatsAppMessage::fireEvent() can resolve
        | template name as an event key. If not, it will safely log as missing mapping.
        |
        */

        if ($template && trim((string) $template->name) !== '') {
            return trim((string) $template->name);
        }

        return null;
    }

    private function resolvePhone(array $ctx): ?string
    {
        foreach ([
            'phone',
            'phone_norm',
            'to',
            'to_phone',
            'toE164',
            'to_e164',
            'customer_phone',
            'whatsapp',
        ] as $key) {
            if (! empty($ctx[$key])) {
                return trim((string) $ctx[$key]);
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | WAIT
    |--------------------------------------------------------------------------
    */

    private function wait(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $wait = $step->config['wait'] ?? ['minutes' => 5];

        $seconds =
            ($wait['seconds'] ?? 0) +
            ($wait['minutes'] ?? 0) * 60 +
            ($wait['hours'] ?? 0) * 3600;

        $ctx = $enr->context ?? [];
        $ctx['_wake_at'] = now()->addSeconds($seconds)->toISOString();

        $enr->update([
            'context'               => $ctx,
            'current_step_position' => $enr->current_step_position + 1,
        ]);

        $this->logStep(
            enr: $enr,
            step: $step,
            action: 'WAIT_SET',
            meta: [
                'seconds' => $seconds,
                'wake_at' => $ctx['_wake_at'],
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | IF
    |--------------------------------------------------------------------------
    */

    private function branch(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $if = $step->config['if'] ?? null;

        if (! $if) {
            $this->skip($enr);

            return;
        }

        $ctx = $enr->context ?? [];

        $left = data_get($ctx, $if['key']);
        $right = $if['value'] ?? null;
        $op = $if['op'] ?? '=';

        $result = match ($op) {
            '='  => $left == $right,
            '!=' => $left != $right,
            default => false,
        };

        $jump = $result
            ? ($if['then_jump'] ?? 1)
            : ($if['else_jump'] ?? 1);

        $enr->update([
            'current_step_position' => $enr->current_step_position + $jump,
        ]);

        $this->logStep(
            enr: $enr,
            step: $step,
            action: 'BRANCH_EVALUATED',
            meta: [
                'key'    => $if['key'] ?? null,
                'left'   => $left,
                'op'     => $op,
                'right'  => $right,
                'result' => $result,
                'jump'   => $jump,
            ]
        );

        $this->advance($enr);
    }

    private function tag(JourneyEnrollment $enr, JourneyStep $step): void
    {
        /*
        |--------------------------------------------------------------------------
        | Reserved for Phase 9
        |--------------------------------------------------------------------------
        */

        $this->logStep(
            enr: $enr,
            step: $step,
            action: 'TAG_SKIPPED',
            meta: [
                'reason' => 'TAG step reserved for future phase',
            ]
        );

        $this->skip($enr);
    }

    private function logStep(
        JourneyEnrollment $enr,
        JourneyStep $step,
        string $action,
        array $meta = []
    ): void {
        AutomationLog::create([
            'company_id'      => $enr->company_id,
            'entity_type'     => $enr->enrollable_type,
            'entity_id'       => $enr->enrollable_id,
            'automation_type' => 'journey',
            'action'          => $action,
            'meta'            => array_merge([
                'journey_id'    => $enr->journey_id,
                'enrollment_id' => $enr->id,
                'step_id'       => $step->id,
                'step_type'     => $step->type,
                'position'      => $step->position,
            ], $meta),
        ]);
    }
}