<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function messages(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $sinceId = (int) $request->query('since_id', 0);

        $messages = MessageLog::query()
            ->where('conversation_id', $conversation->id)
            ->when($sinceId > 0, fn($q) => $q->where('id', '>', $sinceId))
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(fn($m) => $m->toChatPayload())
            ->values()
            ->all();

        return response()->json([
            'ok'       => true,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $this->authorize('reply', $conversation);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $body = trim($data['message']);

        $log = MessageLog::create([
            'company_id'      => $conversation->company_id,
            'conversation_id' => $conversation->id,
            'lead_id'         => $conversation->lead_id,
            'direction'       => 'out',
            'channel'         => 'whatsapp',
            'source'          => 'human',
            'body'            => $body,
        ]);

        // Update preview
        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => mb_substr($body, 0, 180),
        ]);

        return response()->json([
            'ok'      => true,
            'message' => $log->toChatPayload(),
        ]);
    }

    /**
     * NEW — mark all inbound unread messages as read
     */
    public function markRead(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        MessageLog::where('conversation_id', $conversation->id)
            ->where('direction', 'in')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->update(['unread_count' => 0]);

        return response()->json(['ok' => true]);
    }
}
