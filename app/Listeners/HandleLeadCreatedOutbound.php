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
use Illuminate\Support\Facades\Schema;

class HandleLeadCreatedOutbound implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /*
    |--------------------------------------------------------------------------
    | Default WhatsApp Event Key
    |--------------------------------------------------------------------------
    |
    | This is the proactive / first outbound lead acknowledgement.
    |
    | DB mapping should be:
    |
    |   lead.created -> lead_conversation_start_v1 / lead intent menu template
    |
    | Optional source-specific mappings can also exist:
    |
    |   lead.created.website
    |   lead.created.meta
    |
    | If source-specific mapping fails, we fallback to lead.created.
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

        $lockKey = 'lead_created_outbound_processed_' . (int) $lead->company_id . '_' . (int) $lead->id;

        if (! Cache::add($lockKey, true, now()->addMinutes(15))) {
            Log::info('[LeadCreatedOutbound] duplicate listener skipped by cache lock', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'lock_key' => $lockKey,
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
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'source' => $lead->source,
                'external_source' => $lead->external_source,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 4 — Skip bulk import leads
        |--------------------------------------------------------------------------
        |
        | Imported leads should be segmented, but should not be blindly messaged.
        | They should enter campaigns/fire rules only when explicitly selected.
        |
        */

        if ($this->isBulkImport($lead)) {
            Log::info('[LeadCreatedOutbound] skipping bulk import lead', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'source' => $lead->source,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 5 — Audience Segmentation Toggle
        |--------------------------------------------------------------------------
        */

        if (! $this->isAudienceSegmentationEnabled((int) $lead->company_id, $this->audienceSegmentationKey)) {
            Log::info('[LeadCreatedOutbound] audience segmentation disabled, ACK skipped', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'segment_key' => $this->audienceSegmentationKey,
                'event_key' => $this->eventKey,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 6 — Skip if ACK already marked
        |--------------------------------------------------------------------------
        */

        if ($this->ackAlreadyMarked($lead)) {
            Log::info('[LeadCreatedOutbound] ACK already marked on lead, skipped', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 7 — Prevent duplicate WhatsApp sends
        |--------------------------------------------------------------------------
        |
        | Codex found that fireEvent may persist outside message_logs in some paths.
        | So we check message_logs, optional whatsapp_messages, and wa_ack_sent.
        |
        */

        if ($this->hasAnyWhatsAppActivity($lead)) {
            Log::info('[LeadCreatedOutbound] WhatsApp activity already exists, ACK skipped', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
            ]);

            $this->markAckSent($lead, 'activity_exists');

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 8 — WhatsApp availability
        |--------------------------------------------------------------------------
        */

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        if (method_exists($wa, 'isActiveForCompany') && ! $wa->isActiveForCompany((int) $lead->company_id)) {
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

        $phone = $this->leadPhone($lead);

        if (! $phone) {
            Log::warning('[LeadCreatedOutbound] lead has no phone', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Send via DB-mapped WhatsApp event
        |--------------------------------------------------------------------------
        */

        $eventKeys = $this->candidateEventKeys($lead);

        $payload = $this->payload($lead, $phone);

        $lastError = null;

        foreach ($eventKeys as $eventKey) {
            try {
                Log::info('[LeadCreatedOutbound] firing WhatsApp event', [
                    'company_id' => $lead->company_id,
                    'lead_id' => $lead->id,
                    'phone' => $phone,
                    'source' => $lead->source,
                    'external_source' => $lead->external_source,
                    'event_key' => $eventKey,
                    'segment_key' => $this->audienceSegmentationKey,
                ]);

                /** @var SendWhatsAppMessage $sender */
                $sender = app(SendWhatsAppMessage::class);

                $sender->fireEvent(
                    (int) $lead->company_id,
                    $eventKey,
                    (string) $phone,
                    array_merge($payload, [
                        'event_key' => $eventKey,
                        'fallback_event_key' => $this->eventKey,
                    ])
                );

                $this->markAckSent($lead, $eventKey);

                Log::info('[LeadCreatedOutbound] WhatsApp ACK sent', [
                    'company_id' => $lead->company_id,
                    'lead_id' => $lead->id,
                    'phone' => $phone,
                    'event_key' => $eventKey,
                ]);

                return;
            } catch (\Throwable $e) {
                $lastError = $e;

                Log::warning('[LeadCreatedOutbound] WhatsApp event failed, trying fallback if available', [
                    'company_id' => $lead->company_id,
                    'lead_id' => $lead->id,
                    'event_key' => $eventKey,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        Log::error('[LeadCreatedOutbound] failed to fire all WhatsApp ACK events', [
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'attempted_event_keys' => $eventKeys,
            'error' => $lastError?->getMessage(),
        ]);

        $this->handoffToManager(
            lead: $lead,
            reason: 'Lead acknowledgement WhatsApp event failed: ' . ($lastError?->getMessage() ?: 'Unknown error')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Event key selection
    |--------------------------------------------------------------------------
    */

    protected function candidateEventKeys(Lead $lead): array
    {
        $source = strtolower(trim((string) $lead->source));
        $externalSource = strtolower(trim((string) $lead->external_source));

        $keys = [];

        if (str_contains($source, 'website') || str_contains($externalSource, 'website')) {
            $keys[] = 'lead.created.website';
        }

        if (
            str_contains($source, 'meta')
            || str_contains($externalSource, 'meta')
            || str_contains($source, 'facebook')
            || str_contains($source, 'instagram')
        ) {
            $keys[] = 'lead.created.meta';
        }

        /*
        |--------------------------------------------------------------------------
        | Canonical fallback
        |--------------------------------------------------------------------------
        */

        $keys[] = $this->eventKey;

        return collect($keys)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function payload(Lead $lead, string $phone): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Template variables
            |--------------------------------------------------------------------------
            */

            'name' => $lead->name ?? 'Customer',
            'customer_name' => $lead->name ?? 'Customer',
            'lead_name' => $lead->name ?? 'Customer',
            'phone' => $phone,
            'source' => $lead->source ?? '-',

            /*
            |--------------------------------------------------------------------------
            | Context variables
            |--------------------------------------------------------------------------
            */

            'company_id' => (int) $lead->company_id,
            'lead_id' => (int) $lead->id,
            'client_id' => $lead->client_id ? (int) $lead->client_id : null,
            'external_source' => $lead->external_source ?? null,
            'external_id' => $lead->external_id ?? null,
            'external_form_id' => $lead->external_form_id ?? null,

            'action' => 'initial',
            'send_mode' => 'meta_template',
            'audience_segmentation_key' => $this->audienceSegmentationKey,

            /*
            |--------------------------------------------------------------------------
            | Journey hint
            |--------------------------------------------------------------------------
            |
            | The actual WhatsApp template should say:
            |
            | Hi {Name}, how can we help you today?
            | 1. Service
            | 2. General Enquiry
            | 3. Speak to the Manager
            |
            */

            'journey' => 'lead_intent_menu',
            'menu_1' => 'Service',
            'menu_2' => 'General Enquiry',
            'menu_3' => 'Speak to the Manager',
        ];
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
        $externalSource = strtolower(trim((string) $lead->external_source));

        return in_array($source, [
            'csv',
            'excel',
            'import',
            'upload',
            'bulk',
            'bulk_import',
        ], true)
            || in_array($externalSource, [
                'csv',
                'excel',
                'import',
                'upload',
                'bulk',
                'bulk_import',
            ], true);
    }

    protected function leadPhone(Lead $lead): ?string
    {
        $phone = $lead->phone
            ?? $lead->phone_norm
            ?? $lead->whatsapp
            ?? $lead->whatsapp_number
            ?? null;

        $phone = trim((string) $phone);

        return $phone !== '' ? $phone : null;
    }

    protected function ackAlreadyMarked(Lead $lead): bool
    {
        try {
            if (
                Schema::hasColumn($lead->getTable(), 'wa_ack_sent')
                && (bool) ($lead->wa_ack_sent ?? false)
            ) {
                return true;
            }
        } catch (\Throwable $e) {
            Log::debug('[LeadCreatedOutbound] ACK marker check skipped', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    protected function markAckSent(Lead $lead, string $reason): void
    {
        try {
            $updates = [];

            if (Schema::hasColumn($lead->getTable(), 'wa_ack_sent')) {
                $updates['wa_ack_sent'] = true;
            }

            if (Schema::hasColumn($lead->getTable(), 'wa_ack_sent_at')) {
                $updates['wa_ack_sent_at'] = now();
            }

            if (Schema::hasColumn($lead->getTable(), 'conversation_data')) {
                $data = $lead->conversation_data ?? [];
                $data = is_array($data) ? $data : [];

                $updates['conversation_data'] = array_merge($data, [
                    'lead_ack_sent' => true,
                    'lead_ack_sent_at' => now()->toIso8601String(),
                    'lead_ack_reason' => $reason,
                    'lead_ack_event_key' => $reason,
                ]);
            }

            if (! empty($updates)) {
                $lead->forceFill($updates)->save();
            }
        } catch (\Throwable $e) {
            Log::debug('[LeadCreatedOutbound] ACK marker update skipped', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function hasAnyWhatsAppActivity(Lead $lead): bool
    {
        $leadId = (int) $lead->id;
        $companyId = (int) $lead->company_id;

        /*
        |--------------------------------------------------------------------------
        | Message logs
        |--------------------------------------------------------------------------
        */

        try {
            if (Schema::hasTable('message_logs')) {
                $exists = MessageLog::query()
                    ->where('company_id', $companyId)
                    ->where('lead_id', $leadId)
                    ->where('channel', 'whatsapp')
                    ->where('direction', 'out')
                    ->where(function ($query) {
                        $query->where('template', $this->eventKey)
                            ->orWhere('template', 'lead_conversation_start_v1')
                            ->orWhere('template', 'new_lead_ack_v1')
                            ->orWhere('meta->event_key', $this->eventKey)
                            ->orWhere('meta->event_key', 'lead.created.website')
                            ->orWhere('meta->event_key', 'lead.created.meta')
                            ->orWhere('meta->action', 'initial');
                    })
                    ->exists();

                if ($exists) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::debug('[LeadCreatedOutbound] message_logs duplicate check skipped', [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Optional whatsapp_messages table
        |--------------------------------------------------------------------------
        |
        | Some send paths persist here instead of message_logs.
        | We only query columns if they exist.
        |
        */

        try {
            if (Schema::hasTable('whatsapp_messages')) {
                $query = \DB::table('whatsapp_messages');

                if (Schema::hasColumn('whatsapp_messages', 'company_id')) {
                    $query->where('company_id', $companyId);
                }

                if (Schema::hasColumn('whatsapp_messages', 'lead_id')) {
                    $query->where('lead_id', $leadId);
                }

                if (Schema::hasColumn('whatsapp_messages', 'direction')) {
                    $query->where('direction', 'out');
                }

                if (Schema::hasColumn('whatsapp_messages', 'template')) {
                    $query->where(function ($q) {
                        $q->where('template', $this->eventKey)
                            ->orWhere('template', 'lead_conversation_start_v1')
                            ->orWhere('template', 'new_lead_ack_v1');
                    });
                } elseif (Schema::hasColumn('whatsapp_messages', 'event_key')) {
                    $query->whereIn('event_key', [
                        $this->eventKey,
                        'lead.created.website',
                        'lead.created.meta',
                    ]);
                } else {
                    return false;
                }

                if ($query->exists()) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::debug('[LeadCreatedOutbound] whatsapp_messages duplicate check skipped', [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    protected function isAudienceSegmentationEnabled(int $companyId, string $segmentKey): bool
    {
        $segmentation = AudienceSegmentation::query()
            ->where('key', $segmentKey)
            ->first();

        if (! $segmentation) {
            Log::warning('[LeadCreatedOutbound] audience segmentation missing, defaulting enabled', [
                'company_id' => $companyId,
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
                'lead_id' => $lead->id,
                'channel' => 'system',
                'template' => null,
                'body' => null,
                'meta' => [
                    'action' => 'manager_handoff',
                    'reason' => $reason,
                    'event_key' => 'manager.attention_required',
                    'source' => 'handle_lead_created_outbound',
                ],
            ]);

            if ($lead->assignee && method_exists($lead->assignee, 'notify')) {
                $lead->assignee->notify(
                    new ManagerLeadHandoffNotification(
                        companyId: $lead->company_id,
                        leadId: $lead->id,
                        name: $lead->name ?? 'Lead',
                        phone: $this->leadPhone($lead) ?? 'N/A',
                        source: $lead->source ?? '-',
                        reason: $reason
                    )
                );
            }
        } catch (\Throwable $e) {
            Log::error('[LeadCreatedOutbound][ManagerHandoff] ' . $e->getMessage(), [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
            ]);
        }
    }
}