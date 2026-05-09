<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboxController extends Controller
{
    public function jsonList(Request $request)
    {
        $companyId = (int) $request->user()->company_id;
        $search = trim((string) $request->query('search', ''));

        $query = Conversation::query()
            ->where('company_id', $companyId)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('last_message_preview', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('last_message_at')
            ->limit(100);

        $items = $query->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'customer_name' => $c->customer_name,
                'customer_phone' => $c->customer_phone,
                'last_message_preview' => $c->last_message_preview,
                'last_message_at' => optional($c->last_message_at)->toIso8601String(),
                'unread_count' => (int) ($c->unread_count ?? 0),
                'lead_id' => $c->lead_id ?? null,
            ];
        });

        return response()->json([
            'ok' => true,
            'conversations' => $items,
        ]);
    }

    public function jsonMessages(Request $request, Conversation $conversation)
    {
        $companyId = (int) $request->user()->company_id;

        abort_if($conversation->company_id !== $companyId, 403);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'direction' => $m->direction,
                    'body' => $m->body,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                    'is_ai' => (bool) $m->is_ai,
                    'source' => $m->source,
                    'provider_status' => $m->provider_status,
                    'template' => $m->template,
                    'read_at' => optional($m->read_at)->toIso8601String(),
                ];
            });

        $context = $this->conversationContext($conversation, $companyId);

        $conversation->markAllRead();

        return response()->json([
            'ok' => true,
            'messages' => $messages,
            'context' => $context,
        ]);
    }

    public function send(Request $request, WhatsAppService $wa)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string|max:4000',
        ]);

        $companyId = (int) $request->user()->company_id;

        $conversation = Conversation::where('id', $request->conversation_id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($conversation->lead) {
            $conversation->lead->update([
                'conversation_state' => 'human',
            ]);
        }

        $wa->sendText(
            $conversation->customer_phone,
            $request->message,
            ['company_id' => $companyId]
        );

        MessageLog::out([
            'company_id' => $companyId,
            'conversation_id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'channel' => 'whatsapp',
            'to_number' => $conversation->customer_phone,
            'from_number' => null,
            'body' => $request->message,
            'source' => 'human',
            'provider_status' => 'queued',
        ]);

        $conversation->update([
            'last_message_preview' => Str::limit($request->message, 120),
            'last_message_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Message sent',
        ]);
    }

    public function suggestReply(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'tone' => 'nullable|string|in:friendly,professional,short,urgent',
        ]);

        $companyId = (int) $request->user()->company_id;
        $tone = $request->input('tone', 'professional');

        $conversation = Conversation::where('id', $request->conversation_id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $recentMessages = MessageLog::where('company_id', $companyId)
            ->where('conversation_id', $conversation->id)
            ->where('channel', 'whatsapp')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $lastInbound = $recentMessages
            ->where('direction', 'in')
            ->last();

        $lastText = strtolower((string) ($lastInbound->body ?? ''));

        $suggestion = $this->basicAiSuggestion($lastText, $tone, $conversation);

        return response()->json([
            'ok' => true,
            'suggestion' => $suggestion,
            'tone' => $tone,
        ]);
    }

    public function markRead(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $companyId = (int) $request->user()->company_id;

        $conversation = Conversation::where('id', $request->conversation_id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $conversation->markAllRead();

        return response()->json([
            'ok' => true,
        ]);
    }

    protected function conversationContext(Conversation $conversation, int $companyId): array
    {
        $lead = $conversation->lead;

        return [
            'lead_id' => $lead?->id,
            'lead_name' => $lead?->name,
            'lead_status' => $lead?->status,
            'conversation_state' => $lead?->conversation_state,
            'phone' => $conversation->customer_phone,
            'name' => $conversation->customer_name,
        ];
    }

    protected function basicAiSuggestion(string $lastText, string $tone, Conversation $conversation): string
    {
        $name = $conversation->customer_name ?: 'there';

        if (str_contains($lastText, 'price') || str_contains($lastText, 'cost') || str_contains($lastText, 'how much')) {
            return match ($tone) {
                'short' => "Hi {$name}, pricing depends on the vehicle and service needed. Please share your car make/model and issue so we can guide you.",
                'urgent' => "Hi {$name}, we can help quickly. Please share your vehicle make/model and the service required so our team can confirm the best price.",
                'friendly' => "Hi {$name}, happy to help 😊 The price depends on your vehicle and the service needed. Could you please share the car make/model and issue?",
                default => "Hi {$name}, thank you for reaching out. Pricing depends on the vehicle make/model and the service required. Please share those details and we’ll guide you with the next steps.",
            };
        }

        if (str_contains($lastText, 'book') || str_contains($lastText, 'appointment') || str_contains($lastText, 'slot')) {
            return match ($tone) {
                'short' => "Hi {$name}, sure. Please share your preferred date and time slot for the booking.",
                'urgent' => "Hi {$name}, we can arrange this. Please send your preferred date/time and vehicle details so we can confirm quickly.",
                'friendly' => "Hi {$name}, sure 😊 Please share your preferred date and time, and we’ll help arrange the booking.",
                default => "Hi {$name}, sure. Please share your preferred date, time, vehicle details, and service requirement so we can confirm the booking.",
            };
        }

        if (str_contains($lastText, 'complaint') || str_contains($lastText, 'bad') || str_contains($lastText, 'not happy') || str_contains($lastText, 'angry')) {
            return match ($tone) {
                'short' => "Hi {$name}, sorry about this. Please share the issue and we’ll escalate it to the team.",
                'urgent' => "Hi {$name}, sorry for the inconvenience. We’re escalating this immediately. Please share the details so we can assist.",
                'friendly' => "Hi {$name}, really sorry to hear this. Please share what happened and we’ll make sure the right person looks into it.",
                default => "Hi {$name}, we’re sorry for the inconvenience. Please share the details of the issue, and we’ll escalate it to the concerned team for immediate review.",
            };
        }

        return match ($tone) {
            'short' => "Hi {$name}, thanks for your message. Please share a few more details so we can assist.",
            'urgent' => "Hi {$name}, thanks for reaching out. Please share the details and our team will assist as soon as possible.",
            'friendly' => "Hi {$name}, thanks for messaging 😊 Could you please share a few more details so we can help you better?",
            default => "Hi {$name}, thank you for your message. Please share a few more details about your requirement, and our team will assist you.",
        };
    }
}