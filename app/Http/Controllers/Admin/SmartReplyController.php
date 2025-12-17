<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\AiSuggestion;
use App\Services\Ai\NlpService;
use Illuminate\Http\Request;

class SmartReplyController extends Controller
{
    public function suggest(Request $request, Conversation $conversation, NlpService $ai)
    {
        $this->authorize('view', $conversation);

        $lastMessage = $conversation->messages()
            ->orderByDesc('id')
            ->first();

        if (!$lastMessage) {
            return response()->json([
                'ok' => true,
                'suggestions' => [],
            ]);
        }

        $raw = $ai->generateSmartReply($conversation, $lastMessage);

        $suggestions = collect($raw)
            ->take(5)
            ->map(function ($item) use ($conversation, $lastMessage) {
                AiSuggestion::create([
                    'message_log_id'  => $lastMessage->id,
                    'suggestion_text' => $item['text'],
                    'confidence'      => $item['confidence'] ?? 0.75,
                    'chosen'          => false,
                ]);

                return [
                    'text'       => $item['text'],
                    'confidence' => $item['confidence'] ?? 0.75,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'suggestions' => $suggestions,
        ]);
    }
}
