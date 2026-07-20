<?php

namespace App\Services\Conversation;

use App\Models\Client\Lead;
use App\Models\MessageLog;
use App\Models\Client\Opportunity;
use App\Services\Leads\LeadConversionService;
use App\Services\WhatsApp\ManagerNotificationService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConversationGuard
{
    public function __construct(
        protected LeadConversionService $leadConversionService,
        protected ManagerNotificationService $managerNotificationService
    ) {}

    public function isDuplicateMessage(Lead $lead, string $text, array $context = []): bool
    {
        $currentMessage = $this->normalizeMessage($text);

        if ($currentMessage === '') {
            return false;
        }

        $currentAt = $this->currentInboundTime($context);
        $windowSeconds = $this->duplicateWindowSeconds();
        $previous = $this->previousInboundMessage($lead, $context, $currentAt);

        if ($previous) {
            $previousMessage = $this->normalizeMessage((string) $previous->body);
            $previousAt = $previous->created_at instanceof CarbonInterface
                ? $previous->created_at
                : Carbon::parse($previous->created_at);
            $elapsedSeconds = $previousAt->diffInSeconds($currentAt, false);

            if (
                $previousMessage !== ''
                && $previousMessage === $currentMessage
                && $elapsedSeconds >= 0
                && $elapsedSeconds <= $windowSeconds
            ) {
                Log::info('[ConversationGuard] Duplicate inbound skipped', [
                    'company_id' => $lead->company_id,
                    'lead_id' => $lead->id,
                    'message' => $currentMessage,
                    'diff_seconds' => $elapsedSeconds,
                    'previous_message_log_id' => $previous->id,
                    'current_message_log_id' => $context['message_log_id'] ?? null,
                    'duplicate_window_seconds' => $windowSeconds,
                ]);

                return true;
            }
        }

        $data = $this->conversationData($lead);
        $data['last_user_message'] = trim($text);
        $data['last_user_message_norm'] = $currentMessage;
        $data['last_user_message_at'] = $currentAt->toIso8601String();

        $lead->conversation_data = $data;
        $lead->conversation_updated_at = now();
        $lead->save();

        return false;
    }

    protected function previousInboundMessage(Lead $lead, array $context, CarbonInterface $currentAt): ?MessageLog
    {
        $query = MessageLog::query()
            ->where('company_id', (int) $lead->company_id)
            ->where('lead_id', (int) $lead->id)
            ->where('direction', 'in')
            ->where('channel', 'whatsapp')
            ->where('created_at', '<=', $currentAt);

        if (! empty($context['message_log_id'])) {
            $query->where('id', '<>', (int) $context['message_log_id']);
        }

        if (! empty($context['provider_message_id'])) {
            $query->where(function ($q) use ($context) {
                $q->whereNull('provider_message_id')
                    ->orWhere('provider_message_id', '<>', (string) $context['provider_message_id']);
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    protected function currentInboundTime(array $context): CarbonInterface
    {
        if (! empty($context['message_logged_at'])) {
            try {
                return Carbon::parse($context['message_logged_at']);
            } catch (\Throwable) {
                // Fall through to now.
            }
        }

        return now();
    }

    protected function duplicateWindowSeconds(): int
    {
        return max(0, (int) config('conversation.duplicate_window_seconds', env('CONVERSATION_DUPLICATE_WINDOW_SECONDS', 10)));
    }

    public function containsProfanity(string $text): bool
    {
        $badWords = [
            'fuck',
            'shit',
            'idiot',
            'bastard',
            'asshole',
            'stupid',
            'nonsense',
            'bloody',
        ];

        $clean = strtolower($text);

        foreach ($badWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $clean)) {
                return true;
            }
        }

        return false;
    }

    public function shouldEscalateAsUrgent(string $text): bool
    {
        $text = strtolower(trim($text));

        if ($text === '') {
            return false;
        }

        $keywords = [
            'urgent',
            'emergency',
            'asap',
            'immediately',
            'right now',
            'breakdown',
            'stuck',
            'stranded',
            'accident',
            'not starting',
            'car stopped',
            'engine stopped',
            'tow',
            'towing',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function escalateToManager(Lead $lead, string $reason): array
    {
        $lead->refresh();

        Log::info('[BOT ESCALATION]', [
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'reason'  => $reason,
        ]);

        $data = $this->conversationData($lead);

        /*
        |--------------------------------------------------------------------------
        | Prevent repeated escalation messages
        |--------------------------------------------------------------------------
        |
        | This is not replacing the DB-level last action lock.
        | It is a local safety lock to avoid repeated customer/manager spam
        | if the same inbound message is processed twice.
        |
        */

        $lockKey = 'manager_escalation:company:' . (int) $lead->company_id . ':lead:' . (int) $lead->id;

        if (! Cache::add($lockKey, true, now()->addMinutes(10))) {
            Log::info('[ConversationGuard] Escalation skipped by cache lock', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'reason' => $reason,
            ]);

            return $this->sessionResponse(
                template: 'manager_handoff_v1',
                action: 'handoff_manager',
                body: $this->managerHandoffBody($lead),
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'event_key' => 'manager.attention_required',
                    'reason' => $reason,
                    'already_escalated' => true,
                    'lock_key' => $lockKey,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent multiple escalation mutations
        |--------------------------------------------------------------------------
        */

        if (($data['is_escalated'] ?? false) === true) {
            return $this->sessionResponse(
                template: 'manager_handoff_v1',
                action: 'handoff_manager',
                body: $this->managerHandoffBody($lead),
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'event_key' => 'manager.attention_required',
                    'reason' => $reason,
                    'already_escalated' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Human lock
        |--------------------------------------------------------------------------
        */

        $this->updateState($lead, 'human', [
            'is_escalated' => true,
            'escalated_at' => now()->toIso8601String(),
            'escalation_reason' => $reason,
            'last_escalation_source' => 'conversation_guard',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ensure client/opportunity exists
        |--------------------------------------------------------------------------
        */

        try {
            $this->leadConversionService->ensureClientAndOpportunity($lead->id, (int) $lead->company_id);
            $lead->refresh();
        } catch (\Throwable $e) {
            Log::error('[ConversationGuard] Escalation conversion failed', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'reason'  => $reason,
                'error'   => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Update opportunity only
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Do NOT save manager_confirmation_pending into leads.status.
        | That value belongs to opportunities.stage only.
        |
        */

        try {
            if ($lead->opportunity) {
                $lead->opportunity->update([
                    'stage' => Opportunity::STAGE_MANAGER_CONFIRMATION_PENDING,
                    'notes' => $this->appendNote(
                        $lead->opportunity->notes,
                        'Escalation: ' . $reason
                    ),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[ConversationGuard] Escalation CRM update failed', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'reason'  => $reason,
                'error'   => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Notify manager via WhatsApp template
        |--------------------------------------------------------------------------
        |
        | This is proactive manager notification.
        | It must remain Meta-template based through ManagerNotificationService.
        |
        */

        try {
            $this->managerNotificationService->notifyForLead(
                lead: $lead,
                reason: $reason,
                preferredAt: null,
                bookingId: null,
                extra: [
                    'source'          => 'conversation_guard',
                    'escalation_type' => 'bot_escalation',
                    'event_key'       => 'manager.attention_required',
                    'last_message'    => $data['last_user_message'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[ConversationGuard] Manager notification failed', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'reason'  => $reason,
                'error'   => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer response
        |--------------------------------------------------------------------------
        |
        | This response is consumed by ProcessInboundWhatsApp.
        | Because this is triggered by inbound customer message, it should be sent
        | as a normal app/session message inside the 24-hour window.
        |
        */

        return $this->sessionResponse(
            template: 'manager_handoff_v1',
            action: 'handoff_manager',
            body: $this->managerHandoffBody($lead),
            placeholders: [$lead->name ?: 'there'],
            context: [
                'event_key' => 'manager.attention_required',
                'reason' => $reason,
            ]
        );
    }

    protected function updateState(Lead $lead, string $state, array $extra = []): void
    {
        $data = $this->conversationData($lead);

        $lead->conversation_state = $state;
        $lead->conversation_data = array_merge($data, $extra, [
            'last_state_at' => now()->toIso8601String(),
        ]);
        $lead->conversation_updated_at = now();

        $lead->save();
    }

    protected function sessionResponse(
        string $template,
        string $action,
        string $body,
        array $placeholders = [],
        array $context = []
    ): array {
        return [
            /*
            |--------------------------------------------------------------------------
            | Important
            |--------------------------------------------------------------------------
            |
            | ConversationGuard is used by ProcessInboundWhatsApp.
            | This is an inbound/session flow, so body/text/message should be sent
            | from the app inside the 24-hour WhatsApp customer service window.
            |
            | template is kept only as a compatibility/logging hint.
            |
            */

            'body'    => $body,
            'text'    => $body,
            'message' => $body,

            'template'      => $template,
            'template_hint' => $template,

            'placeholders' => $placeholders,
            'action'       => $action,

            'context' => array_merge([
                'send_mode'     => 'session_message',
                'template_hint' => $template,
            ], $context),
        ];
    }

    protected function managerHandoffBody(Lead $lead): string
    {
        $name = $lead->name ?: 'there';

        return "Thanks {$name}. I am connecting you with our manager.\n\n"
            . "Someone from the team will assist you shortly.";
    }

    protected function conversationData(Lead $lead): array
    {
        $data = $lead->conversation_data ?? [];

        return is_array($data) ? $data : [];
    }

    protected function normalizeMessage(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string) $text);
    }

    protected function appendNote(?string $existing, string $line): string
    {
        $existing = trim((string) $existing);
        $line = trim($line);

        if ($existing === '') {
            return $line;
        }

        if (str_contains($existing, $line)) {
            return $existing;
        }

        return $existing . "\n" . $line;
    }
}
