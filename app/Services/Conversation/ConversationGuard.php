<?php

namespace App\Services\Conversation;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Services\Leads\LeadConversionService;
use App\Services\WhatsApp\ManagerNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ConversationGuard
{
    public function __construct(
        protected LeadConversionService $leadConversionService,
        protected ManagerNotificationService $managerNotificationService
    ) {}

    public function isDuplicateMessage(Lead $lead, string $text): bool
    {
        $data = $this->conversationData($lead);

        $currentMessage = $this->normalizeMessage($text);
        $lastMessage = $this->normalizeMessage($data['last_user_message'] ?? '');
        $lastTime = $data['last_user_message_at'] ?? null;

        if (
            $currentMessage !== ''
            && $lastMessage !== ''
            && $lastMessage === $currentMessage
            && $lastTime
        ) {
            try {
                $diff = now()->diffInSeconds(Carbon::parse($lastTime));

                if ($diff < 10) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::debug('[ConversationGuard] Failed to parse last message time', [
                    'lead_id' => $lead->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $data['last_user_message'] = trim($text);
        $data['last_user_message_norm'] = $currentMessage;
        $data['last_user_message_at'] = now()->toIso8601String();

        $lead->conversation_data = $data;
        $lead->save();

        return false;
    }

    public function containsProfanity(string $text): bool
    {
        $badWords = [
            'fuck',
            'shit',
            'idiot',
            'bastard',
            'asshole',
        ];

        $clean = strtolower($text);

        foreach ($badWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $clean)) {
                return true;
            }
        }

        return false;
    }

    public function escalateToManager(Lead $lead, string $reason): array
    {
        Log::info('[BOT ESCALATION]', [
            'lead_id' => $lead->id,
            'reason'  => $reason,
        ]);

        $data = $this->conversationData($lead);

        /*
        |--------------------------------------------------------------------------
        | Prevent multiple escalation mutations
        |--------------------------------------------------------------------------
        */
        if (($data['is_escalated'] ?? false) === true) {
            return [
                'template' => 'manager_handoff_v1',
                'placeholders' => [],
                'action' => 'handoff_manager',
                'context' => [
                    'reason' => $reason,
                    'already_escalated' => true,
                ],
            ];
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
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ensure client/opportunity exists
        |--------------------------------------------------------------------------
        */
        try {
            $this->leadConversionService->ensureClientAndOpportunity($lead->id);
            $lead->refresh();
        } catch (\Throwable $e) {
            Log::error('[ConversationGuard] Escalation conversion failed', [
                'lead_id' => $lead->id,
                'reason'  => $reason,
                'error'   => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Update opportunity only
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | Do NOT save manager_confirmation_pending into leads.status.
        | That value belongs to opportunities.stage only.
        |--------------------------------------------------------------------------
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
                'lead_id' => $lead->id,
                'reason'  => $reason,
                'error'   => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Notify manager via WhatsApp template
        |--------------------------------------------------------------------------
        | This uses manager_attention_required_v1 and force_template=true inside
        | ManagerNotificationService, so it works even if the manager has no
        | active 24-hour session.
        |--------------------------------------------------------------------------
        */
        try {
            $this->managerNotificationService->notifyForLead(
                lead: $lead,
                reason: $reason,
                preferredAt: null,
                bookingId: null,
                extra: [
                    'source' => 'conversation_guard',
                    'escalation_type' => 'bot_escalation',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[ConversationGuard] Manager notification failed', [
                'lead_id' => $lead->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'template' => 'manager_handoff_v1',
            'placeholders' => [],
            'action' => 'handoff_manager',
            'context' => [
                'reason' => $reason,
            ],
        ];
    }

    protected function updateState(Lead $lead, string $state, array $extra = []): void
    {
        $data = $this->conversationData($lead);

        $lead->conversation_state = $state;
        $lead->conversation_data = array_merge($data, $extra, [
            'last_state_at' => now()->toIso8601String(),
        ]);

        $lead->save();
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

        return trim($text);
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