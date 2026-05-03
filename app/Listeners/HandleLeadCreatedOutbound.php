<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Models\MessageLog;
use App\Models\Client\Lead;
use App\Notifications\ManagerLeadHandoffNotification;
use App\Services\WhatsApp\WhatsAppService;
use App\Jobs\SendWhatsAppFromTemplate;
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
    | IMPORTANT
    |--------------------------------------------------------------------------
    | Use Utility template for website lead acknowledgement.
    | Marketing template lead_conversation_start_v1 was getting blocked with 131049.
    */
    protected string $welcomeTemplate = 'follow_up_new_lead_v1';

    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        /*
        |--------------------------------------------------------------------------
        | Guard 1 — sanity
        |--------------------------------------------------------------------------
        */

        if (!$lead || !$lead->company_id || !$lead->id) {
            Log::warning('[LeadCreatedOutbound] invalid lead');
            return;
        }

        $lead->refresh();

        /*
        |--------------------------------------------------------------------------
        | Guard 2 — Cache lock to prevent duplicate event/listener execution
        |--------------------------------------------------------------------------
        */

        $lockKey = 'lead_created_outbound_processed_' . $lead->id;

        if (!Cache::add($lockKey, true, now()->addMinutes(10))) {
            Log::info('[LeadCreatedOutbound] duplicate listener skipped by cache lock', [
                'lead_id' => $lead->id,
            ]);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 3 — Skip ONLY WhatsApp-origin leads
        |--------------------------------------------------------------------------
        */

        if ($this->isWhatsAppOrigin($lead)) {

            Log::info('[LeadCreatedOutbound] skipping WA-origin lead', [
                'lead_id'         => $lead->id,
                'source'          => $lead->source,
                'external_source' => $lead->external_source,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Guard 3.5 — Skip bulk import leads (CSV/Excel)
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
        | Guard 4 — Prevent duplicate sends if activity already exists
        |--------------------------------------------------------------------------
        */

        if ($this->hasAnyWhatsAppActivity($lead->id)) {

            Log::info('[LeadCreatedOutbound] WA activity already exists', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        /*
        |--------------------------------------------------------------------------
        | Guard 5 — WhatsApp availability
        |--------------------------------------------------------------------------
        */

        if (method_exists($wa, 'isActiveForCompany') && !$wa->isActiveForCompany($lead->company_id)) {

            $this->handoffToManager(
                lead:   $lead,
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

        if (!$phone) {

            Log::warning('[LeadCreatedOutbound] lead has no phone', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Dispatch Utility welcome message
        |--------------------------------------------------------------------------
        */

        Log::info('[LeadCreatedOutbound] sending welcome message', [
            'lead_id'  => $lead->id,
            'phone'    => $phone,
            'source'   => $lead->source,
            'template' => $this->welcomeTemplate,
        ]);

        SendWhatsAppFromTemplate::dispatch(
            companyId:    $lead->company_id,
            leadId:       $lead->id,
            toNumberE164: $phone,
            templateName: $this->welcomeTemplate,
            placeholders: [$lead->name ?? 'Customer'],
            links:        [],
            context: [
                'company_id' => $lead->company_id,
                'lead_id'    => $lead->id,
                'source'     => $lead->source,
                'template_category' => 'utility',
            ],
            action: 'initial'
        );
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
                        leadId:    $lead->id,
                        name:      $lead->name ?? 'Lead',
                        phone:     $lead->phone ?? 'N/A',
                        source:    $lead->source ?? '-',
                        reason:    $reason
                    )
                );
            }

        } catch (\Throwable $e) {

            Log::error('[LeadCreated][ManagerHandoff] '.$e->getMessage(), [
                'lead_id' => $lead->id,
            ]);
        }
    }
}