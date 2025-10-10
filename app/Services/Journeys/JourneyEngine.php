<?php

namespace App\Services\Journeys;

use App\Models\{Journey, JourneyEnrollment, JourneyStep, MessageTemplate, WhatsAppMessage};
use App\Services\WhatsApp\ProviderFactory;

class JourneyEngine
{
    /** Enroll an entity into all active journeys for a trigger and immediately advance */
    public function enrollForTrigger(int $companyId, string $trigger, object $enrollable, array $context = []): void
    {
        $journeys = Journey::where([
            'company_id' => $companyId,
            'trigger'    => $trigger,
            'is_active'  => true
        ])->get();

        foreach ($journeys as $journey) {
            $enr = JourneyEnrollment::create([
                'company_id'            => $companyId,
                'journey_id'            => $journey->id,
                'enrollable_type'       => get_class($enrollable),
                'enrollable_id'         => $enrollable->id,
                'current_step_position' => 0,
                'status'                => 'active',
                'context'               => $context,
            ]);

            $this->advance($enr);
        }
    }

    /** Advance to next step; handle types: SEND_WHATSAPP, WAIT, IF, TAG, STOP */
    public function advance(JourneyEnrollment $enr): void
    {
        if ($enr->status !== 'active') return;

        $step = $enr->journey->steps->firstWhere('position', $enr->current_step_position + 1);
        if (!$step) {
            $enr->update(['status' => 'completed']);
            return;
        }

        match ($step->type) {
            'SEND_WHATSAPP' => $this->sendWhatsApp($enr, $step),
            'WAIT'          => $this->wait($enr, $step),
            'IF'            => $this->branch($enr, $step),
            'TAG'           => $this->tag($enr, $step),
            'STOP'          => $enr->update(['status' => 'completed']),
            default         => $this->skip($enr),
        };
    }

    private function skip(JourneyEnrollment $enr): void
    {
        $enr->update(['current_step_position' => $enr->current_step_position + 1]);
        $this->advance($enr);
    }

    private function sendWhatsApp(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $cfg = $step->config ?? [];
        $template = MessageTemplate::find($cfg['template_id'] ?? 0);
        if (!$template || !$template->is_active) {
            $this->skip($enr);
            return;
        }

        $ctx = $enr->context ?? [];
        $to  = $ctx['phone'] ?? null;
        if (!$to) {
            $this->skip($enr);
            return;
        }

        // simple {{var}} interpolation from context
        $body = preg_replace_callback('/{{\s*(\w+)\s*}}/', fn($m) => $ctx[$m[1]] ?? '', $template->body);

        $msg = WhatsAppMessage::create([
            'company_id'           => $enr->company_id,
            'messageable_type'     => $enr->enrollable_type,
            'messageable_id'       => $enr->enrollable_id,
            'to'                   => $to,
            // keep FROM optional; provider client can use its configured default
            'from'                 => null,
            'message_template_id'  => $template->id,
            'body'                 => $body,
            'status'               => 'queued',
        ]);

        try {
            // Resolve correct provider based on WHATSAPP_PROVIDER
            $client = ProviderFactory::make();
            $resp   = $client->send($to, $body);

            $msg->update([
                'status'                => 'sent',
                // try common id shapes across providers
                'provider_message_id'   => $resp['sid'] ?? ($resp['messages'][0]['id'] ?? $resp['message_id'] ?? null),
                'meta'                  => [
                    'provider' => config('services.whatsapp.provider'),
                    'response' => $resp,
                ],
                'sent_at'               => now(),
            ]);
        } catch (\Throwable $e) {
            $msg->update([
                'status'    => 'failed',
                'meta'      => [
                    'provider' => config('services.whatsapp.provider'),
                    'error'    => $e->getMessage(),
                ],
                'failed_at' => now(),
            ]);
        }

        $this->skip($enr);
    }

    private function wait(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $wait    = $step->config['wait'] ?? ['minutes' => 5];
        $seconds = ($wait['seconds'] ?? 0)
                 + 60 * ($wait['minutes'] ?? 0)
                 + 3600 * ($wait['hours'] ?? 0);

        $ctx = $enr->context ?? [];
        $ctx['_wake_at'] = now()->addSeconds($seconds)->toISOString();

        $enr->update([
            'context'               => $ctx,
            'current_step_position' => $enr->current_step_position + 1,
        ]);
        // a scheduled command should pick it up when due
    }

    private function branch(JourneyEnrollment $enr, JourneyStep $step): void
    {
        $if = $step->config['if'] ?? null;
        if (!$if) {
            $this->skip($enr);
            return;
        }

        $ctx   = $enr->context ?? [];
        $left  = data_get($ctx, $if['key'] ?? null);
        $op    = $if['op'] ?? '=';
        $right = $if['value'] ?? null;

        $ok = match ($op) {
            '='        => $left == $right,
            '!='       => $left != $right,
            'contains' => is_string($left) && str_contains($left, (string) $right),
            default    => false,
        };

        $jump = (int) ($ok ? ($if['then_jump'] ?? 1) : ($if['else_jump'] ?? 1));

        $enr->update(['current_step_position' => $enr->current_step_position + $jump]);
        $this->advance($enr);
    }

    private function tag(JourneyEnrollment $enr, JourneyStep $step): void
    {
        // placeholder for future tagging functionality
        $this->skip($enr);
    }
}
