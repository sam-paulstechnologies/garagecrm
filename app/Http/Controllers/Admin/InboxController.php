<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;

        $conversations = Conversation::forCompany($companyId)
            ->orderByDesc('last_message_at')
            ->paginate(30);

        return view('admin.chat.index', [
            'conversations'        => $conversations,
            'activeConversation'   => null,
            'activeConversationId' => null,
            'initialMessagesJson'  => json_encode([]),
        ]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $user = $request->user();
        $companyId = (int) $user->company_id;

        $conversations = Conversation::forCompany($companyId)
            ->orderByDesc('last_message_at')
            ->paginate(30);

        return view('admin.chat.index', [
            'conversations'        => $conversations,
            'activeConversation'   => $conversation,
            'activeConversationId' => $conversation->id,
            'initialMessagesJson'  => json_encode([]),
        ]);
    }

    /**
     * NEW — JSON API for refreshing left panel
     */
    public function jsonList(Request $request)
    {
        $companyId = (int) $request->user()->company_id;

        $items = Conversation::forCompany($companyId)
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
            })
            ->values();

        return response()->json([
            'ok' => true,
            'conversations' => $items
        ]);
    }
}
