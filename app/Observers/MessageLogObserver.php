<?php

namespace App\Observers;

use App\Models\MessageLog;
use App\Services\AI\AiDecisionEngine;
use App\Services\AI\AiReplyGenerator;

class MessageLogObserver
{
    public function created(MessageLog $message): void
    {
        // Only inbound messages
        if (!isset($message->direction) || $message->direction !== 'in') {
            return;
        }

        $companyId = (int) ($message->company_id ?? 0);
        if ($companyId <= 0) return;

        // Must have AI analysis available to decide (your system already stores ai_analysis)
        $analysis = [];
        if (!empty($message->ai_analysis)) {
            $analysis = is_array($message->ai_analysis)
                ? $message->ai_analysis
                : (json_decode($message->ai_analysis, true) ?: []);
        }

        // If no analysis, don’t guess. Safe skip.
        if (empty($analysis)) return;

        $decision = AiDecisionEngine::shouldGenerate($analysis, $companyId);

        if (!$decision['enabled']) return;

        // If handoff intent → we still generate a “manager handoff” suggestion (optional)
        if (!empty($decision['handoff'])) {
            AiReplyGenerator::createSuggestion(
                $message,
                $decision['policy_reply'],
                $decision['confidence'] ?? null
            );
            return;
        }

        // If not allowed → suggest policy reply (don’t block; still human approves)
        if (!$decision['allowed']) {
            AiReplyGenerator::createSuggestion(
                $message,
                $decision['policy_reply'],
                $decision['confidence'] ?? null
            );
            return;
        }

        // ✅ Allowed: Create a placeholder suggestion based on analysis summary (UAT-safe)
        // You can later swap this with LLM-generated text. For now it is deterministic.
        $intent = $decision['intent'] ?: 'general';

        $text = "Thanks for reaching out. I understand this is about {$intent}. "
              . "A manager can assist you shortly, or you can share more details to help us help faster.";

        AiReplyGenerator::createSuggestion(
            $message,
            $text,
            $decision['confidence'] ?? null
        );
    }
}
