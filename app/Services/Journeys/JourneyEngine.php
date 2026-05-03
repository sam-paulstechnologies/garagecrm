<?php

namespace App\Services\Journeys;

use App\Models\Journey;
use App\Models\JourneyEnrollment;
use App\Models\JourneyStep;
use App\Models\AutomationLog;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\WhatsApp\ProviderFactory;

class JourneyEngine
{
    /**
     * Enroll entity into all active journeys matching trigger_key
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
     * Execute next step
     */
    public function advance(JourneyEnrollment $enr): void
    {
        if ($enr->status !== 'active') {
            return;
        }

        $enr->loadMissing('journey.steps');

        $step = $enr->journey->steps
            ->firstWhere('position', $enr->current_step_position + 1);

        if (!$step) {
            $enr->update(['status' => 'completed']);

            AutomationLog::create([
                'company_id'      => $enr->company_id,
                'entity_type'     => $enr->enrollable_type,
                'entity_id'       => $enr->enrollable_id,
                'automation_type' => 'journey',
                'action'          => 'COMPLETED',
                'meta'            => ['journey_id' => $enr->journey_id],
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
    }

    /* ---------------- WHATSAPP ---------------- */

    private function sendWhatsApp(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $cfg = $step->config ?? [];
        $template = WhatsAppTemplate::find($cfg['template_id'] ?? null);

        if (!$template) {
            $this->skip($enr);
            return;
        }

        $ctx = $enr->context ?? [];
        $to  = $ctx['phone'] ?? null;

        if (!$to) {
            $this->skip($enr);
            return;
        }

        $body = preg_replace_callback(
            '/{{\s*(\w+)\s*}}/',
            fn ($m) => $ctx[$m[1]] ?? '',
            $template->body
        );

        $msg = WhatsAppMessage::create([
            'company_id'  => $enr->company_id,
            'template_id' => $template->id,
            'to'          => $to,
            'direction'   => 'out',
            'status'      => 'queued',
            'payload'     => [
                'journey_id' => $enr->journey_id,
                'step_id'    => $step->id,
                'body'       => $body,
            ],
        ]);

        try {
            $resp = ProviderFactory::make()->send($to, $body);

            $msg->update([
                'status'              => 'sent',
                'provider_message_id' => $resp['sid'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $msg->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        $this->skip($enr);
    }

    /* ---------------- WAIT ---------------- */

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
    }

    /* ---------------- IF ---------------- */

    private function branch(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $if = $step->config['if'] ?? null;
        if (!$if) {
            $this->skip($enr);
            return;
        }

        $ctx   = $enr->context ?? [];
        $left  = data_get($ctx, $if['key']);
        $right = $if['value'] ?? null;
        $op    = $if['op'] ?? '=';

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

        $this->advance($enr);
    }

    private function tag(JourneyEnrollment $enr, JourneyStep $step): void
    {
        // reserved for Phase 9
        $this->skip($enr);
    }
}
