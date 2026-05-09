<?php

namespace App\Jobs;

use App\Models\Client\Lead;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Services\WhatsApp\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendWhatsAppFromTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    protected const ACTIONS = [
        'initial',
        'start',
        'follow_up',
        'collect',
        'collect_vehicle',
        'collect_timeslot',
        'collect_general_enquiry',
        'retry',
        'retry_vehicle',
        'retry_timeslot',
        'change_timeslot',
        'confirm_booking',
        'confirmed',
        'reminder',
        'feedback',
        'handoff_manager',
        'booking_handoff',
        'booking_already_created',
        'manager_attention',
        'daily_report',
        'fallback',
        'acknowledge',
        'manual_reply',

        // Manual lead journey
        'manual_lead_welcome',
        'manual_lead_follow_up',
        'manual_lead_booking_push',
    ];

    protected const FOLLOWUP_TEMPLATES = [
        'ask_make_model_v1',
        'ask_intent_v1',
        'manual_lead_welcome_v1',
        'manual_lead_booking_push_v1',
    ];

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
        $this->action = in_array($action, self::ACTIONS, true) ? $action : 'initial';

        $this->toNumberE164 = $this->normalizeNumber($this->toNumberE164);

        $this->onConnection('database');
        $this->onQueue('whatsapp');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("wa-send-{$this->companyId}-{$this->leadId}-{$this->templateName}-{$this->action}"))
                ->expireAfter(120),

            new RateLimited('wa-sends'),
        ];
    }

    public function handle(WhatsAppService $wa): void
    {
        $lead = Lead::where('company_id', $this->companyId)
            ->find($this->leadId);

        if (! $lead) {
            Log::warning('[WA] Lead missing or company mismatch, skipping send', [
                'company_id' => $this->companyId,
                'lead_id' => $this->leadId,
            ]);

            return;
        }

        if (! $this->toNumberE164) {
            Log::warning('[WA] Missing recipient number, skipping send', [
                'company_id' => $this->companyId,
                'lead_id' => $this->leadId,
            ]);

            return;
        }

        $conversation = $this->resolveOrCreateConversation($lead);
        $conversationId = $conversation?->id;

        $ctx = array_merge($this->context, [
            'company_id'      => $this->companyId,
            'lead_id'         => $this->leadId,
            'conversation_id' => $conversationId,
            'action'          => $this->action,
        ]);

        if ($this->action === 'follow_up') {
            $since = ! empty($this->context['since'])
                ? Carbon::parse($this->context['since'])
                : now()->subMinutes(15);

            if ($this->hasRecentInbound($this->leadId, $this->toNumberE164, $since)) {
                Log::info('[WA][FollowUp] skipped inbound already received', [
                    'company_id' => $this->companyId,
                    'lead_id' => $this->leadId,
                ]);

                return;
            }
        }

        if ($this->isDuplicateRecently(
            leadId: $this->leadId,
            template: $this->templateName,
            action: $this->action,
            placeholders: $this->placeholders,
            minutes: 2
        )) {
            Log::info('[WA][Send] skipped duplicate', [
                'company_id' => $this->companyId,
                'lead_id'  => $this->leadId,
                'template' => $this->templateName,
                'action'   => $this->action,
            ]);

            return;
        }

        $from = $this->getCompanyFromNumber($this->companyId);

        /*
        |--------------------------------------------------------------------------
        | Force Template Mode
        |--------------------------------------------------------------------------
        | Manager notifications, daily reports, and manual campaign initiations
        | may need template mode because the recipient may not have an open
        | WhatsApp 24-hour service window.
        |--------------------------------------------------------------------------
        */
        $forceTemplate = (bool) ($this->context['force_template'] ?? false);

        $sessionOpen = $forceTemplate
            ? false
            : $this->isSessionOpen($this->leadId);

        $text = '';
        $resArr = [];
        $sendMode = $sessionOpen ? 'session' : 'template';
        $providerSendCompleted = false;
        $localLogCompleted = false;

        try {
            /*
            |--------------------------------------------------------------------------
            | Manual Manager Reply
            |--------------------------------------------------------------------------
            | Manual replies are free-text replies from the manager.
            | They must not be assembled as template text.
            |--------------------------------------------------------------------------
            */
            if ($this->action === 'manual_reply' || $this->templateName === 'manual_reply') {
                $text = trim((string) ($this->placeholders[0] ?? ''));

                if ($text === '') {
                    Log::warning('[WA][ManualReply] Empty manual reply skipped', [
                        'company_id' => $this->companyId,
                        'lead_id' => $this->leadId,
                    ]);

                    return;
                }

                if (! $sessionOpen) {
                    Log::warning('[WA][ManualReply] Session closed, manual reply skipped', [
                        'company_id' => $this->companyId,
                        'lead_id' => $this->leadId,
                        'to' => $this->toNumberE164,
                    ]);

                    return;
                }

                $sendMode = 'session';

                Log::info('[WA][ManualReply] Sending manager session reply', [
                    'company_id' => $this->companyId,
                    'lead_id' => $this->leadId,
                    'to' => $this->toNumberE164,
                ]);

                $res = $wa->sendText(
                    $this->toNumberE164,
                    $text,
                    $ctx
                );

                $providerSendCompleted = true;
                $resArr = $this->normalizeProviderResponse($res);
            } else {
                $text = $wa->assembleTemplateAsText(
                    $this->templateName,
                    $this->placeholders,
                    $this->links
                );

                if ($sessionOpen) {
                    Log::info('[WA] Sending session message', [
                        'company_id' => $this->companyId,
                        'lead_id'  => $this->leadId,
                        'template' => $this->templateName,
                        'action'   => $this->action,
                        'to'       => $this->toNumberE164,
                    ]);

                    $res = $wa->sendText(
                        $this->toNumberE164,
                        $text,
                        $ctx
                    );

                    $providerSendCompleted = true;
                    $resArr = $this->normalizeProviderResponse($res);
                } else {
                    Log::info('[WA] Sending template message', [
                        'company_id'     => $this->companyId,
                        'lead_id'        => $this->leadId,
                        'template'       => $this->templateName,
                        'action'         => $this->action,
                        'to'             => $this->toNumberE164,
                        'force_template' => $forceTemplate,
                    ]);

                    $res = $wa->sendTemplate(
                        toE164: $this->toNumberE164,
                        templateName: $this->templateName,
                        params: $this->placeholders,
                        links: $this->links,
                        context: $ctx
                    );

                    $providerSendCompleted = true;
                    $resArr = $this->normalizeProviderResponse($res);
                }
            }
        } catch (\Throwable $e) {
            Log::error('[WA][Send] provider exception ' . $e->getMessage(), [
                'company_id' => $this->companyId,
                'lead_id'  => $this->leadId,
                'template' => $this->templateName,
                'action'   => $this->action,
                'mode'     => $sendMode,
                'to'       => $this->toNumberE164,
            ]);

            throw $e;
        }

        /*
        |--------------------------------------------------------------------------
        | Local Message Logging
        |--------------------------------------------------------------------------
        | If provider send succeeded, do not throw after this point.
        |--------------------------------------------------------------------------
        */
        try {
            MessageLog::out([
                'company_id'      => $this->companyId,
                'lead_id'         => $this->leadId,
                'conversation_id' => $conversationId,
                'channel'         => 'whatsapp',
                'direction'       => 'out',
                'to_number'       => $this->toNumberE164,
                'from_number'     => $from ?: null,
                'template'        => $this->templateName,
                'body'            => $text,
                'provider_message_id' => $resArr['sid'] ?? ($resArr['id'] ?? ($resArr['message_id'] ?? null)),
                'provider_status'     => $resArr['status'] ?? 'queued',
                'source'              => $this->action === 'manual_reply' ? 'human' : 'bot',
                'meta' => array_merge($ctx, [
                    'send_mode' => $sendMode,
                    'force_template' => $forceTemplate,
                    'placeholders_hash' => $this->hashArray($this->placeholders),
                    'provider_send_completed' => $providerSendCompleted,
                ], $resArr),
            ]);

            $localLogCompleted = true;
        } catch (\Throwable $e) {
            Log::error('[WA][Send] local log failed after provider send - NOT retrying to avoid duplicate ' . $e->getMessage(), [
                'company_id' => $this->companyId,
                'lead_id'  => $this->leadId,
                'template' => $this->templateName,
                'action'   => $this->action,
                'mode'     => $sendMode,
                'provider_response' => $resArr,
            ]);

            return;
        }

        if ($localLogCompleted) {
            $this->updateConversationAfterSend($conversation, $text);
        }

        if (in_array($this->action, ['handoff_manager', 'booking_handoff', 'manager_attention'], true)) {
            $this->markConversationRequiresAttention($conversation);
        }

        /*
        |--------------------------------------------------------------------------
        | Auto Follow-up
        |--------------------------------------------------------------------------
        | For selected templates, schedule a follow-up if the customer has not
        | replied. This now includes manual lead booking initiation templates.
        |--------------------------------------------------------------------------
        */
        if (
            in_array($this->action, ['initial', 'manual_lead_welcome', 'manual_lead_booking_push'], true)
            && in_array($this->templateName, self::FOLLOWUP_TEMPLATES, true)
        ) {
            $followups = DB::table('message_logs')
                ->where('company_id', $this->companyId)
                ->where('lead_id', $this->leadId)
                ->where('template', $this->templateName)
                ->where('direction', 'out')
                ->where('channel', 'whatsapp')
                ->where(function ($q) {
                    $q->where('meta->action', 'follow_up')
                        ->orWhere('meta->action', 'initial')
                        ->orWhere('meta->action', 'manual_lead_welcome')
                        ->orWhere('meta->action', 'manual_lead_booking_push');
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            if ($followups < 2) {
                self::dispatch(
                    companyId: $this->companyId,
                    leadId: $this->leadId,
                    toNumberE164: $this->toNumberE164,
                    templateName: 'manual_lead_follow_up_v1',
                    placeholders: $this->placeholders,
                    links: $this->links,
                    context: array_merge($ctx, [
                        'since' => now()->toDateTimeString(),
                    ]),
                    action: 'follow_up'
                )->delay(now()->addMinutes(10));
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[WA][JobFailed] ' . $e->getMessage(), [
            'company_id' => $this->companyId,
            'lead_id'  => $this->leadId,
            'template' => $this->templateName,
            'action'   => $this->action,
        ]);
    }

    protected function resolveOrCreateConversation(Lead $lead): ?Conversation
    {
        $phone = $lead->phone ?: $this->toNumberE164;
        $digits = preg_replace('/\D+/', '', (string) $phone);

        $conversation = Conversation::where('company_id', $this->companyId)
            ->where(function ($q) use ($lead, $phone, $digits) {
                $q->where('lead_id', $lead->id);

                if ($digits) {
                    $q->orWhere('customer_phone', 'like', "%{$digits}%");
                }

                if ($phone) {
                    $q->orWhere('customer_phone', $phone);
                }
            })
            ->latest('id')
            ->first();

        if ($conversation) {
            if (! $conversation->lead_id || (int) $conversation->lead_id !== (int) $lead->id) {
                $conversation->update([
                    'lead_id' => $lead->id,
                    'client_id' => $lead->client_id ?: $conversation->client_id,
                    'customer_name' => $lead->name ?: $conversation->customer_name,
                    'customer_phone' => $phone ?: $conversation->customer_phone,
                ]);
            }

            return $conversation;
        }

        try {
            return Conversation::create([
                'company_id' => $this->companyId,
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'customer_name' => $lead->name ?: 'WhatsApp Lead',
                'customer_phone' => $phone,
                'last_message_at' => now(),
                'last_message_preview' => null,
                'unread_count' => 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WA] Conversation create failed', [
                'company_id' => $this->companyId,
                'lead_id' => $lead->id,
                'err' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function updateConversationAfterSend(?Conversation $conversation, string $text): void
    {
        if (! $conversation) {
            return;
        }

        try {
            $conversation->update([
                'last_message_at' => now(),
                'last_message_preview' => mb_substr($text, 0, 120),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[WA] Conversation update failed', [
                'company_id' => $this->companyId,
                'conversation_id' => $conversation->id,
                'err' => $e->getMessage(),
            ]);
        }
    }

    protected function markConversationRequiresAttention(?Conversation $conversation): void
    {
        if (! $conversation) {
            return;
        }

        try {
            $updates = [
                'last_message_at' => now(),
            ];

            if (Schema::hasColumn('conversations', 'status')) {
                $updates['status'] = 'requires_attention';
            }

            if (Schema::hasColumn('conversations', 'requires_attention')) {
                $updates['requires_attention'] = true;
            }

            $conversation->update($updates);
        } catch (\Throwable $e) {
            Log::warning('[WA] Conversation attention update failed', [
                'company_id' => $this->companyId,
                'conversation_id' => $conversation->id,
                'err' => $e->getMessage(),
            ]);
        }
    }

    protected function normalizeProviderResponse(mixed $res): array
    {
        if (is_array($res)) {
            return $res;
        }

        if (is_object($res)) {
            return json_decode(json_encode($res), true) ?? [];
        }

        if (is_string($res)) {
            return ['id' => $res];
        }

        if ($res === true) {
            return ['ok' => true];
        }

        if ($res === false) {
            return ['ok' => false];
        }

        return [];
    }

    protected function isDuplicateRecently(
        int $leadId,
        string $template,
        ?string $action,
        array $placeholders = [],
        int $minutes = 2
    ): bool {
        return DB::table('message_logs')
            ->where('company_id', $this->companyId)
            ->where('lead_id', $leadId)
            ->where('direction', 'out')
            ->where('channel', 'whatsapp')
            ->where('template', $template)
            ->where('meta->action', $action)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->where(function ($q) use ($placeholders) {
                $q->where('meta->placeholders_hash', $this->hashArray($placeholders))
                    ->orWhereNull('meta->placeholders_hash');
            })
            ->exists();
    }

    protected function hasRecentInbound(
        int $leadId,
        string $toNumber,
        \DateTimeInterface $since
    ): bool {
        $digits = preg_replace('/\D+/', '', $toNumber);

        return DB::table('message_logs')
            ->where('company_id', $this->companyId)
            ->where('direction', 'in')
            ->where(function ($q) use ($leadId, $digits) {
                $q->where('lead_id', $leadId);

                if ($digits) {
                    $q->orWhere('from_number', 'like', "%{$digits}%");
                }
            })
            ->where('created_at', '>', $since)
            ->exists();
    }

    protected function isSessionOpen(int $leadId): bool
    {
        return DB::table('message_logs')
            ->where('company_id', $this->companyId)
            ->where('lead_id', $leadId)
            ->where('direction', 'in')
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();
    }

    protected function hashArray(array $value): string
    {
        return sha1(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function normalizeNumber(?string $number): string
    {
        $number = trim((string) $number);
        $number = preg_replace('/^whatsapp:/i', '', $number);
        $number = preg_replace('/\D+/', '', $number);

        if (str_starts_with($number, '05')) {
            $number = '971' . substr($number, 1);
        }

        if (str_starts_with($number, '9710')) {
            $number = '971' . substr($number, 3);
        }

        return $number;
    }

    private function getCompanyFromNumber(int $companyId): ?string
    {
        return DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', [
                'twilio.whatsapp_from',
                'twilio_whatsapp_from',
                'meta.whatsapp_from',
                'meta_whatsapp_from',
                'whatsapp.from',
                'whatsapp_from',
            ])
            ->value('value');
    }
}