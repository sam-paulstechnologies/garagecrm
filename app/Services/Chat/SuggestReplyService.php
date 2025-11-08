<?php

namespace App\Services\Chat;

use App\Models\AiSuggestion;
use App\Models\MessageLog;
use App\Services\Ai\NlpService;

class SuggestReplyService
{
    public function __construct(protected NlpService $nlp) {}

    /** Generate & save a suggestion for a given inbound message */
    public function generateFor(MessageLog $inbound, array $lead = []): AiSuggestion
    {
        $text = $this->nlp->replyText(
            $inbound->from_number ?? '',
            $inbound->to_number   ?? '',
            $inbound->body        ?? '',
            ['lead' => $lead, 'nlp' => $inbound->ai_analysis ?? []]
        );

        $conf = is_array($inbound->ai_analysis) ? ($inbound->ai_analysis['confidence'] ?? null) : null;

        return AiSuggestion::create([
            'message_log_id' => $inbound->id,
            'suggestion_text'=> $text,
            'confidence'     => $conf,
        ]);
    }
}
