<?php

namespace App\Services\Ai;

use App\Models\MessageLog;
use Illuminate\Support\Facades\DB;

class AiReplyGenerator
{
    public static function generate(
        MessageLog $message,
        string $replyText,
        float $confidence
    ): int {
        return DB::table('ai_suggestions')->insertGetId([
            'company_id'     => $message->company_id,
            'message_log_id' => $message->id,
            'conversation_id'=> $message->conversation_id,
            'suggestion_text'=> $replyText,
            'confidence'     => $confidence,
            'status'         => 'pending',
            'chosen'         => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public static function createSuggestion(
        MessageLog $message,
        string $replyText,
        float $confidence
    ): int {
        return self::generate($message, $replyText, $confidence);
    }
}