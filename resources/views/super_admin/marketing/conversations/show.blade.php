@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => $conversation->prospect?->business_name ?? 'Conversation',
        'subtitle' => 'From platform WhatsApp channel to '.$conversation->prospect?->whatsapp_number
    ])

    <div class="grid gap-5 xl:grid-cols-[1fr_340px]">
        <div class="sa-card rounded-3xl p-5">
            <div class="mb-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('super-admin.marketing.conversations.pause-ai', $conversation) }}">@csrf<button class="rounded-2xl bg-white/10 px-4 py-2 text-xs font-black text-white">Pause AI</button></form>
                <form method="POST" action="{{ route('super-admin.marketing.conversations.resume-ai', $conversation) }}">@csrf<button class="rounded-2xl bg-emerald-500 px-4 py-2 text-xs font-black text-white">Resume AI</button></form>
                <form method="POST" action="{{ route('super-admin.marketing.conversations.takeover', $conversation) }}">@csrf<button class="rounded-2xl bg-orange-500 px-4 py-2 text-xs font-black text-white">Take Over</button></form>
            </div>
            <div class="space-y-3">
                @forelse($conversation->messages as $message)
                    <div class="rounded-3xl p-4 {{ $message->direction === 'out' ? 'ml-auto max-w-2xl bg-emerald-500/15' : 'mr-auto max-w-2xl bg-white/10' }}">
                        <p class="text-xs font-black uppercase tracking-wide text-emerald-300">{{ str($message->actor)->headline() }} · {{ str($message->direction)->headline() }} · {{ str($message->provider_status)->headline() }}</p>
                        <p class="mt-2 text-sm">{{ $message->body }}</p>
                    </div>
                @empty
                    <p class="sa-muted text-sm">No messages yet.</p>
                @endforelse
            </div>
        </div>
        <aside class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Prospect</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="sa-label">Contact</dt><dd>{{ $conversation->prospect?->contact_name ?? 'Unknown' }}</dd></div>
                <div><dt class="sa-label">Phone</dt><dd>{{ $conversation->prospect?->whatsapp_number }}</dd></div>
                <div><dt class="sa-label">Status</dt><dd>{{ str($conversation->prospect?->status)->headline() }}</dd></div>
                <div><dt class="sa-label">Pain points</dt><dd>{{ $conversation->prospect?->pain_points ?: 'Not captured' }}</dd></div>
                <div><dt class="sa-label">Handoff</dt><dd>{{ $conversation->human_takeover ? 'Human takeover' : 'AI workflow' }}</dd></div>
            </dl>
        </aside>
    </div>
@endsection
