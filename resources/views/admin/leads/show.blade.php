{{-- resources/views/admin/leads/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-8 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Lead Details</h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.leads.edit', $lead) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">
                ✏️ Edit Lead
            </a>

            {{-- Quick: Toggle Hot --}}
            <form method="POST" action="{{ route('admin.leads.toggleHot', $lead) }}">
                @csrf
                @method('PATCH')
                <button class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded shadow">
                    {{ $lead->is_hot ? '🔥 Unmark Hot' : '⭐ Mark Hot' }}
                </button>
            </form>

            {{-- Quick: Touch Contacted --}}
            <form method="POST" action="{{ route('admin.leads.touch', $lead) }}">
                @csrf
                @method('PATCH')
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded shadow">
                    ☎️ Touch Contacted
                </button>
            </form>

            {{-- Quick: Convert --}}
            <form method="POST" action="{{ route('admin.leads.convert', $lead) }}">
                @csrf
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded shadow">
                    🔁 Convert to Opportunity
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">
            <div><strong>Name:</strong> {{ $lead->name }}</div>
            <div><strong>Email:</strong> {{ $lead->email ?? '—' }}</div>
            <div><strong>Phone:</strong> {{ $lead->phone ?? '—' }}</div>
            <div><strong>Status:</strong> {{ ucfirst($lead->status) }}</div>
            <div><strong>Source:</strong> {{ $lead->source ?? '—' }}</div>
            <div><strong>Assigned To:</strong> {{ $lead->assignee?->name ?? '—' }}</div>
            <div><strong>Preferred Channel:</strong> {{ $lead->preferred_channel ? ucfirst($lead->preferred_channel) : '—' }}</div>
            <div><strong>Last Contacted:</strong> {{ $lead->last_contacted_at?->format('d/m/Y, H:i') ?? '—' }}</div>
            <div><strong>Is Hot:</strong> {{ $lead->is_hot ? 'Yes' : 'No' }}</div>
            <div><strong>Lead Score Reason:</strong> {{ $lead->lead_score_reason ?? '—' }}</div>
            <div class="md:col-span-2"><strong>Notes:</strong> {{ $lead->notes ?? '—' }}</div>
            <div><strong>Score:</strong> {{ $lead->score ?? ($lead->lead_score ?? 0) }}</div>
            <div><strong>Created:</strong> {{ $lead->created_at?->format('d/m/Y, H:i') ?? '—' }}</div>
        </div>
    </div>

    {{-- 🧠 Co-Pilot Actions --}}
    <div class="bg-white p-6 rounded-lg shadow space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">AI Co-Pilot</h2>
            <div class="text-xs text-gray-500">Lead ID: {{ $lead->id }}</div>
        </div>

        <div x-data="copilot()" x-init="init({{ $lead->id }})" class="space-y-3">
            <div class="flex flex-wrap gap-2">
                <button @click="loadMeta" class="px-3 py-2 bg-gray-800 hover:bg-black text-white rounded">Load Meta</button>
                <button @click="suggest" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">Suggest Reply</button>
                <button @click="quickBooking" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded">Quick Booking</button>
                <button @click="followup" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Follow-up +24h</button>
                <button @click="sendTemplate" class="px-3 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded">Send Template</button>
            </div>

            <template x-if="loading">
                <div class="text-sm text-gray-500">Loading…</div>
            </template>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold mb-1">Meta</h3>
                    <pre class="text-xs bg-gray-50 p-3 rounded overflow-auto h-48" x-text="json(meta)"></pre>
                </div>
                <div>
                    <h3 class="font-semibold mb-1">Suggested Reply</h3>
                    <textarea x-model="reply" class="w-full border rounded p-2 text-sm" rows="6" placeholder="Click ‘Suggest Reply’"></textarea>
                </div>
            </div>
        </div>

        {{-- Alpine (inline) --}}
        <script>
            function copilot() {
                return {
                    loading: false,
                    meta: {},
                    reply: '',
                    leadId: null,
                    init(id){ this.leadId = id; },
                    json(o){ try { return JSON.stringify(o, null, 2);} catch(e){return '';} },
                    async loadMeta(){
                        this.loading = true;
                        try{
                            const r = await fetch(`{{ url('admin/leads') }}/${this.leadId}/copilot/meta`);
                            this.meta = await r.json();
                        } finally { this.loading = false; }
                    },
                    async suggest(){
                        this.loading = true;
                        try{
                            const r = await fetch(`{{ url('admin/leads') }}/${this.leadId}/copilot/suggest-reply`, {
                                method: 'POST',
                                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
                            });
                            const j = await r.json();
                            this.reply = j.reply || '';
                        } finally { this.loading = false; }
                    },
                    async quickBooking(){
                        this.loading = true;
                        try{
                            await fetch(`{{ url('admin/leads') }}/${this.leadId}/copilot/quick-booking`, {
                                method: 'POST',
                                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
                            });
                            alert('Quick booking created (default tomorrow 10:00).');
                        } finally { this.loading = false; }
                    },
                    async followup(){
                        this.loading = true;
                        try{
                            await fetch(`{{ url('admin/leads') }}/${this.leadId}/copilot/followup`, {
                                method: 'POST',
                                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                                body: new URLSearchParams({hours: 24})
                            });
                            alert('Follow-up scheduled (+24h).');
                        } finally { this.loading = false; }
                    },
                    async sendTemplate(){
                        this.loading = true;
                        try{
                            await fetch(`{{ url('admin/leads') }}/${this.leadId}/copilot/send-template`, {
                                method: 'POST',
                                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                                body: new URLSearchParams({template: 'lead_acknowledgment_v2'})
                            });
                            alert('Template enqueued.');
                        } finally { this.loading = false; }
                    }
                }
            }
        </script>
    </div>

    {{-- 🗨️ Communications --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Communications</h2>
            <a href="{{ route('admin.communications.create', ['lead_id' => $lead->id, 'client_id' => $lead->client_id]) }}"
               class="text-sm text-blue-600 underline">Add Communication</a>
        </div>

        @php
            $communications = \App\Models\Shared\Communication::where('company_id', company_id())
                ->where('lead_id', $lead->id)
                ->orderByDesc('communication_date')->orderByDesc('id')
                ->paginate(10);
        @endphp

        @if($communications->count())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left bg-gray-50">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Content</th>
                            <th class="px-3 py-2">Follow-up</th>
                            <th class="px-3 py-2">Completed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($communications as $c)
                            <tr>
                                <td class="px-3 py-2">{{ \Carbon\Carbon::parse($c->communication_date)->format('d M Y, h:i A') }}</td>
                                <td class="px-3 py-2">{{ $c->communication_type }}</td>
                                <td class="px-3 py-2">{{ Str::limit($c->content, 120) }}</td>
                                <td class="px-3 py-2">{{ $c->follow_up_required ? 'Yes' : 'No' }}</td>
                                <td class="px-3 py-2">{{ $c->is_completed ? '✔' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $communications->links() }}
            </div>
        @else
            <p class="text-sm text-gray-500">No communications yet.</p>
        @endif
    </div>
</div>
@endsection
