<div
    x-data="whatsappInboxPopup()"
    x-init="init()"
    class="fixed bottom-6 right-6 z-[9999]"
>
    <!-- Floating Button -->
    <button
        @click="toggle()"
        class="w-16 h-16 rounded-full bg-green-600 hover:bg-green-700
               text-white text-2xl shadow-xl flex items-center justify-center
               transition duration-200"
    >
        💬
    </button>

    <!-- Drawer -->
    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="fixed bottom-28 right-6 w-[420px] max-w-[calc(100vw-2rem)] h-[560px]
               bg-white rounded-xl shadow-2xl border flex flex-col overflow-hidden"
        style="display: none;"
    >
        <!-- Header -->
        <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
            <div>
                <div class="font-semibold text-lg">WhatsApp Inbox</div>
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
                        @click="backToList()"
                        class="text-xs px-3 py-1 rounded bg-gray-200 hover:bg-gray-300"
                    >
                        Back
                    </button>
                </template>

                <a
                    href="{{ route('admin.inbox.index') }}"
                    class="text-xs px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700"
                >
                    Full Inbox
                </a>
            </div>
        </div>

        <!-- Search -->
        <div class="p-3 border-b" x-show="!selectedConversation">
            <input
                x-model="search"
                @input.debounce.400ms="fetchConversations()"
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-green-200"
                placeholder="Search name, phone, message..."
            />
        </div>

        <!-- Conversation List -->
        <div x-show="!selectedConversation" class="flex-1 p-3 overflow-y-auto text-sm space-y-3">
            <template x-if="loading">
                <div class="text-gray-500">Loading conversations...</div>
            </template>

            <template x-if="!loading && conversations.length === 0">
                <div class="text-gray-400">No conversations yet</div>
            </template>

            <template x-for="c in conversations" :key="c.id">
                <button
                    type="button"
                    @click.stop="selectConversation(c)"
                    class="w-full text-left p-3 border rounded-lg hover:bg-green-50 cursor-pointer transition"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold truncate" x-text="c.customer_name || c.customer_phone"></div>
                            <div class="text-xs text-gray-500 truncate" x-text="c.customer_phone || ''"></div>
                        </div>

                        <template x-if="c.unread_count > 0">
                            <span class="bg-green-600 text-white text-[11px] rounded-full px-2 py-0.5" x-text="c.unread_count"></span>
                        </template>
                    </div>

                    <div
                        class="text-xs text-gray-500 truncate mt-2"
                        x-text="c.last_message_preview || 'No message preview'"
                    ></div>

                    <div
                        class="text-[11px] text-gray-400 mt-1"
                        x-text="formatTime(c.last_message_at)"
                    ></div>
                </button>
            </template>
        </div>

        <!-- Chat View -->
        <div x-show="selectedConversation" class="flex-1 flex flex-col min-h-0">
            <!-- Chat Title -->
            <div class="px-4 py-3 border-b bg-white">
                <div class="font-semibold" x-text="selectedConversation?.customer_name || selectedConversation?.customer_phone"></div>
                <div class="text-xs text-gray-500" x-text="selectedConversation?.customer_phone"></div>
            </div>

            <!-- Messages -->
            <div x-ref="messagesBox" class="flex-1 p-4 overflow-y-auto bg-gray-50 space-y-3">
                <template x-if="loadingMessages">
                    <div class="text-sm text-gray-400">Loading messages...</div>
                </template>

                <template x-if="!loadingMessages && messages.length === 0">
                    <div class="text-sm text-gray-400">No messages found.</div>
                </template>

                <template x-for="m in messages" :key="m.id">
                    <div :class="m.direction === 'out' ? 'flex justify-end' : 'flex justify-start'">
                        <div class="max-w-[80%]">
                            <div
                                class="px-3 py-2 rounded-2xl text-sm shadow-sm whitespace-pre-wrap"
                                :class="m.direction === 'out'
                                    ? 'bg-green-600 text-white rounded-br-sm'
                                    : 'bg-white border text-gray-900 rounded-bl-sm'"
                                x-text="m.body || ''"
                            ></div>

                            <div
                                class="text-[10px] text-gray-400 mt-1"
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
            <div class="p-3 border-t bg-white">
                <div class="flex gap-2">
                    <input
                        x-model="draft"
                        @keydown.enter.prevent="sendMessage()"
                        class="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-green-200"
                        placeholder="Type message..."
                    />

                    <button
                        @click="sendMessage()"
                        :disabled="sending || !draft.trim()"
                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 rounded-lg"
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