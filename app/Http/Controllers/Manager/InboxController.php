<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Manager/Inbox/Index', [
            'selectedConversationId' => $request->query('conversation'),
        ]);
    }

    public function jsonList(Request $request)
    {
        $companyId = $this->companyId($request);
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

        $items = $query->get()->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'customer_name' => $conversation->customer_name,
                'customer_phone' => $conversation->customer_phone,
                'last_message_preview' => $conversation->last_message_preview,
                'last_message_at' => optional($conversation->last_message_at)->toIso8601String(),
                'unread_count' => (int) ($conversation->unread_count ?? 0),
                'lead_id' => $conversation->lead_id ?? null,
            ];
        });

        return response()->json([
            'ok' => true,
            'conversations' => $items,
        ]);
    }

    public function jsonMessages(Request $request, Conversation $conversation)
    {
        $companyId = $this->companyId($request);

        abort_if((int) $conversation->company_id !== $companyId, 403);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'direction' => $message->direction,
                    'body' => $message->body,
                    'created_at' => optional($message->created_at)->toIso8601String(),
                    'is_ai' => (bool) ($message->is_ai ?? false),
                    'source' => $message->source,
                    'provider_status' => $message->provider_status,
                    'template' => $message->template,
                    'read_at' => optional($message->read_at)->toIso8601String(),
                ];
            });

        $context = $this->conversationContext($conversation);

        if (method_exists($conversation, 'markAllRead')) {
            $conversation->markAllRead();
        } else {
            $conversation->update([
                'unread_count' => 0,
            ]);
        }

        return response()->json([
            'ok' => true,
            'messages' => $messages,
            'context' => $context,
        ]);
    }

    public function send(Request $request, WhatsAppService $wa)
    {
        $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $companyId = $this->companyId($request);

        $conversation = Conversation::query()
            ->where('id', $request->conversation_id)
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
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'tone' => ['nullable', 'string', 'in:friendly,professional,short,urgent'],
        ]);

        $companyId = $this->companyId($request);
        $tone = $request->input('tone', 'professional');

        $conversation = Conversation::query()
            ->where('id', $request->conversation_id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $recentMessages = MessageLog::query()
            ->where('company_id', $companyId)
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
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
        ]);

        $companyId = $this->companyId($request);

        $conversation = Conversation::query()
            ->where('id', $request->conversation_id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (method_exists($conversation, 'markAllRead')) {
            $conversation->markAllRead();
        } else {
            $conversation->update([
                'unread_count' => 0,
            ]);
        }

        return response()->json([
            'ok' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Backward Compatible Old Lead-Based Methods
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, Lead $lead)
    {
        $companyId = $this->companyId($request);

        abort_if((int) $lead->company_id !== $companyId, 403);

        $conversation = Conversation::query()
            ->where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            return redirect()
                ->route('manager.inbox.index')
                ->with('error', 'No conversation found for this lead yet.');
        }

        return redirect()
            ->route('manager.inbox.index', ['conversation' => $conversation->id]);
    }

    public function reply(Request $request, Lead $lead, WhatsAppService $wa)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $companyId = $this->companyId($request);

        abort_if((int) $lead->company_id !== $companyId, 403);

        $conversation = Conversation::query()
            ->where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->latest('last_message_at')
            ->first();

        if (! $conversation) {
            return back()->with('error', 'No conversation found for this lead yet.');
        }

        $lead->update([
            'conversation_state' => 'human',
        ]);

        $wa->sendText(
            $conversation->customer_phone,
            $request->message,
            ['company_id' => $companyId]
        );

        MessageLog::out([
            'company_id' => $companyId,
            'conversation_id' => $conversation->id,
            'lead_id' => $lead->id,
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

        return redirect()
            ->route('manager.inbox.index', ['conversation' => $conversation->id])
            ->with('success', 'Reply sent.');
    }

    public function resumeBot(Request $request, Lead $lead)
    {
        $companyId = $this->companyId($request);

        abort_if((int) $lead->company_id !== $companyId, 403);

        $lead->update([
            'conversation_state' => 'bot',
        ]);

        $conversation = Conversation::query()
            ->where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->latest('last_message_at')
            ->first();

        return redirect()
            ->route('manager.inbox.index', $conversation ? ['conversation' => $conversation->id] : [])
            ->with('success', 'Bot resumed for this conversation.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    protected function conversationContext(Conversation $conversation): array
    {
        $lead = $conversation->lead;

        return [
            'conversation_id' => $conversation->id,
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

        if (
            str_contains($lastText, 'price') ||
            str_contains($lastText, 'cost') ||
            str_contains($lastText, 'how much')
        ) {
            return match ($tone) {
                'short' => "Hi {$name}, pricing depends on the vehicle and service needed. Please share your car make/model and issue so we can guide you.",
                'urgent' => "Hi {$name}, we can help quickly. Please share your vehicle make/model and the service required so our team can confirm the best price.",
                'friendly' => "Hi {$name}, happy to help 😊 The price depends on your vehicle and the service needed. Could you please share the car make/model and issue?",
                default => "Hi {$name}, thank you for reaching out. Pricing depends on the vehicle make/model and the service required. Please share those details and we’ll guide you with the next steps.",
            };
        }

        if (
            str_contains($lastText, 'book') ||
            str_contains($lastText, 'appointment') ||
            str_contains($lastText, 'slot')
        ) {
            return match ($tone) {
                'short' => "Hi {$name}, sure. Please share your preferred date and time slot for the booking.",
                'urgent' => "Hi {$name}, we can arrange this. Please send your preferred date/time and vehicle details so we can confirm quickly.",
                'friendly' => "Hi {$name}, sure 😊 Please share your preferred date and time, and we’ll help arrange the booking.",
                default => "Hi {$name}, sure. Please share your preferred date, time, vehicle details, and service requirement so we can confirm the booking.",
            };
        }

        if (
            str_contains($lastText, 'complaint') ||
            str_contains($lastText, 'bad') ||
            str_contains($lastText, 'not happy') ||
            str_contains($lastText, 'angry')
        ) {
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