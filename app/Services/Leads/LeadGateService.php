<?php

namespace App\Services\Leads;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\MessageLog;
use App\Models\TimelineComment;
use App\Services\Moderation\ProfanityGuard;
use App\Services\WhatsApp\ManagerNotificationService;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadGateService
{
    /*
    |--------------------------------------------------------------------------
    | Lead Gate Service
    |--------------------------------------------------------------------------
    |
    | This service runs after lead creation.
    |
    | Since this is proactive / first outbound, WhatsApp must go through:
    |
    |   SendWhatsAppMessage::fireEvent()
    |
    | using DB mapping + approved Meta template.
    |
    | It must NOT call:
    |
    |   WhatsAppService::sendTemplate()
    |
    */

    protected string $leadCreatedEventKey = 'lead.created';

    public function __construct(
        protected ProfanityGuard $profanity,
        protected ManagerNotificationService $managerNotificationService
    ) {}

    /**
     * Main entry point after lead creation.
     */
    public function process(Lead $lead): void
    {
        $lead->refresh();

        if (! $lead->id || ! $lead->company_id) {
            Log::warning('[LeadGateService] Invalid lead, skipped', [
                'lead_id'    => $lead->id ?? null,
                'company_id' => $lead->company_id ?? null,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Skip WhatsApp-origin leads
        |--------------------------------------------------------------------------
        |
        | WhatsApp-origin leads are handled by the inbound/session flow.
        |
        */

        if ($this->isWhatsAppOrigin($lead)) {
            Log::info('[LeadGateService] WhatsApp-origin lead skipped', [
                'lead_id' => $lead->id,
                'source'  => $lead->source,
            ]);

            $this->autoConvert($lead);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate ACK
        |--------------------------------------------------------------------------
        |
        | HandleLeadCreatedOutbound.php also sends lead.created.
        | If any WhatsApp activity already exists, do not send another ACK here.
        |
        */

        if ($this->hasAnyWhatsAppActivity($lead)) {
            Log::info('[LeadGateService] WhatsApp activity exists, lead ACK skipped', [
                'lead_id'    => $lead->id,
                'company_id' => $lead->company_id,
            ]);

            $this->autoConvert($lead);

            return;
        }

        $phone = $this->leadPhone($lead);

        if (! $phone) {
            $this->handover($lead, 'Lead has no WhatsApp phone number');

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Send approved Meta template via DB mapping
        |--------------------------------------------------------------------------
        */

        try {
            app(SendWhatsAppMessage::class)->fireEvent(
                (int) $lead->company_id,
                $this->leadCreatedEventKey,
                (string) $phone,
                [
                    /*
                    |--------------------------------------------------------------------------
                    | Template variables
                    |--------------------------------------------------------------------------
                    */

                    'name'          => $lead->name ?? 'Customer',
                    'customer_name' => $lead->name ?? 'Customer',
                    'lead_name'     => $lead->name ?? 'Customer',
                    'phone'         => $phone,
                    'source'        => $lead->source ?? '-',

                    /*
                    |--------------------------------------------------------------------------
                    | Context variables
                    |--------------------------------------------------------------------------
                    */

                    'company_id'    => (int) $lead->company_id,
                    'lead_id'       => (int) $lead->id,
                    'event_key'     => $this->leadCreatedEventKey,
                    'source'        => 'lead_gate_service',
                    'action'        => 'lead_gate_ack',
                    'send_mode'     => 'meta_template',
                ]
            );

            Log::info('[LeadGateService] Lead WhatsApp ACK event fired', [
                'lead_id'    => $lead->id,
                'company_id' => $lead->company_id,
                'event_key'  => $this->leadCreatedEventKey,
            ]);

            $this->autoConvert($lead);
        } catch (\Throwable $e) {
            Log::error('[LeadGateService] Lead WhatsApp ACK failed', [
                'lead_id'    => $lead->id,
                'company_id' => $lead->company_id,
                'event_key'  => $this->leadCreatedEventKey,
                'error'      => $e->getMessage(),
            ]);

            $this->handover($lead, 'WhatsApp not delivered: ' . $e->getMessage());
        }
    }

    protected function autoConvert(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {
            $lead->refresh();

            $client = Client::firstOrCreate(
                [
                    'company_id' => $lead->company_id,
                    'phone'      => $lead->phone,
                ],
                [
                    'name'  => $lead->name,
                    'email' => $lead->email,
                ]
            );

            Opportunity::firstOrCreate(
                [
                    'lead_id'    => $lead->id,
                    'company_id' => $lead->company_id,
                ],
                [
                    'client_id' => $client->id,
                    'stage'     => 'new',
                    'source'    => $lead->source,
                ]
            );

            $lead->update([
                'client_id' => $client->id,
                'status'    => 'auto_qualified',
            ]);

            TimelineComment::system(
                $lead->company_id,
                'lead',
                $lead->id,
                'Lead auto-qualified after WhatsApp ACK flow.'
            );
        });
    }

    protected function handover(Lead $lead, string $reason): void
    {
        $lead->update(['status' => 'manager_review']);

        TimelineComment::system(
            $lead->company_id,
            'lead',
            $lead->id,
            "Lead handed to manager: {$reason}"
        );

        try {
            $this->managerNotificationService->notifyForLead(
                lead: $lead,
                reason: $reason,
                preferredAt: null,
                bookingId: null,
                extra: [
                    'source' => 'lead_gate_service',
                    'action' => 'lead_gate_handover',
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[LeadGateService] Manager handover notification failed', [
                'lead_id' => $lead->id,
                'reason'  => $reason,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    protected function hasAnyWhatsAppActivity(Lead $lead): bool
    {
        return MessageLog::where('company_id', $lead->company_id)
            ->where('lead_id', $lead->id)
            ->where('channel', 'whatsapp')
            ->exists();
    }

    protected function leadPhone(Lead $lead): ?string
    {
        $phone = $lead->phone_norm ?: $lead->phone;

        $phone = trim((string) $phone);

        return $phone !== '' ? $phone : null;
    }

    protected function isWhatsAppOrigin(Lead $lead): bool
    {
        $source = strtolower(trim((string) $lead->source));
        $externalSource = strtolower(trim((string) $lead->external_source));

        return in_array($source, [
            'whatsapp',
            'wa',
            'meta whatsapp',
        ], true)
            || in_array($externalSource, [
                'whatsapp',
                'wa',
                'meta whatsapp',
            ], true);
    }
}