{{-- resources/views/admin/dashboard/partials/_whatsapp_health.blade.php --}}

@php
    $sentToday = $sentToday ?? $whatsappSentToday ?? $messagesSentToday ?? 0;
    $outbound7d = $outbound7d ?? $whatsappOutbound7d ?? $messagesOutbound7d ?? 0;
    $replies7d = $replies7d ?? $whatsappReplies7d ?? $customerReplies7d ?? 0;
    $failed7d = $failed7d ?? $whatsappFailed7d ?? $failedWhatsAppCount ?? 0;
    $aiReplies7d = $aiReplies7d ?? $whatsappAiReplies7d ?? $aiResponseCount7d ?? 0;

    $inboxRoute = \Illuminate\Support\Facades\Route::has('admin.inbox.index')
        ? route('admin.inbox.index')
        : null;

    $items = [
        [
            'label' => 'Sent Today',
            'value' => $sentToday,
            'note' => 'Messages sent today',
            'valueClass' => 'text-white',
        ],
        [
            'label' => 'Outbound 7d',
            'value' => $outbound7d,
            'note' => 'Total outbound messages',
            'valueClass' => 'text-white',
        ],
        [
            'label' => 'Replies 7d',
            'value' => $replies7d,
            'note' => 'Customer replies',
            'valueClass' => 'text-white',
        ],
        [
            'label' => 'Failed 7d',
            'value' => $failed7d,
            'note' => 'Failed WhatsApp messages',
            'valueClass' => $failed7d > 0 ? 'text-red-400' : 'text-white',
        ],
        [
            'label' => 'AI Replies 7d',
            'value' => $aiReplies7d,
            'note' => 'AI-assisted replies',
            'labelClass' => 'sf-tone-blue text-blue-300',
            'valueClass' => 'sf-tone-blue text-blue-300',
        ],
    ];
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm">
    <div class="mb-3 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-white">
                WhatsApp Health
            </h2>
            <p class="mt-1 text-xs text-slate-400">
                WhatsApp activity from message logs. Today can be zero if no messages were sent today.
            </p>
        </div>

        @if ($inboxRoute)
            <a
                href="{{ $inboxRoute }}"
                class="text-xs font-black text-orange-400 transition hover:text-orange-300"
            >
                Open Inbox
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($items as $item)
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <p class="text-xs font-bold uppercase tracking-wide {{ $item['labelClass'] ?? 'text-slate-500' }}">
                    {{ $item['label'] }}
                </p>

                <p class="mt-2 text-2xl font-extrabold {{ $item['valueClass'] }}">
                    {{ $item['value'] }}
                </p>

                <p class="mt-2 text-xs text-slate-500">
                    {{ $item['note'] }}
                </p>
            </div>
        @endforeach
    </div>
</div>
