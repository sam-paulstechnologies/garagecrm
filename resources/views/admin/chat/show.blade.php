@extends('layouts.app')

@section('title', 'Chat · Thread #'.$conversation->id)

@section('content')
<div class="container mx-auto" x-data="chatThread({{ $conversation->id }}, '{{ route('admin.chat.messages',$conversation->id) }}', '{{ route('admin.chat.send',$conversation->id) }}')">
    <div class="mb-4">
        <a href="{{ route('admin.chat.index') }}" class="text-sm text-gray-600 hover:underline">← Back to conversations</a>
    </div>

    <div class="bg-white rounded shadow">
        <div class="border-b px-4 py-3">
            <div class="font-semibold text-gray-900">
                {{ $conversation->subject ?? 'WhatsApp Thread' }}
            </div>
            <div class="text-xs text-gray-500">#{{ $conversation->id }}</div>
        </div>

        <div id="thread" class="max-h-[65vh] overflow-y-auto p-4 space-y-3">
            @php $lastId = 0; @endphp
            @foreach($messages as $m)
                @php $lastId = $m->id; @endphp
                <div class="flex {{ $m->direction === 'out' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-lg px-3 py-2 
                        {{ $m->direction === 'out' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-900' }}">
                        <div class="whitespace-pre-wrap text-sm">{{ $m->body }}</div>
                        <div class="mt-1 text-[11px] opacity-75">
                            {{ $m->created_at->format('d M H:i') }}
                            @if($m->direction === 'out' && $m->provider_status)
                                · {{ $m->provider_status }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t p-3">
            <form x-ref="sendForm" @submit.prevent="sendMessage">
                @csrf
                <div class="flex gap-2">
                    <input type="hidden" id="after_id" value="{{ $lastId }}">
                    <input x-ref="input" type="text" name="body" id="body" class="flex-1 border rounded px-3 py-2"
                           placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/chat.js') }}"></script>
<script>
document.addEventListener('alpine:init', () => {
    window.chatThread = (conversationId, pollUrl, sendUrl) => ({
        conversationId, pollUrl, sendUrl, timer: null,
        init() {
            const box = document.getElementById('thread');
            box.scrollTop = box.scrollHeight;
            this.timer = setInterval(this.poll.bind(this), 5000);
        },
        async poll() {
            const after = document.getElementById('after_id').value || 0;
            try {
                const res = await fetch(this.pollUrl + '?after_id=' + after, {
                    headers: {'X-Requested-With':'XMLHttpRequest'}
                });
                const j = await res.json();
                if (!j.ok) return;
                if (j.messages.length) {
                    const box = document.getElementById('thread');
                    j.messages.forEach(m => this.appendMsg(m, box));
                    const last = j.messages[j.messages.length-1].id;
                    document.getElementById('after_id').value = last;
                    box.scrollTop = box.scrollHeight;
                }
            } catch(e) { /* silent */ }
        },
        async sendMessage() {
            const input = this.$refs.input;
            const text  = (input.value || '').trim();
            if (!text) return;

            const form = new FormData();
            form.append('_token', '{{ $csrf }}');
            form.append('body', text);

            const res = await fetch(this.sendUrl, {
                method: 'POST',
                headers: {'X-Requested-With':'XMLHttpRequest'},
                body: form
            });

            if (res.ok) {
                input.value = '';
                this.poll(); // pull immediately
            }
        },
        appendMsg(m, box) {
            const wrap = document.createElement('div');
            wrap.className = 'flex ' + (m.direction === 'out' ? 'justify-end' : 'justify-start');
            const bubble = document.createElement('div');
            bubble.className = 'max-w-[75%] rounded-lg px-3 py-2 ' + (m.direction === 'out' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-900');
            const body = document.createElement('div');
            body.className = 'whitespace-pre-wrap text-sm';
            body.textContent = m.body || '';
            const meta = document.createElement('div');
            meta.className = 'mt-1 text-[11px] opacity-75';
            meta.textContent = (new Date(m.created_at)).toLocaleString() + (m.direction === 'out' && m.provider_status ? (' · ' + m.provider_status) : '');
            bubble.appendChild(body);
            bubble.appendChild(meta);
            wrap.appendChild(bubble);
            box.appendChild(wrap);
        }
    });
});
</script>
@endpush
@endsection
