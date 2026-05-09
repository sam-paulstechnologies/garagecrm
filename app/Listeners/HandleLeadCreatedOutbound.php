<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\AudienceSegmentation;
use App\Models\Client\Lead;
use App\Models\CompanyAudienceSegmentationSetting;
use App\Models\MessageLog;
use App\Notifications\ManagerLeadHandoffNotification;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleLeadCreatedOutbound implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries   = 3;
    public $backoff = [10, 30, 60];

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Event Key
    |--------------------------------------------------------------------------
    |
    | This is a proactive / first outbound lead acknowledgement.
    |
    | DB mapping should be:
    |
    |   lead.created -> new_lead_ack_v1
    |
    */

    protected string $eventKey = 'lead.created';

    /*
    |--------------------------------------------------------------------------
    | Audience Segmentation Key
    |--------------------------------------------------------------------------
    |
    | This listener respects the enable/disable toggle from:
    |
    |   Settings -> Audience Segmentation -> New Lead Conversation
    |
    */

    protected string $audienceSegmentationKey = 'new_lead_conversation';

    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        /*
        |--------------------------------------------------------------------------
        | Guard 1 — Sanity
        |--------------------------------------------------------------------------
        */

        if (! $lead || ! $lead->company_id || ! $lead->id) {
            Log::warning('[LeadCreatedOutbound] invalid lead');
            return;
        }

        $lead->refresh();

        /*
        |--------------------------------------------------------------------------
        | Guard 2 — Cache lock to prevent duplicate listener execution
        |--------------------------------------------------------------------------
        */

        $lockKey = 'lead_created_outbound_processed_' . $lead->id;

        if (! Cache::add($lockKey, true, now()->addMinutes(10))) {
            Log::info('[LeadCreatedOutbound] duplicate listener skipped by cache lock', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 3 — Skip WhatsApp-origin leads
        |--------------------------------------------------------------------------
        |
        | WhatsApp-origin leads are handled by inbound conversation flow.
        | Inbound replies are session messages from the app, not template ACKs.
        |
        */

        if ($this->isWhatsAppOrigin($lead)) {
            Log::info('[LeadCreatedOutbound] skipping WhatsApp-origin lead', [
                'lead_id'         => $lead->id,
                'source'          => $lead->source,
                'external_source' => $lead->external_source,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 4 — Skip bulk import leads
        |--------------------------------------------------------------------------
        */

        if ($this->isBulkImport($lead)) {
            Log::info('[LeadCreatedOutbound] skipping bulk import lead', [
                'lead_id' => $lead->id,
                'source'  => $lead->source,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 5 — Audience Segmentation Toggle
        |--------------------------------------------------------------------------
        |
        | If New Lead Conversation is disabled from Settings -> Audience
        | Segmentation, do not send the new lead ACK.
        |
        */

        if (! $this->isAudienceSegmentationEnabled((int) $lead->company_id, $this->audienceSegmentationKey)) {
            Log::info('[LeadCreatedOutbound] audience segmentation disabled, ACK skipped', [
                'lead_id'     => $lead->id,
                'company_id'  => $lead->company_id,
                'segment_key' => $this->audienceSegmentationKey,
                'event_key'   => $this->eventKey,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 6 — Prevent duplicate WhatsApp sends
        |--------------------------------------------------------------------------
        */

        if ($this->hasAnyWhatsAppActivity($lead->id)) {
            Log::info('[LeadCreatedOutbound] WhatsApp activity already exists', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 7 — WhatsApp availability
        |--------------------------------------------------------------------------
        */

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        if (method_exists($wa, 'isActiveForCompany') && ! $wa->isActiveForCompany($lead->company_id)) {
            $this->handoffToManager(
                lead: $lead,
                reason: 'WhatsApp inactive or not configured'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare phone
        |--------------------------------------------------------------------------
        */

        $phone = $lead->phone ?: $lead->phone_norm;

        if (! $phone) {
            Log::warning('[LeadCreatedOutbound] lead has no phone', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Send via DB-mapped WhatsApp event
        |--------------------------------------------------------------------------
        */

        try {
            Log::info('[LeadCreatedOutbound] firing WhatsApp event', [
                'lead_id'     => $lead->id,
                'company_id'  => $lead->company_id,
                'phone'       => $phone,
                'source'      => $lead->source,
                'event_key'   => $this->eventKey,
                'segment_key' => $this->audienceSegmentationKey,
            ]);

            /** @var SendWhatsAppMessage $sender */
            $sender = app(SendWhatsAppMessage::class);

            $sender->fireEvent(
                (int) $lead->company_id,
                $this->eventKey,
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

                    'company_id'               => (int) $lead->company_id,
                    'lead_id'                  => (int) $lead->id,
                    'action'                   => 'initial',
                    'event_key'                => $this->eventKey,
                    'send_mode'                => 'meta_template',
                    'audience_segmentation_key'=> $this->audienceSegmentationKey,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[LeadCreatedOutbound] failed to fire WhatsApp event', [
                'lead_id'    => $lead->id,
                'company_id' => $lead->company_id,
                'event_key'  => $this->eventKey,
                'error'      => $e->getMessage(),
            ]);

            $this->handoffToManager(
                lead: $lead,
                reason: 'Lead acknowledgement WhatsApp event failed: ' . $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    */

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

    protected function isBulkImport(Lead $lead): bool
    {
        $source = strtolower(trim((string) $lead->source));

        return in_array($source, [
            'csv',
            'excel',
            'import',
            'upload',
        ], true);
    }

    protected function hasAnyWhatsAppActivity(int $leadId): bool
    {
        return MessageLog::where('lead_id', $leadId)
            ->where('channel', 'whatsapp')
            ->exists();
    }

    protected function isAudienceSegmentationEnabled(int $companyId, string $segmentKey): bool
    {
        $segmentation = AudienceSegmentation::query()
            ->where('key', $segmentKey)
            ->first();

        if (! $segmentation) {
            Log::warning('[LeadCreatedOutbound] audience segmentation missing, defaulting enabled', [
                'company_id'  => $companyId,
                'segment_key' => $segmentKey,
            ]);

            return true;
        }

        $setting = CompanyAudienceSegmentationSetting::query()
            ->where('company_id', $companyId)
            ->where('audience_segmentation_id', $segmentation->id)
            ->first();

        if (! $setting) {
            return (bool) $segmentation->default_enabled;
        }

        return (bool) $setting->is_enabled;
    }

    /*
    |--------------------------------------------------------------------------
    | Manager handoff
    |--------------------------------------------------------------------------
    */

    protected function handoffToManager(Lead $lead, string $reason): void
    {
        try {
            MessageLog::out([
                'company_id' => $lead->company_id,
                'lead_id'    => $lead->id,
                'channel'    => 'system',
                'template'   => null,
                'body'       => null,
                'meta'       => [
                    'action' => 'manager_handoff',
                    'reason' => $reason,
                ],
            ]);

            if ($lead->assignee && method_exists($lead->assignee, 'notify')) {
                $lead->assignee->notify(
                    new ManagerLeadHandoffNotification(
                        companyId: $lead->company_id,
                        leadId: $lead->id,
                        name: $lead->name ?? 'Lead',
                        phone: $lead->phone ?? 'N/A',
                        source: $lead->source ?? '-',
                        reason: $reason
                    )
                );
            }
        } catch (\Throwable $e) {
            Log::error('[LeadCreatedOutbound][ManagerHandoff] ' . $e->getMessage(), [
                'lead_id' => $lead->id,
            ]);
        }
    }
}