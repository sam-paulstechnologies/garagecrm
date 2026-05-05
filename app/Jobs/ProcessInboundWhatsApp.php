<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Services\Ai\NlpService;
use App\Services\Conversation\ConversationEngine;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\MessageLogger;
use App\Services\Conversation\ConversationGuard;
use App\Services\Leads\LeadResolver;
use App\Services\Leads\LeadConversionService;
use App\Notifications\ManagerLeadHandoffNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessInboundWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 20, 60];

    public function __construct(
        public string $from,
        public string $to,
        public string $body,
        public ?string $sid = null,
        public int $numMedia = 0,
        public ?string $profileName = null,
        public string $provider = 'twilio',
        public array $payload = [],
        public ?int $companyId = null
    ) {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('wa-inbound-' . $this->companyId . '-' . $this->from))->expireAfter(60),
        ];
    }

    public function handle(): void
    {
        $companyId = (int) ($this->companyId ?? 0);

        if (!$companyId) {
            Log::warning('[WA] No company resolved');
            return;
        }

        $fromE164 = $this->normalizeWhatsAppNumber($this->from);
        $toE164   = $this->normalizeWhatsAppNumber($this->to);
        $digits   = preg_replace('/\D+/', '', $fromE164);

        $text = trim((string) $this->body);
        $hasMedia = $this->numMedia > 0;

        if ($text === '' && !$hasMedia) {
            Log::info('[WA] Empty inbound ignored', [
                'company_id' => $companyId,
                'from'       => $fromE164,
                'sid'        => $this->sid,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate provider message protection
        |--------------------------------------------------------------------------
        */

        if ($this->sid && MessageLog::where('company_id', $companyId)->where('provider_message_id', $this->sid)->exists()) {
            Log::info('[WA] Duplicate SID ignored', [
                'company_id' => $companyId,
                'sid' => $this->sid,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve / create lead
        |--------------------------------------------------------------------------
        */

        $lead = app(LeadResolver::class)->resolve([
            'phone'           => $fromE164,
            'phone_norm'      => $digits,
            'name'            => $this->profileName ?: 'WhatsApp Lead',
            'source'          => 'whatsapp',
            'external_source' => 'whatsapp',
        ], $companyId);

        if (!$lead) {
            Log::warning('[WA] Lead resolve failed', [
                'company_id' => $companyId,
                'from'       => $fromE164,
            ]);

            return;
        }

        if ((int) $lead->company_id !== $companyId) {
            Log::warning('[WA] Lead company mismatch', [
                'company_id' => $companyId,
                'lead_id'    => $lead->id,
                'lead_company_id' => $lead->company_id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure client/opportunity
        |--------------------------------------------------------------------------
        */

        try {
            app(LeadConversionService::class)->ensureClientAndOpportunity($lead->id, $companyId);
            $lead->refresh();
        } catch (\Throwable $e) {
            Log::warning('[WA] Conversion failed', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve conversation
        |--------------------------------------------------------------------------
        */

        $conversation = app(ConversationService::class)->resolve($companyId, $lead);
        $conversationId = $conversation?->id;

        /*
        |--------------------------------------------------------------------------
        | NLP - optional only. Bot must work even if OpenAI gives 429.
        |--------------------------------------------------------------------------
        */

        $nlp = [
            'intent' => 'fallback',
            'confidence' => 0,
        ];

        if ($text !== '') {
            try {
                $nlp = app(NlpService::class)->analyze($text);
            } catch (\Throwable $e) {
                Log::warning('[NlpService] Chat failed ' . $e->getMessage());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Log inbound
        |--------------------------------------------------------------------------
        */

        app(MessageLogger::class)->logInbound([
            'company_id'      => $companyId,
            'lead_id'         => $lead->id,
            'conversation_id' => $conversationId,
            'to'              => $toE164,
            'from'            => $fromE164,
            'body'            => $text !== '' ? $text : '[Media]',
            'provider_message_id' => $this->sid,
            'meta' => array_merge($this->payload, [
                'has_media' => $hasMedia,
                'num_media' => $this->numMedia,
                'provider'  => $this->provider,
            ]),
            'ai_analysis' => $nlp,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Log communication
        |--------------------------------------------------------------------------
        */

        try {
            Communication::create([
                'company_id' => $companyId,
                'client_id'  => $lead->client_id,
                'lead_id'    => $lead->id,
                'type'       => 'whatsapp',
                'content'    => $text !== '' ? $text : '[Media]',
                'communication_date' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[WA] Communication log failed', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Human takeover lock
        |--------------------------------------------------------------------------
        */

        $lead->refresh();

        if ((int) $lead->company_id !== $companyId) {
            Log::warning('[WA] Lead company mismatch after refresh', [
                'company_id' => $companyId,
                'lead_id'    => $lead->id,
                'lead_company_id' => $lead->company_id,
            ]);

            return;
        }

        if ($lead->conversation_state === 'human') {
            Log::info('[WA] Bot skipped — human takeover active', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'conversation_id' => $conversationId,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Run conversation engine
        |--------------------------------------------------------------------------
        */

        if ($text === '' && $hasMedia) {
            $response = app(ConversationGuard::class)
                ->escalateToManager($lead, 'Customer sent media attachment on WhatsApp.');
        } else {
            try {
                $response = app(ConversationEngine::class)->handle($lead, $text, $nlp);
            } catch (\Throwable $e) {
                Log::error('[WA] Engine failed', [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'error'   => $e->getMessage(),
                ]);

                $response = app(ConversationGuard::class)
                    ->escalateToManager($lead, 'System failure: ' . $e->getMessage());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Skip / no response
        |--------------------------------------------------------------------------
        */

        if (($response['action'] ?? null) === 'skip') {
            return;
        }

        $template = $response['template'] ?? null;

        if (!$template) {
            Log::info('[WA] No template returned by engine', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'action'  => $response['action'] ?? null,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Manager notification / human handoff
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | Do not update leads.status here.
        | Manager confirmation belongs to opportunity.stage.
        |--------------------------------------------------------------------------
        */

        if (in_array($response['action'] ?? null, ['handoff_manager', 'booking_handoff'], true)) {
            $lead->update([
                'conversation_state' => 'human',
            ]);

            $this->notifyManagers($companyId, $lead, $fromE164, $response);
        }

        /*
        |--------------------------------------------------------------------------
        | Send WhatsApp response
        |--------------------------------------------------------------------------
        */

        Log::info('[WA] Dispatching outbound template', [
            'company_id' => $companyId,
            'lead_id'  => $lead->id,
            'template' => $template,
            'action'   => $response['action'] ?? 'initial',
        ]);

        SendWhatsAppFromTemplate::dispatch(
            companyId: $companyId,
            leadId: $lead->id,
            toNumberE164: $fromE164,
            templateName: $template,
            placeholders: $response['placeholders'] ?? [],
            context: $response['context'] ?? [],
            action: $response['action'] ?? 'initial'
        );
    }

    protected function notifyManagers(int $companyId, $lead, string $fromE164, array $response): void
    {
        try {
            $reason = $response['context']['reason'] ?? 'User requires assistance';

            $targets = collect();

            if ($lead->assigned_to) {
                $assigned = User::where('company_id', $companyId)
                    ->where('id', $lead->assigned_to)
                    ->first();

                if ($assigned) {
                    $targets->push($assigned);
                }
            }

            if ($targets->isEmpty()) {
                $targets = User::where('company_id', $companyId)
                    ->whereIn('role', ['admin', 'manager'])
                    ->get();
            }

            foreach ($targets->unique('id') as $user) {
                $user->notify(new ManagerLeadHandoffNotification(
                    companyId: $companyId,
                    leadId: $lead->id,
                    name: $lead->name ?? 'Lead',
                    phone: $fromE164,
                    source: 'WhatsApp',
                    reason: $reason
                ));
            }

        } catch (\Throwable $e) {
            Log::error('[WA] Manager notify failed', [
                'company_id' => $companyId,
                'lead_id' => $lead->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    protected function normalizeWhatsAppNumber(?string $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/^whatsapp:/i', '', $value);
        $value = trim($value);

        return $value;
    }
}