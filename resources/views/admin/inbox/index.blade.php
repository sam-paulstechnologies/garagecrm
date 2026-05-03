@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        WhatsApp Inbox
    </h2>
@endsection

@section('content')
<div class="h-[calc(100vh-120px)] flex bg-white shadow rounded-lg overflow-hidden">

    {{-- LEFT: Conversations --}}
    <div class="w-1/3 border-r overflow-y-auto" id="conversation-list">
        <div class="p-4 font-semibold border-b bg-gray-50">
            Conversations
        </div>
        <div id="conversation-items"></div>
    </div>

    {{-- RIGHT: Chat Panel --}}
    <div class="flex-1 flex flex-col">

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50" id="chat-messages">
            <div class="text-gray-400 text-center mt-20">
                Select a conversation
            </div>
        </div>

        {{-- Reply Box --}}
        <div class="border-t p-4 bg-white">
            <form id="reply-form" class="flex gap-2">
                @csrf
                <input type="hidden" id="conversation_id">
                <input type="text"
                       id="message-input"
                       class="flex-1 border rounded-lg px-4 py-2"
                       placeholder="Type your message..."
                       autocomplete="off" />
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Send
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
let currentConversation = null;

function loadConversations() {
    fetch("{{ route('admin.inbox.list') }}")
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('conversation-items');
            container.innerHTML = '';

            data.conversations.forEach(conv => {
                const div = document.createElement('div');
                div.className = "p-4 border-b cursor-pointer hover:bg-gray-100";
                div.innerHTML = `
                    <div class="font-semibold">${conv.customer_name ?? conv.customer_phone}</div>
                    <div class="text-sm text-gray-500 truncate">${conv.last_message_preview ?? ''}</div>
                    ${conv.unread_count > 0 ? `<span class="text-xs bg-red-500 text-white px-2 py-1 rounded">${conv.unread_count}</span>` : ''}
                `;
                div.onclick = () => loadMessages(conv.id);
                container.appendChild(div);
            });
        });
}

function loadMessages(conversationId) {
    currentConversation = conversationId;
    document.getElementById('conversation_id').value = conversationId;

    fetch(`/admin/inbox/messages/${conversationId}`)
        .then(res => res.json())
        .then(data => {
            const chat = document.getElementById('chat-messages');
            chat.innerHTML = '';

            data.messages.forEach(msg => {
                const wrapper = document.createElement('div');
                wrapper.className = msg.direction === 'out'
                    ? "flex justify-end"
                    : "flex justify-start";

                const bubble = document.createElement('div');
                bubble.className = `
                    max-w-xs px-4 py-2 rounded-lg text-sm
                    ${msg.direction === 'out'
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-200 text-gray-800'}
                `;

                bubble.innerHTML = `
                    ${msg.body}
                    ${msg.is_ai ? '<div class="text-xs opacity-70 mt-1">AI</div>' : ''}
                `;

                wrapper.appendChild(bubble);
                chat.appendChild(wrapper);
            });

            chat.scrollTop = chat.scrollHeight;
        });
}

document.getElementById('reply-form').addEventListener('submit', function(e) {
    e.preventDefault();

    if (!currentConversation) return;

    const message = document.getElementById('message-input').value;

    fetch("{{ route('admin.inbox.send') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            conversation_id: currentConversation,
            message: message
        })
    })
    .then(res => res.json())
    .then(() => {
        document.getElementById('message-input').value = '';
        loadMessages(currentConversation);
        loadConversations();
    });
});

loadConversations();
</script>
@endpush