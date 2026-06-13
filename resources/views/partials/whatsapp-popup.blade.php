<div
    x-data="whatsappInboxPopup()"
    x-init="init()"
    class="fixed bottom-6 right-6 z-[9999]"
>
    <!-- Floating Button -->
    <button
        type="button"
        @click="toggle()"
        aria-label="Open WhatsApp Inbox"
        class="flex h-16 w-16 items-center justify-center rounded-full bg-[#25D366] text-white shadow-xl shadow-green-900/30 transition duration-200 hover:scale-105 hover:bg-[#1EBE5D]"
    >
        <svg
            class="h-8 w-8 text-white"
            viewBox="0 0 32 32"
            fill="currentColor"
            aria-hidden="true"
        >
            <path d="M16.01 3C8.83 3 3 8.83 3 16.01c0 2.29.6 4.53 1.74 6.5L3 29l6.67-1.7A12.92 12.92 0 0 0 16.01 29C23.18 29 29 23.18 29 16.01 29 8.83 23.18 3 16.01 3Zm0 23.75c-2.01 0-3.97-.54-5.69-1.57l-.41-.24-3.96 1.01 1.06-3.86-.27-.43a10.63 10.63 0 0 1-1.5-5.65c0-5.94 4.83-10.77 10.77-10.77s10.76 4.83 10.76 10.77-4.83 10.74-10.76 10.74Zm5.9-8.06c-.32-.16-1.9-.94-2.2-1.04-.29-.11-.51-.16-.72.16-.21.32-.83 1.04-1.02 1.25-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.59-.96-.86-1.6-1.91-1.79-2.23-.19-.32-.02-.5.14-.66.14-.14.32-.38.48-.56.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.39-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.09 1.31 3.3c.16.21 2.26 3.45 5.48 4.84.77.33 1.37.53 1.84.68.77.24 1.47.21 2.03.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.39.19-1.53-.08-.13-.29-.21-.61-.37Z"/>
        </svg>
    </button>

    <!-- Drawer -->
    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="fixed bottom-28 right-6 flex h-[560px] w-[420px] max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-xl border bg-white shadow-2xl"
        style="display: none;"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b bg-gray-50 px-4 py-3">
            <div>
                <div class="text-lg font-semibold text-gray-900">
                    WhatsApp Inbox
                </div>

                <div class="text-xs text-gray-500" x-show="!selectedConversation">
                    Recent conversations
                </div>

                <div class="text-xs text-gray-500" x-show="selectedConversation">
                    <span x-text="selectedConversation?.customer_phone"></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <template x-if="selectedConversation">
                    <button
                        type="button"
                        @click="backToList()"
                        class="rounded bg-gray-200 px-3 py-1 text-xs text-gray-800 hover:bg-gray-300"
                    >
                        Back
                    </button>
                </template>

                <a
                    href="{{ route('admin.inbox.index') }}"
                    class="rounded bg-green-600 px-3 py-1 text-xs text-white hover:bg-green-700"
                >
                    Full Inbox
                </a>
            </div>
        </div>

        <!-- Search -->
        <div class="border-b p-3" x-show="!selectedConversation">
            <input
                x-model="search"
                @input.debounce.400ms="fetchConversations()"
                class="w-full rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring focus:ring-green-200"
                placeholder="Search name, phone, message..."
            />
        </div>

        <!-- Conversation List -->
        <div x-show="!selectedConversation" class="flex-1 space-y-3 overflow-y-auto p-3 text-sm">
            <template x-if="loading">
                <div class="text-gray-500">
                    Loading conversations...
                </div>
            </template>

            <template x-if="!loading && conversations.length === 0">
                <div class="text-gray-400">
                    No conversations yet
                </div>
            </template>

            <template x-for="c in conversations" :key="c.id">
                <button
                    type="button"
                    @click.stop="selectConversation(c)"
                    class="w-full cursor-pointer rounded-lg border p-3 text-left transition hover:bg-green-50"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-gray-900" x-text="c.customer_name || c.customer_phone"></div>
                            <div class="truncate text-xs text-gray-500" x-text="c.customer_phone || ''"></div>
                        </div>

                        <template x-if="c.unread_count > 0">
                            <span
                                class="rounded-full bg-green-600 px-2 py-0.5 text-[11px] text-white"
                                x-text="c.unread_count"
                            ></span>
                        </template>
                    </div>

                    <div
                        class="mt-2 truncate text-xs text-gray-500"
                        x-text="c.last_message_preview || 'No message preview'"
                    ></div>

                    <div
                        class="mt-1 text-[11px] text-gray-400"
                        x-text="formatTime(c.last_message_at)"
                    ></div>
                </button>
            </template>
        </div>

        <!-- Chat View -->
        <div x-show="selectedConversation" class="flex min-h-0 flex-1 flex-col">
            <!-- Chat Title -->
            <div class="border-b bg-white px-4 py-3">
                <div class="font-semibold text-gray-900" x-text="selectedConversation?.customer_name || selectedConversation?.customer_phone"></div>
                <div class="text-xs text-gray-500" x-text="selectedConversation?.customer_phone"></div>
            </div>

            <!-- Messages -->
            <div x-ref="messagesBox" class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-4">
                <template x-if="loadingMessages">
                    <div class="text-sm text-gray-400">
                        Loading messages...
                    </div>
                </template>

                <template x-if="!loadingMessages && messages.length === 0">
                    <div class="text-sm text-gray-400">
                        No messages found.
                    </div>
                </template>

                <template x-for="m in messages" :key="m.id">
                    <div :class="m.direction === 'out' ? 'flex justify-end' : 'flex justify-start'">
                        <div class="max-w-[80%]">
                            <div
                                class="whitespace-pre-wrap rounded-2xl px-3 py-2 text-sm shadow-sm"
                                :class="m.direction === 'out'
                                    ? 'bg-green-600 text-white rounded-br-sm'
                                    : 'bg-white border text-gray-900 rounded-bl-sm'"
                                x-text="m.body || ''"
                            ></div>

                            <div
                                class="mt-1 text-[10px] text-gray-400"
                                :class="m.direction === 'out' ? 'text-right' : 'text-left'"
                            >
                                <span x-text="formatTime(m.created_at)"></span>
                                <template x-if="m.provider_status">
                                    <span> · <span x-text="m.provider_status"></span></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Input -->
            <div class="border-t bg-white p-3">
                <div class="flex gap-2">
                    <input
                        x-model="draft"
                        @keydown.enter.prevent="sendMessage()"
                        class="flex-1 rounded-lg border px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring focus:ring-green-200"
                        placeholder="Type message..."
                    />

                    <button
                        type="button"
                        @click="sendMessage()"
                        :disabled="sending || !draft.trim()"
                        class="rounded-lg bg-green-600 px-4 text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        <span x-show="!sending">Send</span>
                        <span x-show="sending">...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function whatsappInboxPopup() {
    return {
        open: false,
        loading: false,
        loadingMessages: false,
        sending: false,
        conversations: [],
        messages: [],
        selectedConversation: null,
        draft: '',
        search: '',

        init() {
            this.fetchConversations();
        },

        toggle() {
            this.open = !this.open;

            if (this.open) {
                this.fetchConversations();
            }
        },

        backToList() {
            this.selectedConversation = null;
            this.messages = [];
            this.draft = '';
            this.fetchConversations();
        },

        async fetchConversations() {
            this.loading = true;

            try {
                const url = new URL('/admin/inbox/list', window.location.origin);

                if (this.search.trim()) {
                    url.searchParams.set('search', this.search.trim());
                }

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                this.conversations = data.conversations ?? [];
            } catch (e) {
                console.error('Failed to load conversations', e);
            } finally {
                this.loading = false;
            }
        },

        async selectConversation(conversation) {
            this.selectedConversation = conversation;
            this.messages = [];
            this.draft = '';

            await this.fetchMessages(conversation.id);
        },

        async fetchMessages(conversationId) {
            this.loadingMessages = true;

            try {
                const res = await fetch(`/admin/inbox/messages/${conversationId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json();
                this.messages = data.messages ?? [];

                this.$nextTick(() => {
                    if (this.$refs.messagesBox) {
                        this.$refs.messagesBox.scrollTop = this.$refs.messagesBox.scrollHeight;
                    }
                });
            } catch (e) {
                console.error('Failed to load messages', e);
            } finally {
                this.loadingMessages = false;
            }
        },

        async sendMessage() {
            if (!this.draft.trim() || !this.selectedConversation || this.sending) return;

            this.sending = true;

            try {
                const res = await fetch('/admin/inbox/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        conversation_id: this.selectedConversation.id,
                        message: this.draft.trim()
                    })
                });

                const data = await res.json();

                if (!data.ok) {
                    alert('Message failed to send.');
                    return;
                }

                this.draft = '';
                await this.fetchMessages(this.selectedConversation.id);
                await this.fetchConversations();
            } catch (e) {
                console.error('Failed to send message', e);
                alert('Message failed to send. Please check WhatsApp settings.');
            } finally {
                this.sending = false;
            }
        },

        formatTime(value) {
            if (!value) return '';

            try {
                return new Date(value).toLocaleString();
            } catch (e) {
                return value;
            }
        }
    }
}
</script>
