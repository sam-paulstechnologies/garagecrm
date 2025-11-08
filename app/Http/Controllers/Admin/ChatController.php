<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\Client\Lead;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /** List conversations (latest first) */
    public function index(Request $request)
    {
        $companyId = (int) ($request->user()->company_id ?? 0);

        $conversations = Conversation::query()
            ->where('company_id', $companyId)
            ->orderByDesc('latest_message_at')
            ->orderByDesc('updated_at')
            ->withCount([
                // unread = inbound + read_at is NULL
                'messages as unread_count' => function ($q) {
                    $q->where('direction', 'in')->whereNull('read_at');
                },
            ])
            ->paginate(20);

        return view('admin.chat.index', [
            'conversations' => $conversations,
        ]);
    }

    /** Show a thread with the last N messages (and mark inbound as read) */
    public function show(Request $request, Conversation $chat)
    {
        $this->authorizeCompany($request, $chat->company_id);

        $messages = MessageLog::query()
            ->where('conversation_id', $chat->id)
            ->orderBy('created_at')
            ->take(200)
            ->get();

        // mark inbound as read (requires read_at column)
        MessageLog::where('conversation_id', $chat->id)
            ->where('direction', 'in')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('admin.chat.show', [
            'conversation' => $chat,
            'messages'     => $messages,
            'csrf'         => csrf_token(),
        ]);
    }

    /** Poll new messages (JSON) */
    public function messages(Request $request, Conversation $chat)
    {
        $this->authorizeCompany($request, $chat->company_id);

        $afterId = (int) $request->query('after_id', 0);

        $rows = MessageLog::query()
            ->where('conversation_id', $chat->id)
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(200)
            ->get([
                'id',
                'direction',
                'source',
                'body',
                'created_at',
                'provider_status',
                'ai_intent',
                'ai_confidence',
            ]);

        return response()->json([
            'ok'       => true,
            'messages' => $rows,
            'now'      => now()->toISOString(),
        ]);
    }

    /** Send a human message from the manager in the thread */
    public function send(Request $request, Conversation $chat)
    {
        $this->authorizeCompany($request, $chat->company_id);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        // Prefer last inbound for destination number
        $lastInbound = MessageLog::query()
            ->where('conversation_id', $chat->id)
            ->where('direction', 'in')
            ->orderByDesc('id')
            ->first();

        $toE164 = $lastInbound?->from_number;
        $leadId = $lastInbound?->lead_id;

        // Fallback via the lead (if any)
        if (!$toE164 && $leadId) {
            $lead   = Lead::find($leadId);
            $toE164 = $lead?->phone;
        }

        if (!$toE164) {
            return response()->json([
                'ok'    => false,
                'error' => 'No recipient number found for this thread',
            ], 422);
        }

        /** @var WhatsAppService $wa */
        $wa = app(WhatsAppService::class);

        // Send WA text
        $wa->sendText($toE164, $data['body'], [
            'company_id'      => $chat->company_id,
            'conversation_id' => $chat->id,
        ]);

        // Log out message for unified inbox
        MessageLog::out([
            'company_id'      => $chat->company_id,
            'lead_id'         => $leadId,
            'conversation_id' => $chat->id,
            'channel'         => 'whatsapp',
            'source'          => 'human',
            'to_number'       => $toE164,
            'from_number'     => $request->user()?->phone ?? null,
            'body'            => $data['body'],
            'provider_status' => 'sent',
        ]);

        // bump conversation latest_message_at
        $chat->update(['latest_message_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** Same-company guard */
    private function authorizeCompany(Request $request, int $resourceCompanyId): void
    {
        $meCompany = (int) ($request->user()->company_id ?? 0);
        abort_if($meCompany !== (int) $resourceCompanyId, 403, 'Forbidden');
    }
}
