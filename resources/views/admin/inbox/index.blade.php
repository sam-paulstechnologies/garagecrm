@extends('layouts.app')

@section('title', 'WhatsApp Inbox')

@section('content')
<div class="wa-shell">

    {{-- WhatsApp Web Layout --}}
    <div class="wa-app">

        {{-- LEFT: Conversations --}}
        <aside class="wa-sidebar">

            {{-- Sidebar Header --}}
            <div class="wa-sidebar-header">
                <div>
                    <h1 class="wa-sidebar-title">WhatsApp Inbox</h1>
                    <p class="wa-sidebar-subtitle">Manage customer conversations</p>
                </div>

                <div class="wa-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
                </div>
            </div>

            {{-- Search --}}
            <div class="wa-search-wrap">
                <div class="wa-search">
                    <span class="wa-search-icon">⌕</span>
                    <input
                        type="text"
                        id="conversation-search"
                        placeholder="Search or start new chat"
                        autocomplete="off"
                    >
                </div>
            </div>

            {{-- Conversation List --}}
            <div id="conversation-items" class="wa-conversation-list">
                <div class="wa-empty-list">
                    Loading conversations...
                </div>
            </div>
        </aside>

        {{-- CENTER: Chat --}}
        <main class="wa-chat">

            {{-- Chat Header --}}
            <header class="wa-chat-header" id="chat-header">
                <div class="flex items-center gap-3">
                    <div class="wa-contact-avatar">
                        ?
                    </div>

                    <div>
                        <div class="wa-chat-name">
                            Select a conversation
                        </div>
                        <div class="wa-chat-phone">
                            Customer messages will appear here
                        </div>
                    </div>
                </div>

                <div class="wa-chat-actions">
                    <span class="wa-chat-action-dot"></span>
                    <span class="wa-chat-action-dot"></span>
                    <span class="wa-chat-action-dot"></span>
                </div>
            </header>

            {{-- Messages --}}
            <section class="wa-messages" id="chat-messages">
                <div class="wa-welcome">
                    <div class="wa-welcome-card">
                        <div class="wa-welcome-icon">💬</div>
                        <h2>WhatsApp Inbox</h2>
                        <p>Select a conversation from the left to view messages and reply from SayaraForce.</p>
                    </div>
                </div>
            </section>

            {{-- Reply Box --}}
            <footer class="wa-reply-box">
                <form id="reply-form" class="wa-reply-form">
                    @csrf

                    <input type="hidden" id="conversation_id">

                    <button type="button" class="wa-icon-btn" title="Emoji">
                        🙂
                    </button>

                    <input
                        type="text"
                        id="message-input"
                        class="wa-message-input"
                        placeholder="Type a message"
                        autocomplete="off"
                        disabled
                    />

                    <button type="submit" id="send-button" class="wa-send-btn" disabled>
                        ➤
                    </button>
                </form>

                <div class="wa-reply-note">
                    Manual reply moves the conversation to human mode and stops bot replies.
                </div>
            </footer>
        </main>

        {{-- RIGHT: Customer Context --}}
        <aside class="wa-context">

            <div class="wa-context-header">
                <h2>Customer Info</h2>
                <p>Conversation context</p>
            </div>

            <div id="customer-context" class="wa-context-body">
                <div class="wa-context-empty">
                    Select a conversation to view customer details.
                </div>
            </div>

        </aside>

    </div>
</div>
@endsection

