<?php

namespace App\Services\Ai;

use App\Models\MessageLog;
use Illuminate\Support\Facades\DB;

class AiFollowupGenerator
{
    public static function generate(MessageLog $message, string $text)
    {
        return DB::table('ai_suggestions')->insertGetId([
            'message_log_id' => $message->id,
            'suggestion_text'=> $text,
            'confidence'     => null,
            'chosen'         => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }
}
