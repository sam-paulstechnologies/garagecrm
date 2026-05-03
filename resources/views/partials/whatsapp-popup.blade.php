<div 
    x-data="whatsappInbox()"
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
        class="fixed bottom-28 right-6 w-96 h-[500px]
               bg-white rounded-xl shadow-2xl border
               flex flex-col overflow-hidden"
        style="display: none;"
    >

        <!-- Header -->
        <div class="px-4 py-3 border-b font-semibold text-lg bg-gray-50">
            WhatsApp Inbox
        </div>

        <!-- Messages Area -->
        <div class="flex-1 p-4 overflow-y-auto text-sm space-y-3">

            <template x-if="loading">
                <div class="text-gray-500">Loading conversations...</div>
            </template>

            <template x-if="!loading && conversations.length === 0">
                <div class="text-gray-400">No conversations yet</div>
            </template>

            <template x-for="c in conversations" :key="c.id">
                <div class="p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <div class="font-semibold" x-text="c.customer_name"></div>
                    <div class="text-xs text-gray-500 truncate"
                         x-text="c.last_message_preview ?? ''"></div>
                </div>
            </template>

        </div>

        <!-- Input -->
        <div class="p-3 border-t flex gap-2">
            <input 
                x-model="draft"
                @keydown.enter="sendMessage"
                class="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-green-200"
                placeholder="Type message..."
            />
            <button 
                @click="sendMessage"
                class="bg-green-600 hover:bg-green-700 text-white px-4 rounded-lg"
            >
                Send
            </button>
        </div>

    </div>
</div>

<script>
function whatsappInbox() {
    return {
        open: false,
        loading: false,
        conversations: [],
        draft: '',

        init() {
            this.fetchConversations()
        },

        toggle() {
            this.open = !this.open
        },

        async fetchConversations() {
            this.loading = true
            try {
                const res = await fetch('/admin/inbox/list')
                const data = await res.json()
                this.conversations = data.conversations ?? []
            } catch (e) {
                console.error(e)
            } finally {
                this.loading = false
            }
        },

        async sendMessage() {
            if (!this.draft.trim()) return
            console.log('Sending:', this.draft)
            this.draft = ''
        }
    }
}
</script>