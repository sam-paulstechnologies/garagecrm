@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => 'Marketing Command Center',
        'subtitle' => 'PaulTechnologies-owned prospecting, WhatsApp campaigns, AI sales conversations, and demo conversion tracking. This data is isolated from garage tenants.'
    ])

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($metrics as $label => $value)
            <div class="sa-card rounded-3xl p-5">
                <p class="sa-label text-xs font-black uppercase tracking-wide">{{ str($label)->headline() }}</p>
                <p class="mt-3 text-3xl font-black">{{ is_numeric($value) ? $value : e($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-3">
        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Channel Health</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="sa-label">Number</dt><dd>{{ $channel?->display_phone_number ?? '+971527427692' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="sa-label">Phone Number ID</dt><dd>{{ $channel?->masked_phone_number_id ?? '1070...0019' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="sa-label">Status</dt><dd>{{ str($channel?->connection_status ?? 'not_connected')->headline() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="sa-label">Webhook</dt><dd>{{ str($channel?->webhook_health ?? 'unknown')->headline() }}</dd></div>
            </dl>
        </div>

        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Recent Conversations</h2>
            <div class="mt-4 space-y-3">
                @forelse($recentConversations as $conversation)
                    <a href="{{ route('super-admin.marketing.conversations.show', $conversation) }}" class="sa-soft block rounded-2xl p-3 text-sm">
                        <span class="font-bold">{{ $conversation->prospect?->business_name ?? $conversation->prospect?->contact_name ?? 'Prospect' }}</span>
                        <span class="sa-label block">{{ str($conversation->state)->headline() }} · {{ $conversation->last_message_at?->diffForHumans() ?? 'No messages yet' }}</span>
                    </a>
                @empty
                    <p class="sa-muted text-sm">No platform conversations yet.</p>
                @endforelse
            </div>
        </div>

        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Upcoming Demos</h2>
            <div class="mt-4 space-y-3">
                @forelse($upcomingAppointments as $appointment)
                    <div class="sa-soft rounded-2xl p-3 text-sm">
                        <span class="font-bold">{{ $appointment->starts_at?->format('d M, H:i') }}</span>
                        <span class="sa-label block">{{ str($appointment->status)->headline() }}</span>
                    </div>
                @empty
                    <p class="sa-muted text-sm">No upcoming demo appointments.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
