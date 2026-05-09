<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Notifications\ManagerLeadHandoffNotification;
use App\Services\Ai\NlpService;
use App\Services\Conversation\ConversationEngine;
use App\Services\Conversation\ConversationGuard;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\MessageLogger;
use App\Services\Feedback\FeedbackResponseService;
use App\Services\Leads\LeadConversionService;
use App\Services\Leads\LeadResolver;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;

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
            (new WithoutOverlapping('wa-inbound-' . $this->companyId . '-' . $this->from))
                ->expireAfter(60),
        ];
    }

    public function handle(): void
    {
        $companyId = (int) ($this->companyId ?? 0);

        if (! $companyId) {
            Log::warning('[WA] No company resolved');
            return;
        }

        $fromE164 = $this->normalizeWhatsAppNumber($this->from);
        $toE164 = $this->normalizeWhatsAppNumber($this->to);
        $digits = preg_replace('/\D+/', '', $fromE164);

        $text = trim((string) $this->body);
        $hasMedia = $this->numMedia > 0;

        if ($text === '' && ! $hasMedia) {
            Log::info('[WA] Empty inbound ignored', [
                'company_id' => $companyId,
                'from' => $fromE164,
                'sid' => $this->sid,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate provider message protection
        |--------------------------------------------------------------------------
        */

        if (
            $this->sid &&
            MessageLog::where('company_id', $companyId)
                ->where('provider_message_id', $this->sid)
                ->exists()
        ) {
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
            'phone' => $fromE164,
            'phone_norm' => $digits,
            'name' => $this->profileName ?: 'WhatsApp Lead',
            'source' => 'whatsapp',
            'external_source' => 'whatsapp',
        ], $companyId);

        if (! $lead) {
            Log::warning('[WA] Lead resolve failed', [
                'company_id' => $companyId,
                'from' => $fromE164,
            ]);

            return;
        }

        if ((int) $lead->company_id !== $companyId) {
            Log::warning('[WA] Lead company mismatch', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
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
                'error' => $e->getMessage(),
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
            'company_id' => $companyId,
            'lead_id' => $lead->id,
            'conversation_id' => $conversationId,
            'to' => $toE164,
            'from' => $fromE164,
            'body' => $text !== '' ? $text : '[Media]',
            'provider_message_id' => $this->sid,
            'meta' => array_merge($this->payload, [
                'has_media' => $hasMedia,
                'num_media' => $this->numMedia,
                'provider' => $this->provider,
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
                'client_id' => $lead->client_id,
                'lead_id' => $lead->id,
                'type' => 'whatsapp',
                'content' => $text !== '' ? $text : '[Media]',
                'communication_date' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[WA] Communication log failed', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Refresh lead before routing decisions
        |--------------------------------------------------------------------------
        */

        $lead->refresh();

        if ((int) $lead->company_id !== $companyId) {
            Log::warning('[WA] Lead company mismatch after refresh', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'lead_company_id' => $lead->company_id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Feedback Reply Handling - BEFORE human takeover check
        |--------------------------------------------------------------------------
        |
        | Important:
        | Feedback replies must be handled even if conversation_state = human.
        |
        | Example:
        | Customer receives job_done_feedback_v1 and taps:
        | 1 / 2 / 3 / 4 / 5
        |
        | If we check human takeover first, the reply is ignored.
        |--------------------------------------------------------------------------
        */

        if ($text !== '') {
            try {
                $handledFeedback = app(FeedbackResponseService::class)
                    ->handleIfFeedbackReply(
                        companyId: $companyId,
                        lead: $lead,
                        text: $text,
                        fromE164: $fromE164,
                        conversationId: $conversationId
                    );

                if ($handledFeedback) {
                    Log::info('[WA] Feedback reply handled before human takeover check', [
                        'company_id' => $companyId,
                        'lead_id' => $lead->id,
                        'conversation_id' => $conversationId,
                        'body' => $text,
                    ]);

                    return;
                }

                Log::info('[WA] Feedback reply not handled, continuing normal flow', [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'conversation_id' => $conversationId,
                    'body' => $text,
                ]);
            } catch (\Throwable $e) {
                Log::error('[WA] Feedback reply handling failed', [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'conversation_id' => $conversationId,
                    'body' => $text,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Human takeover lock
        |--------------------------------------------------------------------------
        */

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
                    'error' => $e->getMessage(),
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

        /*
        |--------------------------------------------------------------------------
        | Manager notification / human handoff
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
        | Send WhatsApp session response
        |--------------------------------------------------------------------------
        */

        $sessionBody = $this->composeSessionBody($response, $lead);

        if (! $sessionBody) {
            Log::info('[WA] No session body generated by engine', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'action' => $response['action'] ?? null,
                'template' => $response['template'] ?? null,
            ]);

            return;
        }

        Log::info('[WA] Sending session response from app', [
            'company_id' => $companyId,
            'lead_id' => $lead->id,
            'conversation_id' => $conversationId,
            'action' => $response['action'] ?? 'reply',
            'template_hint' => $response['template'] ?? null,
        ]);

        $this->sendSessionMessage(
            companyId: $companyId,
            leadId: (int) $lead->id,
            conversationId: $conversationId,
            toNumberE164: $fromE164,
            body: $sessionBody,
            context: array_merge($response['context'] ?? [], [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'conversation_id' => $conversationId,
                'source' => 'process_inbound_whatsapp',
                'provider' => $this->provider,
                'action' => $response['action'] ?? 'reply',
                'template_hint' => $response['template'] ?? null,
                'send_mode' => 'session_message',
            ])
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
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendSessionMessage(
        int $companyId,
        int $leadId,
        ?int $conversationId,
        string $toNumberE164,
        string $body,
        array $context = []
    ): void {
        try {
            /** @var WhatsAppService $whatsapp */
            $whatsapp = app(WhatsAppService::class);

            foreach ([
                'sendText',
                'sendMessage',
                'sendSessionMessage',
                'sendFreeformText',
            ] as $method) {
                if (method_exists($whatsapp, $method)) {
                    $this->callWhatsAppMethod(
                        service: $whatsapp,
                        method: $method,
                        args: [
                            'companyId' => $companyId,
                            'company_id' => $companyId,
                            'leadId' => $leadId,
                            'lead_id' => $leadId,
                            'conversationId' => $conversationId,
                            'conversation_id' => $conversationId,
                            'toNumberE164' => $toNumberE164,
                            'toE164' => $toNumberE164,
                            'to' => $toNumberE164,
                            'phone' => $toNumberE164,
                            'number' => $toNumberE164,
                            'body' => $body,
                            'message' => $body,
                            'text' => $body,
                            'content' => $body,
                            'context' => $context,
                            'meta' => $context,
                        ]
                    );

                    Log::info('[WA] Session message sent', [
                        'company_id' => $companyId,
                        'lead_id' => $leadId,
                        'conversation_id' => $conversationId,
                        'method' => $method,
                    ]);

                    return;
                }
            }

            Log::error('[WA] No session-send method found on WhatsAppService', [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'conversation_id' => $conversationId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WA] Session message send failed', [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function callWhatsAppMethod(object $service, string $method, array $args): mixed
    {
        $reflection = new ReflectionMethod($service, $method);
        $parameters = $reflection->getParameters();

        $orderedArgs = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $args)) {
                $orderedArgs[] = $args[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $orderedArgs[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $orderedArgs[] = null;
                continue;
            }

            throw new \InvalidArgumentException("Cannot resolve WhatsAppService::{$method} parameter: {$name}");
        }

        return $reflection->invokeArgs($service, $orderedArgs);
    }

    protected function composeSessionBody(array $response, $lead): ?string
    {
        foreach (['body', 'message', 'text', 'reply'] as $key) {
            if (! empty($response[$key]) && is_string($response[$key])) {
                return $this->cleanBody($response[$key]);
            }
        }

        $template = (string) ($response['template'] ?? '');
        $action = (string) ($response['action'] ?? '');
        $placeholders = $response['placeholders'] ?? [];

        $name = $lead->name ?: 'there';
        $p0 = $this->placeholder($placeholders, 0, $name);
        $p1 = $this->placeholder($placeholders, 1, '');
        $p2 = $this->placeholder($placeholders, 2, '');

        return match ($template) {
            'ask_intent_v1' => "Hi {$p0}, thanks for reaching out. How can we help you today?\n\nYou can reply with:\n1. Book a service\n2. Ask a question\n3. Talk to manager",

            'lead_conversation_start_v1',
            'follow_up_new_lead_v1' => "Hi {$p0}, thanks for your enquiry. How can we help you today?\n\nYou can reply with:\n1. Book a service\n2. Ask a question\n3. Talk to manager",

            'ask_make_model_v1',
            'follow_up_vehicle_pending_v1' => "Sure {$p0}. Please share your vehicle make and model.\n\nExample: Toyota Camry 2020",

            'ask_preferred_time_v1',
            'follow_up_timeslot_pending_v1' => "Thanks {$p0}. Please share your preferred booking date and time.\n\nExample: Tomorrow morning or Friday 4 PM",

            'booking_request_v1',
            'booking_request_received_v1',
            'follow_up_booking_confirm_v1' => "Thanks {$p0}. Your booking request has been received. Our team will review it and confirm shortly.",

            'booking_confirmed_v1' => $p1
                ? "Your booking is confirmed. Reference: {$p1}"
                : 'Your booking is confirmed. Our team will contact you if anything else is needed.',

            'booking_confirmed_by_manager_v1' => $p1
                ? "Your booking has been confirmed by our manager. Reference: {$p1}"
                : 'Your booking has been confirmed by our manager.',

            'booking_reschedule_required_v1' => 'The selected booking time is not available. Please share another preferred date and time.',

            'ask_general_enquiry_v1',
            'follow_up_general_enquiry_v1' => "Sure {$p0}. Please share your question or requirement, and our team will help you.",

            'gratitude_v1' => "You're welcome {$p0}. Happy to help.",

            'manager_handoff_v1',
            'visit_handoff_v1',
            'manager_attention_required_v1' => 'I am connecting you with our manager. Someone from the team will assist you shortly.',

            'follow_up_pending_response_v1' => "Hi {$p0}, we are following up on your service enquiry. Please reply here if you still need assistance.",

            default => $this->fallbackSessionBody($action, $name, $p1, $p2),
        };
    }

    protected function fallbackSessionBody(string $action, string $name, string $p1 = '', string $p2 = ''): ?string
    {
        return match ($action) {
            'start',
            'retry' => "Hi {$name}, how can we help you today?\n\nYou can reply with:\n1. Book a service\n2. Ask a question\n3. Talk to manager",

            'collect_vehicle',
            'retry_vehicle' => "Please share your vehicle make and model.\n\nExample: Toyota Camry 2020",

            'collect_timeslot',
            'retry_timeslot',
            'change_timeslot' => "Please share your preferred booking date and time.\n\nExample: Tomorrow morning or Friday 4 PM",

            'collect_general_enquiry' => 'Please share your question or requirement, and our team will help you.',

            'confirm_booking',
            'confirmed' => $p1
                ? "Your booking is confirmed. Reference: {$p1}"
                : 'Your booking is confirmed.',

            'handoff_manager',
            'booking_handoff' => 'I am connecting you with our manager. Someone from the team will assist you shortly.',

            'gratitude' => "You're welcome {$name}. Happy to help.",

            default => null,
        };
    }

    protected function placeholder(array $placeholders, int $index, string $fallback = ''): string
    {
        $value = $placeholders[$index] ?? $fallback;

        if (is_array($value) || is_object($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    protected function cleanBody(string $body): string
    {
        $body = trim($body);

        return mb_substr($body, 0, 4000);
    }

    protected function normalizeWhatsAppNumber(?string $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/^whatsapp:/i', '', $value);
        $value = trim($value);

        return $value;
    }
}