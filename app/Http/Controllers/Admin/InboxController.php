<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function jsonList(Request $request)
    {
        $companyId = (int) $request->user()->company_id;

        $items = Conversation::where('company_id', $companyId)
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'customer_name' => $c->customer_name,
                    'customer_phone' => $c->customer_phone,
                    'last_message_preview' => $c->last_message_preview,
                    'last_message_at' => optional($c->last_message_at)->toIso8601String(),
                    'unread_count' => (int) $c->unread_count,
                ];
            });

        return response()->json([
            'ok' => true,
            'conversations' => $items
        ]);
    }

    public function jsonMessages(Request $request, Conversation $conversation)
    {
        // 🔥 COMPANY SAFE ACCESS
        abort_if(
            $conversation->company_id !== $request->user()->company_id,
            403
        );

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'direction' => $m->direction,
                    'body' => $m->body,
                    'created_at' => $m->created_at->toIso8601String(),
                    'is_ai' => $m->is_ai,
                ];
            });

        $conversation->markAllRead();

        return response()->json([
            'ok' => true,
            'messages' => $messages
        ]);
    }

    public function send(Request $request, WhatsAppService $wa)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string'
        ]);

        $companyId = (int) $request->user()->company_id;

        // 🔥 SAFE FETCH (CRITICAL FIX)
        $conversation = Conversation::where('id', $request->conversation_id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | 🔥 MANAGER TAKEOVER (CRITICAL FIX)
        |--------------------------------------------------------------------------
        */
        if ($conversation->lead) {
            $conversation->lead->update([
                'conversation_state' => 'human' // 🔥 stops bot
            ]);
        }

        // Send WhatsApp
        $wa->sendText(
            $conversation->customer_phone,
            $request->message,
            ['company_id' => $companyId]
        );

        // Log message
        MessageLog::out([
            'company_id' => $companyId,
            'conversation_id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'channel' => 'whatsapp',
            'to_number' => $conversation->customer_phone,
            'from_number' => null,
            'body' => $request->message,
            'source' => 'human',
        ]);

        return response()->json(['ok' => true]);
    }
}