@push('styles')
<style>
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Web Inspired Inbox
    |--------------------------------------------------------------------------
    */

    body {
        overflow: hidden;
    }

    .wa-shell {
        height: calc(100vh - 72px);
        background: #d9dbd5;
        padding: 0;
        overflow: hidden;
    }

    .wa-shell::before {
        content: "";
        position: fixed;
        inset: 0 0 auto 0;
        height: 128px;
        background: #00a884;
        z-index: 0;
    }

    .wa-app {
        position: relative;
        z-index: 1;
        height: calc(100vh - 96px);
        margin: 12px auto;
        max-width: 1680px;
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr) 320px;
        background: #f0f2f5;
        box-shadow: 0 6px 24px rgba(11, 20, 26, 0.18);
        overflow: hidden;
    }

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */

    .wa-sidebar {
        min-width: 0;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-right: 1px solid #d1d7db;
    }

    .wa-sidebar-header {
        height: 64px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f0f2f5;
        border-bottom: 1px solid #e9edef;
    }

    .wa-sidebar-title {
        font-size: 16px;
        font-weight: 700;
        color: #111b21;
        line-height: 1.2;
    }

    .wa-sidebar-subtitle {
        margin-top: 2px;
        font-size: 12px;
        color: #667781;
    }

    .wa-avatar,
    .wa-contact-avatar {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #00a884;
        color: #ffffff;
        font-weight: 800;
        flex-shrink: 0;
    }

    .wa-search-wrap {
        padding: 8px 12px;
        background: #ffffff;
        border-bottom: 1px solid #e9edef;
    }

    .wa-search {
        height: 40px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        border-radius: 10px;
        background: #f0f2f5;
    }

    .wa-search-icon {
        font-size: 16px;
        color: #667781;
    }

    .wa-search input {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #111b21;
        font-size: 14px;
    }

    .wa-search input::placeholder {
        color: #667781;
    }

    .wa-conversation-list {
        flex: 1;
        overflow-y: auto;
        background: #ffffff;
    }

    .wa-conversation-item {
        min-height: 76px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f0f2f5;
        transition: background 0.15s ease;
    }

    .wa-conversation-item:hover {
        background: #f5f6f6;
    }

    .wa-conversation-item.active {
        background: #e9edef;
    }

    .wa-conv-avatar {
        width: 48px;
        height: 48px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #dfe5e7;
        color: #54656f;
        font-weight: 800;
        flex-shrink: 0;
    }

    .wa-conv-main {
        min-width: 0;
        flex: 1;
    }

    .wa-conv-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .wa-conv-name {
        font-size: 15px;
        font-weight: 700;
        color: #111b21;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .wa-conv-time {
        font-size: 11px;
        color: #667781;
        white-space: nowrap;
    }

    .wa-conv-bottom {
        margin-top: 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .wa-conv-preview {
        min-width: 0;
        color: #667781;
        font-size: 13px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .wa-unread {
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #25d366;
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
    }

    .wa-empty-list,
    .wa-context-empty {
        padding: 24px;
        text-align: center;
        color: #667781;
        font-size: 14px;
    }

    /*
    |--------------------------------------------------------------------------
    | Chat Area
    |--------------------------------------------------------------------------
    */

    .wa-chat {
        min-width: 0;
        display: flex;
        flex-direction: column;
        background: #efeae2;
    }

    .wa-chat-header {
        height: 64px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f0f2f5;
        border-bottom: 1px solid #d1d7db;
        flex-shrink: 0;
    }

    .wa-chat-name {
        font-size: 15px;
        font-weight: 800;
        color: #111b21;
    }

    .wa-chat-phone {
        margin-top: 2px;
        font-size: 12px;
        color: #667781;
    }

    .wa-chat-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .wa-chat-action-dot {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: #54656f;
    }

    .wa-messages {
        position: relative;
        flex: 1;
        overflow-y: auto;
        padding: 24px 64px;
        background-color: #efeae2;
        background-image:
            radial-gradient(circle at 12px 12px, rgba(0, 0, 0, 0.045) 1px, transparent 1.5px),
            radial-gradient(circle at 38px 34px, rgba(0, 0, 0, 0.035) 1px, transparent 1.5px),
            linear-gradient(45deg, rgba(0, 0, 0, 0.018) 25%, transparent 25%),
            linear-gradient(-45deg, rgba(0, 0, 0, 0.018) 25%, transparent 25%);
        background-size: 54px 54px, 54px 54px, 80px 80px, 80px 80px;
        background-position: 0 0, 0 0, 0 0, 0 0;
    }

    .wa-welcome {
        min-height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .wa-welcome-card {
        max-width: 420px;
        padding: 28px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.78);
        text-align: center;
        box-shadow: 0 8px 24px rgba(11, 20, 26, 0.08);
    }

    .wa-welcome-icon {
        font-size: 38px;
        margin-bottom: 10px;
    }

    .wa-welcome-card h2 {
        font-size: 20px;
        font-weight: 800;
        color: #111b21;
    }

    .wa-welcome-card p {
        margin-top: 8px;
        font-size: 14px;
        color: #667781;
        line-height: 1.6;
    }

    .wa-message-row {
        display: flex;
        margin-bottom: 8px;
    }

    .wa-message-row.in {
        justify-content: flex-start;
    }

    .wa-message-row.out {
        justify-content: flex-end;
    }

    .wa-bubble {
        position: relative;
        max-width: min(560px, 72%);
        padding: 8px 10px 6px;
        border-radius: 8px;
        box-shadow: 0 1px 0.5px rgba(11, 20, 26, 0.13);
        color: #111b21;
        font-size: 14px;
        line-height: 1.45;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .wa-message-row.in .wa-bubble {
        background: #ffffff;
        border-top-left-radius: 2px;
    }

    .wa-message-row.out .wa-bubble {
        background: #d9fdd3;
        border-top-right-radius: 2px;
    }

    .wa-bubble-meta {
        margin-top: 4px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        color: #667781;
        font-size: 10px;
        line-height: 1;
    }

    .wa-ai-chip {
        margin-right: auto;
        padding: 2px 5px;
        border-radius: 999px;
        background: rgba(0, 168, 132, 0.12);
        color: #008069;
        font-size: 10px;
        font-weight: 800;
    }

    /*
    |--------------------------------------------------------------------------
    | Reply Box
    |--------------------------------------------------------------------------
    */

    .wa-reply-box {
        padding: 10px 16px 8px;
        background: #f0f2f5;
        border-top: 1px solid #d1d7db;
        flex-shrink: 0;
    }

    .wa-reply-form {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .wa-icon-btn,
    .wa-send-btn {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        font-weight: 800;
        transition: all 0.15s ease;
    }

    .wa-icon-btn {
        background: transparent;
        color: #54656f;
        font-size: 20px;
    }

    .wa-icon-btn:hover {
        background: #e9edef;
    }

    .wa-message-input {
        flex: 1;
        height: 42px;
        border: 0;
        outline: 0;
        border-radius: 999px;
        background: #ffffff;
        padding: 0 16px;
        color: #111b21;
        font-size: 14px;
    }

    .wa-message-input:disabled {
        background: #e9edef;
        cursor: not-allowed;
    }

    .wa-send-btn {
        background: #00a884;
        color: #ffffff;
        font-size: 16px;
    }

    .wa-send-btn:hover:not(:disabled) {
        background: #008069;
    }

    .wa-send-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .wa-reply-note {
        margin-top: 6px;
        padding-left: 54px;
        color: #667781;
        font-size: 11px;
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Context
    |--------------------------------------------------------------------------
    */

    .wa-context {
        min-width: 0;
        background: #ffffff;
        border-left: 1px solid #d1d7db;
        display: flex;
        flex-direction: column;
    }

    .wa-context-header {
        height: 64px;
        padding: 12px 16px;
        background: #f0f2f5;
        border-bottom: 1px solid #e9edef;
    }

    .wa-context-header h2 {
        font-size: 15px;
        font-weight: 800;
        color: #111b21;
    }

    .wa-context-header p {
        margin-top: 2px;
        color: #667781;
        font-size: 12px;
    }

    .wa-context-body {
        padding: 18px;
        overflow-y: auto;
    }

    .wa-context-card {
        border-radius: 14px;
        border: 1px solid #e9edef;
        background: #ffffff;
        overflow: hidden;
    }

    .wa-context-profile {
        padding: 22px 16px;
        text-align: center;
        border-bottom: 1px solid #e9edef;
    }

    .wa-context-big-avatar {
        width: 72px;
        height: 72px;
        margin: 0 auto 12px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #00a884;
        color: #ffffff;
        font-size: 28px;
        font-weight: 900;
    }

    .wa-context-name {
        font-size: 17px;
        font-weight: 800;
        color: #111b21;
    }

    .wa-context-phone {
        margin-top: 4px;
        font-size: 13px;
        color: #667781;
    }

    .wa-context-section {
        padding: 14px 16px;
        border-bottom: 1px solid #f0f2f5;
    }

    .wa-context-label {
        font-size: 11px;
        font-weight: 800;
        color: #667781;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .wa-context-value {
        margin-top: 5px;
        color: #111b21;
        font-size: 14px;
        font-weight: 700;
    }

    .wa-human-note {
        margin-top: 16px;
        padding: 14px;
        border-radius: 12px;
        background: #fff7db;
        border: 1px solid #f4e4a4;
        color: #6b5300;
        font-size: 13px;
        line-height: 1.5;
    }

    .wa-human-note strong {
        display: block;
        margin-bottom: 4px;
        color: #4a3900;
    }

    /*
    |--------------------------------------------------------------------------
    | Responsive
    |--------------------------------------------------------------------------
    */

    @media (max-width: 1280px) {
        .wa-app {
            grid-template-columns: 340px minmax(0, 1fr);
        }

        .wa-context {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .wa-shell {
            height: calc(100vh - 64px);
        }

        .wa-app {
            height: calc(100vh - 76px);
            margin: 0;
            grid-template-columns: 1fr;
        }

        .wa-sidebar {
            display: none;
        }

        .wa-messages {
            padding: 18px 16px;
        }

        .wa-bubble {
            max-width: 86%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
let currentConversation = null;
let activeConversationItem = null;
let conversationSearchTimer = null;

const listUrl = @json(route('admin.inbox.list'));
const sendUrl = @json(route('admin.inbox.send'));

const conversationItems = document.getElementById('conversation-items');
const chatMessages = document.getElementById('chat-messages');
const chatHeader = document.getElementById('chat-header');
const replyForm = document.getElementById('reply-form');
const messageInput = document.getElementById('message-input');
const sendButton = document.getElementById('send-button');
const conversationIdInput = document.getElementById('conversation_id');
const searchInput = document.getElementById('conversation-search');
const customerContext = document.getElementById('customer-context');

function escapeText(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatTime(value) {
    if (!value) return '';

    try {
        return new Intl.DateTimeFormat('en', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }).format(new Date(value));
    } catch (e) {
        return '';
    }
}

function formatDateTime(value) {
    if (!value) return '';

    try {
        return new Intl.DateTimeFormat('en', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }).format(new Date(value));
    } catch (e) {
        return '';
    }
}

function getInitials(name, phone) {
    const base = (name || phone || '?').trim();

    if (!base) return '?';

    const words = base.split(/\s+/).filter(Boolean);

    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }

    return base[0].toUpperCase();
}

function buildListUrl() {
    const search = searchInput?.value?.trim() || '';

    if (!search) {
        return listUrl;
    }

    return `${listUrl}?search=${encodeURIComponent(search)}`;
}

function loadConversations() {
    fetch(buildListUrl())
        .then(res => res.json())
        .then(data => {
            conversationItems.innerHTML = '';

            if (!data.conversations || data.conversations.length === 0) {
                conversationItems.innerHTML = `
                    <div class="wa-empty-list">
                        No conversations found.
                    </div>
                `;
                return;
            }

            data.conversations.forEach(conv => {
                const item = document.createElement('div');
                item.className = 'wa-conversation-item';
                item.dataset.id = conv.id;

                if (String(currentConversation) === String(conv.id)) {
                    item.classList.add('active');
                    activeConversationItem = item;
                }

                const displayName = conv.customer_name || conv.customer_phone || 'Unknown';
                const initials = getInitials(conv.customer_name, conv.customer_phone);
                const time = formatTime(conv.last_message_at);
                const preview = conv.last_message_preview || 'No messages yet';

                item.innerHTML = `
                    <div class="wa-conv-avatar">${escapeText(initials)}</div>

                    <div class="wa-conv-main">
                        <div class="wa-conv-top">
                            <div class="wa-conv-name">${escapeText(displayName)}</div>
                            <div class="wa-conv-time">${escapeText(time)}</div>
                        </div>

                        <div class="wa-conv-bottom">
                            <div class="wa-conv-preview">${escapeText(preview)}</div>
                            ${conv.unread_count > 0 ? `<span class="wa-unread">${conv.unread_count}</span>` : ''}
                        </div>
                    </div>
                `;

                item.onclick = () => {
                    if (activeConversationItem) {
                        activeConversationItem.classList.remove('active');
                    }

                    item.classList.add('active');
                    activeConversationItem = item;

                    loadMessages(conv.id, conv);
                };

                conversationItems.appendChild(item);
            });
        })
        .catch(() => {
            conversationItems.innerHTML = `
                <div class="wa-empty-list">
                    Failed to load conversations.
                </div>
            `;
        });
}

function updateChatHeader(conv, context = null) {
    const name = context?.name || conv?.customer_name || conv?.customer_phone || 'Customer';
    const phone = context?.phone || conv?.customer_phone || '';
    const initials = getInitials(name, phone);

    chatHeader.innerHTML = `
        <div class="flex items-center gap-3">
            <div class="wa-contact-avatar">
                ${escapeText(initials)}
            </div>

            <div>
                <div class="wa-chat-name">${escapeText(name)}</div>
                <div class="wa-chat-phone">${escapeText(phone)}</div>
            </div>
        </div>

        <div class="wa-chat-actions">
            <span class="wa-chat-action-dot"></span>
            <span class="wa-chat-action-dot"></span>
            <span class="wa-chat-action-dot"></span>
        </div>
    `;
}

function updateCustomerContext(context) {
    if (!context) {
        customerContext.innerHTML = `
            <div class="wa-context-empty">
                No customer context available.
            </div>
        `;
        return;
    }

    const name = context.name || context.lead_name || 'Unknown';
    const phone = context.phone || '—';
    const initials = getInitials(name, phone);
    const leadStatus = context.lead_status || 'Not linked';
    const conversationState = context.conversation_state || 'Not available';

    customerContext.innerHTML = `
        <div class="wa-context-card">
            <div class="wa-context-profile">
                <div class="wa-context-big-avatar">${escapeText(initials)}</div>
                <div class="wa-context-name">${escapeText(name)}</div>
                <div class="wa-context-phone">${escapeText(phone)}</div>
            </div>

            <div class="wa-context-section">
                <div class="wa-context-label">Lead Status</div>
                <div class="wa-context-value">${escapeText(leadStatus)}</div>
            </div>

            <div class="wa-context-section">
                <div class="wa-context-label">Conversation State</div>
                <div class="wa-context-value">${escapeText(conversationState)}</div>
            </div>

            <div class="wa-context-section">
                <div class="wa-context-label">Lead ID</div>
                <div class="wa-context-value">${context.lead_id ? '#' + escapeText(context.lead_id) : 'Not linked'}</div>
            </div>
        </div>

        <div class="wa-human-note">
            <strong>Human takeover</strong>
            Sending a manual reply moves this lead into human mode and stops bot replies.
        </div>
    `;
}

function loadMessages(conversationId, conv = null) {
    currentConversation = conversationId;
    conversationIdInput.value = conversationId;

    messageInput.disabled = false;
    sendButton.disabled = false;

    chatMessages.innerHTML = `
        <div class="wa-welcome">
            <div class="wa-welcome-card">
                <div class="wa-welcome-icon">⏳</div>
                <h2>Loading messages</h2>
                <p>Please wait while we fetch the conversation.</p>
            </div>
        </div>
    `;

    fetch(`/admin/inbox/messages/${conversationId}`)
        .then(res => res.json())
        .then(data => {
            chatMessages.innerHTML = '';

            updateChatHeader(conv, data.context);
            updateCustomerContext(data.context);

            if (!data.messages || data.messages.length === 0) {
                chatMessages.innerHTML = `
                    <div class="wa-welcome">
                        <div class="wa-welcome-card">
                            <div class="wa-welcome-icon">💬</div>
                            <h2>No messages yet</h2>
                            <p>Start the conversation by typing a message below.</p>
                        </div>
                    </div>
                `;
                return;
            }

            data.messages.forEach(msg => {
                const row = document.createElement('div');
                row.className = msg.direction === 'out'
                    ? 'wa-message-row out'
                    : 'wa-message-row in';

                const bubble = document.createElement('div');
                bubble.className = 'wa-bubble';

                const body = document.createElement('div');
                body.textContent = msg.body || '[Non-text message received]';

                const meta = document.createElement('div');
                meta.className = 'wa-bubble-meta';

                if (msg.is_ai) {
                    const ai = document.createElement('span');
                    ai.className = 'wa-ai-chip';
                    ai.textContent = 'AI';
                    meta.appendChild(ai);
                }

                const time = document.createElement('span');
                time.textContent = formatTime(msg.created_at);
                meta.appendChild(time);

                if (msg.direction === 'out') {
                    const ticks = document.createElement('span');
                    ticks.textContent = msg.provider_status === 'read' ? '✓✓' : '✓';
                    meta.appendChild(ticks);
                }

                bubble.appendChild(body);
                bubble.appendChild(meta);
                row.appendChild(bubble);
                chatMessages.appendChild(row);
            });

            chatMessages.scrollTop = chatMessages.scrollHeight;
        })
        .catch(() => {
            chatMessages.innerHTML = `
                <div class="wa-welcome">
                    <div class="wa-welcome-card">
                        <div class="wa-welcome-icon">⚠️</div>
                        <h2>Failed to load messages</h2>
                        <p>Please refresh or try selecting the conversation again.</p>
                    </div>
                </div>
            `;
        });
}

replyForm.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!currentConversation) return;

    const message = messageInput.value.trim();

    if (!message) return;

    sendButton.disabled = true;

    fetch(sendUrl, {
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
        messageInput.value = '';
        loadMessages(currentConversation);
        loadConversations();
    })
    .finally(() => {
        sendButton.disabled = false;
        messageInput.focus();
    });
});

searchInput?.addEventListener('input', function () {
    clearTimeout(conversationSearchTimer);

    conversationSearchTimer = setTimeout(() => {
        loadConversations();
    }, 300);
});

loadConversations();
</script>
@endpush