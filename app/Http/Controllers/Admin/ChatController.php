<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Inbox: list conversations, no active thread selected.
     */
    public function index(Request $request)
    {
        $user      = $request->user();
        $companyId = (int) $user->company_id;

        $q        = trim((string) $request->get('q', ''));
        $status   = $request->get('status');  // open / closed / all
        $channel  = $request->get('channel'); // whatsapp / email (future)
        $perPage  = 25;

        $conversations = Conversation::query()
            ->where('company_id', $companyId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('subject', 'like', "%{$q}%")
                       ->orWhere('customer_name', 'like', "%{$q}%")
                       ->orWhere('customer_phone', 'like', "%{$q}%");
                });
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($channel, function ($query) use ($channel) {
                $query->where('channel', $channel);
            })
            ->orderByDesc('last_message_at')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.chat.index', [
            'conversations'        => $conversations,
            'activeConversation'   => null,
            'activeConversationId' => null,
            'initialMessagesJson'  => json_encode([]),
        ]);
    }

    /**
     * Inbox + selected conversation.
     */
    public function show(Request $request, Conversation $chat)
    {
        $user = $request->user();
        $this->authorize('view', $chat);

        $companyId = (int) $user->company_id;

        $q       = trim((string) $request->get('q', ''));
        $status  = $request->get('status');
        $channel = $request->get('channel');
        $perPage = 25;

        $conversations = Conversation::query()
            ->where('company_id', $companyId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('subject', 'like', "%{$q}%")
                       ->orWhere('customer_name', 'like', "%{$q}%")
                       ->orWhere('customer_phone', 'like', "%{$q}%");
                });
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($channel, function ($query) use ($channel) {
                $query->where('channel', $channel);
            })
            ->orderByDesc('last_message_at')
            ->paginate($perPage)
            ->appends($request->query());

        // Last 100 messages
        $messages = MessageLog::query()
            ->where('conversation_id', $chat->id)
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (MessageLog $m) => $m->toChatPayload())
            ->values()
            ->all();

        return view('admin.chat.index', [
            'conversations'        => $conversations,
            'activeConversation'   => $chat,
            'activeConversationId' => $chat->id,
            'initialMessagesJson'  => json_encode($messages),
        ]);
    }

    /**
     * JSON: messages for polling / refresh.
     */
    public function messages(Request $request, Conversation $chat)
    {
        $user = $request->user();
        $this->authorize('view', $chat);

        $sinceId = (int) $request->query('since_id', 0);

        $messages = MessageLog::query()
            ->where('conversation_id', $chat->id)
            ->when($sinceId > 0, fn ($q) => $q->where('id', '>', $sinceId))
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(fn (MessageLog $m) => $m->toChatPayload())
            ->values()
            ->all();

        return response()->json([
            'ok'       => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a human message from admin → customer (WhatsApp for now).
     * NOTE: Expects { message: "text" } from React.
     */
    public function send(Request $request, Conversation $chat, WhatsAppService $whatsApp)
    {
        $user = $request->user();
        $this->authorize('reply', $chat);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $body = trim($data['message']);

        if ($body === '') {
            return response()->json(['ok' => false, 'error' => 'Empty message'], 422);
        }

        $to   = $chat->customer_phone; // assumed E164
        $from = null;                  // pulled by WhatsAppService from settings

        // Log locally first
        $log = MessageLog::create([
            'company_id'      => $chat->company_id,
            'conversation_id' => $chat->id,
            'lead_id'         => $chat->lead_id ?? null,
            'user_id'         => $user->id,
            'direction'       => 'out',
            'channel'         => 'whatsapp',
            'source'          => 'human',
            'from'            => $from,
            'to'              => $to,
            'body'            => $body,
            'is_ai'           => false,
        ]);

        // Fire to WhatsApp (Twilio / Meta chosen by WhatsAppService)
        $waResp = $whatsApp->sendText($to, $body, [
            'company_id'      => $chat->company_id,
            'source'          => 'admin_chat',
            'conversation_id' => $chat->id,
        ]);

        // Update conversation summary
        $chat->fill([
            'last_message_at'      => now(),
            'last_message_preview' => mb_substr($body, 0, 180),
        ])->save();

        return response()->json([
            'ok'      => true,
            'message' => $log->toChatPayload(),
            'wa'      => $waResp,
        ]);
    }
}
