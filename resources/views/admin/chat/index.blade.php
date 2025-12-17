@extends('layouts.admin')

@section('content')

{{-- Initial data for React (optional) --}}
<script>
    window.__CONV_DATA__ = {
        conversations: @json($conversations->items())
    };
</script>

{{-- Inbox auto-refresh JS --}}
<script>
    // React dispatches an event whenever it fetches updated conversation list
    window.addEventListener("conv-list-update", (e) => {
        const items = e.detail;
        const list = document.getElementById("blade-inbox-list");

        if (!list) return;

        list.innerHTML = "";

        items.forEach(conv => {
            list.innerHTML += `
                <a href="/admin/chat/${conv.id}" 
                   class="block border-b px-3 py-2.5 text-sm hover:bg-slate-50">

                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-800 truncate">
                                ${conv.customer_name || "Unknown"}
                            </div>
                            <div class="text-xs text-gray-500 truncate">
                                ${conv.customer_phone || "-"}
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-[11px] text-gray-400">
                                ${conv.last_message_at ? new Date(conv.last_message_at).toLocaleString() : ""}
                            </div>

                            ${conv.unread_count > 0 
                                ? `<span class="inline-flex rounded-full bg-sky-600 text-white text-[11px] px-2 py-0.5">
                                     ${conv.unread_count}
                                   </span>`
                                : ""}
                        </div>
                    </div>

                    <div class="mt-1 text-xs text-gray-500 line-clamp-2">
                        ${conv.last_message_preview || ""}
                    </div>
                `;
        });
    });
</script>

<div class="flex h-[calc(100vh-5rem)] gap-4">

    {{-- LEFT-SIDE INBOX PANEL --}}
    <div class="w-80 border border-gray-200 bg-white rounded-lg flex flex-col">

        {{-- Header --}}
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <h1 class="text-base font-semibold text-gray-800">Inbox</h1>
            <span class="text-xs text-gray-500">{{ $conversations->total() }} conv.</span>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.chat.index') }}" class="p-3 border-b border-gray-100">
            <div class="flex gap-2">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search name, phone…"
                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-slate-500 focus:border-slate-500"
                />
            </div>
        </form>

        {{-- AUTO-REFRESHING INBOX LIST --}}
        <div id="blade-inbox-list" class="flex-1 overflow-y-auto">

            {{-- Blade fallback (initial render) --}}
            @forelse($conversations as $conv)
                @php $isActive = (int)$activeConversationId === (int)$conv->id; @endphp

                <a href="{{ route('admin.chat.show', $conv) }}"
                   class="block border-b px-3 py-2.5 text-sm hover:bg-slate-50 {{ $isActive ? 'bg-slate-100' : '' }}">

                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-800 truncate">
                                {{ $conv->customer_name ?: 'Unknown' }}
                            </div>

                            <div class="text-xs text-gray-500 truncate">
                                {{ $conv->customer_phone ?: '-' }}
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-[11px] text-gray-400">
                                {{ optional($conv->last_message_at)->diffForHumans() }}
                            </div>

                            @if(($conv->unread_count ?? 0) > 0)
                                <span class="inline-flex rounded-full bg-sky-600 text-white text-[11px] px-2 py-0.5">
                                    {{ $conv->unread_count }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($conv->last_message_preview)
                        <div class="mt-1 text-xs text-gray-500 line-clamp-2">
                            {{ $conv->last_message_preview }}
                        </div>
                    @endif

                </a>

            @empty
                <div class="p-4 text-sm text-gray-500">No conversations yet.</div>
            @endforelse

        </div>

        {{-- Footer --}}
        <div class="border-t px-3 py-2 text-[11px] text-gray-400 flex items-center justify-between">
            <span>Showing {{ $conversations->count() }} of {{ $conversations->total() }}</span>
            <span>Chat · WhatsApp</span>
        </div>

    </div>

    {{-- RIGHT SIDE — REACT CHAT THREAD --}}
    <div class="flex-1 border border-gray-200 bg-white rounded-lg flex flex-col overflow-hidden">

        <div
            id="chat-window"
            class="flex-1"
            data-conversation="{{ $activeConversationId }}"
            data-endpoint-messages="{{ $activeConversationId ? route('admin.chat.messages', $activeConversationId) : '' }}"
            data-endpoint-send="{{ $activeConversationId ? route('admin.chat.send', $activeConversationId) : '' }}"
            data-endpoint-smart-replies="{{ $activeConversationId ? route('admin.chat.smart-replies', $activeConversationId) : '' }}"
        >

            {{-- Placeholder before React mounts --}}
            @if(!$activeConversationId)
                <div class="flex-1 flex flex-col items-center justify-center text-center p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-1">
                        Select a conversation
                    </h2>
                    <p class="text-sm text-gray-500 max-w-sm">
                        Choose a conversation from the left to view messages and reply.
                    </p>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                    Loading chat…
                </div>
            @endif

        </div>

    </div>
</div>

@endsection
