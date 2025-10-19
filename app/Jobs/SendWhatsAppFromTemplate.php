<?php

namespace App\Jobs;

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
use App\Support\MessageLog;

class SendWhatsAppFromTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Retry policy for transient failures */
    public $tries   = 3;
    public $backoff = [10, 30, 60]; // seconds

    /**
     * action:
     *  - 'initial'           : first ACK to lead
     *  - 'ask_vehicle_info'  : follow-up after delay
     */
    public string $action;

    public function __construct(
        public int $companyId,
        public int $leadId,
        public string $toNumberE164,
        public string $templateName,
        public array $placeholders = [],
        public array $links = [],
        public array $context = [],
        string $action = 'initial'
    ) {
        $this->action = $action;

        // Pin this job to DB driver + default queue
        $this->onConnection('database');
        $this->onQueue('default');
    }

    /** Optional: protect from bursts & duplicate work per lead */
    public function middleware(): array
    {
        return [
            // Prevent overlapping sends for the same lead for 2 minutes
            (new WithoutOverlapping("wa-send-{$this->leadId}"))->expireAfter(120),

            // Simple global rate limit bucket (configure in cache)
            new RateLimited('wa-sends'),
        ];
    }

    public function handle(WhatsAppService $wa): void
    {
        $ctx = array_merge($this->context, [
            'company_id' => $this->companyId,
            'lead_id'    => $this->leadId,
            'job_id'     => $this->job?->getJobId(), // set only on worker
        ]);

        $from = $this->getCompanyFromNumber($this->companyId);
        $isSandbox = is_string($from) && str_contains($from, '+14155238886'); // Twilio sandbox number

        try {
            if ($isSandbox) {
                // Twilio sandbox doesn’t support business templates
                $text = $this->action === 'initial'
                    ? "Hey! 👋 We’ve received your details. Our manager will call you shortly."
                    : "Could you please share your car’s *Make & Model*?";

                $res = $wa->sendText($this->toNumberE164, $text, $ctx);

                // ✅ OUTBOUND LOG (sandbox) → message_logs
                MessageLog::out([
                    'company_id'          => $this->companyId,
                    'lead_id'             => $this->leadId,
                    'to_number'           => $this->toNumberE164,
                    'from_number'         => $from ?: null,
                    'template'            => null,
                    'body'                => $text,
                    'provider_message_id' => is_array($res) ? ($res['sid'] ?? ($res['messageSid'] ?? null)) : null,
                    'provider_status'     => is_array($res) ? ($res['status'] ?? ($res['messageStatus'] ?? 'queued')) : 'queued',
                    'meta'                => json_encode($res, JSON_UNESCAPED_UNICODE),
                ]);
            } else {
                $res = $wa->sendTemplate(
                    toE164:       $this->toNumberE164,
                    templateName: $this->templateName,
                    params:       $this->placeholders,
                    links:        $this->links,
                    context:      $ctx
                );

                // ✅ OUTBOUND LOG (business/templates) → message_logs
                MessageLog::out([
                    'company_id'          => $this->companyId,
                    'lead_id'             => $this->leadId,
                    'to_number'           => $this->toNumberE164,
                    'from_number'         => $from ?: null,
                    'template'            => $this->templateName,
                    'body'                => null,
                    'provider_message_id' => is_array($res) ? ($res['sid'] ?? ($res['messageSid'] ?? null)) : null,
                    'provider_status'     => is_array($res) ? ($res['status'] ?? ($res['messageStatus'] ?? 'queued')) : 'queued',
                    'meta'                => json_encode($res, JSON_UNESCAPED_UNICODE),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[WA][Send] exception: '.$e->getMessage(), [
                'company_id' => $this->companyId,
                'lead_id'    => $this->leadId,
                'template'   => $this->templateName,
            ]);
            // Let the queue retry; escalate in failed()
            throw $e;
        }

        // Provider returned an app-level error?
        if (isset($res) && is_array($res) && isset($res['error'])) {
            throw new \RuntimeException((string) $res['error']);
        }

        // Success path
        if ($this->action === 'initial') {
            try {
                if ($lead = Lead::find($this->leadId)) {
                    if (method_exists($lead, 'convertToClient')) {
                        $lead->convertToClient();
                    } else {
                        $lead->status = 'converted';
                        $lead->save();
                    }
                }
            } catch (\Throwable $e) {
                Log::error('[WA][Convert] '.$e->getMessage(), [
                    'company_id' => $this->companyId,
                    'lead_id'    => $this->leadId
                ]);
            }

            // Schedule follow-up in 2 minutes asking for Make/Model
            self::dispatch(
                companyId:    $this->companyId,
                leadId:       $this->leadId,
                toNumberE164: $this->toNumberE164,
                templateName: 'visit_feedback_v1', // or 'ask_make_model'
                placeholders: [],
                links:        [],
                context:      $ctx,
                action:       'ask_vehicle_info'
            )->delay(now()->addMinutes(2));
        }
    }

    /** Called by the queue after all retries are exhausted */
    public function failed(\Throwable $e): void
    {
        Log::error('[WA][JobFailed] '.$e->getMessage(), [
            'company_id' => $this->companyId,
            'lead_id'    => $this->leadId,
            'template'   => $this->templateName,
        ]);

        // Best-effort manager notification
        try {
            $this->notifyManager(app(WhatsAppService::class), 'send_failed: '.$e->getMessage());
        } catch (\Throwable $e2) {
            Log::error('[WA][ManagerAlertFail] '.$e2->getMessage(), [
                'company_id' => $this->companyId,
                'lead_id'    => $this->leadId,
            ]);
        }
    }

    protected function notifyManager(WhatsAppService $wa, string $reason): void
    {
        $manager = $this->resolveManagerNumber();
        if (!$manager) {
            Log::warning('[WA] manager_number missing', [
                'company_id' => $this->companyId,
                'lead_id'    => $this->leadId,
                'reason'     => $reason
            ]);
            return;
        }

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
            // KV preferred
            $kv = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->whereIn('key', ['twilio.whatsapp_from', 'twilio_whatsapp_from'])
                ->value('value');

            if ($kv) return $kv;

            // Column fallback (only if you have such a column)
            return DB::table('company_settings')
                ->where('company_id', $companyId)
                ->value('twilio_whatsapp_from');
        } catch (\Throwable $e) {
            Log::error('[WA] getCompanyFromNumber failed: '.$e->getMessage(), ['company_id' => $companyId]);
            return null;
        }
    }
}
