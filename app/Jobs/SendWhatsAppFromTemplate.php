<?php

namespace App\Jobs;

use App\Models\MessageLog; // unified logger
use App\Models\Client\Lead;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SendWhatsAppFromTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;
    public $backoff = [10, 30, 60];

    /**
     * $action:
     *  - 'initial'       : first ACK to lead (will schedule a follow-up check)
     *  - 'follow_up'     : ask for missing details ONLY if no inbound since 'initial'
     *  - 'collect_vehicle': asking for make/model
     *  - 'collect_timeslot': asking for date/time
     *  - 'confirmed'     : booking confirmation
     *  - 'reminder'      : booking reminder
     *  - 'feedback'      : thank-you / feedback request
     */
    public string $action;

    public function __construct(
        public int    $companyId,
        public int    $leadId,
        public string $toNumberE164,
        public string $templateName,
        public array  $placeholders = [],
        public array  $links = [],
        public array  $context = [],
        string        $action = 'initial'
    ) {
        $this->action = $action;
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("wa-send-{$this->leadId}"))->expireAfter(120),
            new RateLimited('wa-sends'),
        ];
    }

    public function handle(WhatsAppService $wa): void
    {
        $ctx = array_merge($this->context, [
            'company_id' => $this->companyId,
            'lead_id'    => $this->leadId,
            'action'     => $this->action, // <- always propagate action
        ]);

        // De-dupe: include action and shorten window (2 minutes)
        if ($this->isDuplicateRecently(
            leadId:    $this->leadId,
            template:  $this->templateName,
            action:    $this->action,
            minutes:   2
        )) {
            Log::info('[WA][Send] skipped duplicate', [
                'lead_id'  => $this->leadId,
                'template' => $this->templateName,
                'action'   => $this->action,
            ]);
            return;
        }

        $from = $this->getCompanyFromNumber($this->companyId);
        $isSandbox = is_string($from) && str_contains($from, '+14155238886');

        try {
            if ($isSandbox) {
                // sandbox: render template as text and send
                $text = $wa->assembleTemplateAsText($this->templateName, $this->placeholders, $this->links);
                $res  = $wa->sendText($this->toNumberE164, $text, $ctx);

                MessageLog::out([
                    'company_id'          => $this->companyId,
                    'lead_id'             => $this->leadId,
                    'channel'             => 'whatsapp',
                    'direction'           => 'out',
                    'to_number'           => $this->toNumberE164,
                    'from_number'         => $from ?: null,
                    'template'            => $this->templateName,
                    'body'                => $text,
                    'provider_message_id' => is_array($res) ? ($res['sid'] ?? null) : null,
                    'provider_status'     => is_array($res) ? ($res['status'] ?? 'queued') : 'queued',
                    // Always include 'action' in meta for de-dupe by action
                    'meta'                => array_merge(['action' => $this->action], is_array($res) ? $res : []),
                ]);
            } else {
                // live: template path (Meta/Twilio)
                $res = $wa->sendTemplate(
                    toE164:       $this->toNumberE164,
                    templateName: $this->templateName,
                    params:       $this->placeholders,
                    links:        $this->links,
                    context:      $ctx
                );

                MessageLog::out([
                    'company_id'          => $this->companyId,
                    'lead_id'             => $this->leadId,
                    'channel'             => 'whatsapp',
                    'direction'           => 'out',
                    'to_number'           => $this->toNumberE164,
                    'from_number'         => $from ?: null,
                    'template'            => $this->templateName,
                    'body'                => null,
                    'provider_message_id' => is_array($res) ? ($res['sid'] ?? null) : null,
                    'provider_status'     => is_array($res) ? ($res['status'] ?? 'queued') : 'queued',
                    'meta'                => array_merge(['action' => $this->action], is_array($res) ? $res : []),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[WA][Send] exception: '.$e->getMessage(), [
                'company_id' => $this->companyId,
                'lead_id'    => $this->leadId,
                'template'   => $this->templateName,
                'action'     => $this->action,
            ]);
            throw $e;
        }

        // Chain: schedule a follow-up (only from 'initial')
        if ($this->action === 'initial') {
            self::dispatch(
                companyId:    $this->companyId,
                leadId:       $this->leadId,
                toNumberE164: $this->toNumberE164,
                templateName: 'ask_make_model_v1', // standardized name
                placeholders: [],
                links:        [],
                context:      array_merge($ctx, ['since' => now()->toDateTimeString()]),
                action:       'follow_up'
            )->delay(now()->addMinutes(10));
        }

        // If this is a follow-up, bail if user already replied since 'since'
        if ($this->action === 'follow_up') {
            $sinceStr = $this->context['since'] ?? null;
            $since    = $sinceStr ? now()->parse($sinceStr) : now()->subMinutes(15);

            if ($this->hasRecentInbound($this->leadId, $this->toNumberE164, $since)) {
                Log::info('[WA][FollowUp] skipped — inbound already received', [
                    'lead_id' => $this->leadId, 'since' => $since
                ]);
                return;
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[WA][JobFailed] '.$e->getMessage(), [
            'company_id' => $this->companyId,
            'lead_id'    => $this->leadId,
            'template'   => $this->templateName,
            'action'     => $this->action,
        ]);

        try {
            $this->notifyManager(app(WhatsAppService::class), 'send_failed: '.$e->getMessage());
        } catch (\Throwable $e2) {
            Log::error('[WA][ManagerAlertFail] '.$e2->getMessage(), [
                'company_id' => $this->companyId,
                'lead_id'    => $this->leadId,
            ]);
        }
    }

    /* ==================== helpers ==================== */

    protected function isDuplicateRecently(
        int $leadId,
        string $template,
        ?string $action,
        int $minutes = 2
    ): bool {
        try {
            $q = DB::table('message_logs')
                ->where('lead_id', $leadId)
                ->where('direction', 'out')
                ->where('channel', 'whatsapp')
                ->where('template', $template)
                ->where('created_at', '>=', now()->subMinutes($minutes));

            // De-dupe by action if we have it in meta
            if ($action) {
                $q->where('meta->action', $action);
            }

            return $q->exists();
        } catch (\Throwable $e) {
            // If DB JSON path fails for any reason, fail-open (no dedupe)
            Log::debug('[WA][Send] dedupe check failed: '.$e->getMessage());
            return false;
        }
    }

    protected function hasRecentInbound(int $leadId, string $toNumber, \DateTimeInterface $since): bool
    {
        $digits = preg_replace('/\D+/', '', $toNumber);

        return DB::table('message_logs')
            ->where('direction', 'in')
            ->where(function ($q) use ($leadId, $digits) {
                $q->where('lead_id', $leadId);
                if ($digits !== '') {
                    $q->orWhere('from_number', 'like', "%{$digits}%");
                }
            })
            ->where('created_at', '>', $since)
            ->exists();
    }

    protected function notifyManager(WhatsAppService $wa, string $reason): void
    {
        $manager = $this->resolveManagerNumber();
        if (!$manager) return;

        try {
            $lead = Lead::find($this->leadId);
            $wa->sendTemplate(
                toE164:       $manager,
                templateName: 'manager_call_lead',
                params:       [
                    $lead?->name ?? 'Lead',
                    $lead?->phone ?? 'N/A',
                    $lead?->source ?? '-',
                    $reason,
                ],
                links:        [],
                context:      ['company_id' => $this->companyId, 'lead_id' => $this->leadId]
            );
        } catch (\Throwable $e) {
            Log::error('[WA][ManagerAlert] '.$e->getMessage(), [
                'company_id' => $this->companyId,
                'lead_id'    => $this->leadId
            ]);
        }
    }

    protected function resolveManagerNumber(): ?string
    {
        try {
            $val = DB::table('company_settings')
                ->where('company_id', $this->companyId)
                ->where('key', 'whatsapp.manager_number')
                ->value('value');

            $val = is_string($val) ? trim($val) : null;
            return $val !== '' ? $val : null;
        } catch (\Throwable $e) {
            Log::error('[WA] resolveManagerNumber failed', [
                'company_id' => $this->companyId,
                'err'        => $e->getMessage()
            ]);
            return null;
        }
    }

    private function getCompanyFromNumber(int $companyId): ?string
    {
        try {
            $kv = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->whereIn('key', ['twilio.whatsapp_from', 'twilio_whatsapp_from'])
                ->value('value');
            if ($kv) return $kv;

            return DB::table('company_settings')
                ->where('company_id', $companyId)
                ->value('twilio_whatsapp_from');
        } catch (\Throwable $e) {
            Log::error('[WA] getCompanyFromNumber failed: '.$e->getMessage(), ['company_id' => $companyId]);
            return null;
        }
    }
}
