@extends('layouts.app')

@section('title', 'Review WhatsApp Contact')

@section('content')
@php
    $labels = ['likely_customer' => 'Likely Customer', 'possible_personal' => 'Possible Personal', 'possible_colleague' => 'Possible Colleague / Staff', 'unknown' => 'Unknown'];
@endphp
<div class="sf-page max-w-5xl">
    @if(session('success'))<div class="sf-alert-success mb-5">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="sf-alert-danger mb-5">{{ session('error') }}</div>@endif
    <a href="{{ route('admin.messaging.whatsapp.history.index') }}" class="text-sm font-semibold text-[color:var(--sf-orange)]">Back to history review</a>
    <header class="mt-4 sf-card p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div><div class="sf-badge-orange">Staged contact</div><h1 class="sf-page-title mt-3">{{ $candidate->display_name ?: 'Unnamed WhatsApp contact' }}</h1><p class="mt-2 font-mono text-lg">{{ $candidate->phone_e164 }}</p></div>
            <div class="text-sm text-[color:var(--sf-muted)]">{{ number_format($candidate->message_count) }} messages<br>{{ $candidate->first_message_at?->format('d M Y') }} – {{ $candidate->last_message_at?->format('d M Y') }}</div>
        </div>
    </header>

    <div class="mt-6 grid gap-5 md:grid-cols-2">
        <section class="sf-card p-5"><div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--sf-muted)]">Suggestion</div><h2 class="mt-2 text-xl font-semibold">{{ $canPreview ? ($labels[$candidate->classification] ?? 'Unknown') : 'Detailed intelligence locked' }}</h2><p class="mt-3 text-sm leading-6 text-[color:var(--sf-muted)]">{{ $canPreview ? $candidate->classification_reason : 'Select this contact for analysis when allowance is available.' }}</p></section>
        <section class="sf-card p-5"><div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--sf-muted)]">Retention</div><h2 class="mt-2 text-xl font-semibold">{{ $canPreview ? str($candidate->retention_level)->title().' potential' : 'Locked' }}</h2><p class="mt-3 text-sm leading-6 text-[color:var(--sf-muted)]">{{ $canPreview ? $candidate->retention_reason : 'Upgrade or use an available history-intelligence unit to see this assessment.' }}</p></section>
    </div>

    <section class="sf-card mt-6 p-6">
        <h2 class="sf-section-title">Conversation preview</h2>
        @if($canPreview)
            <div class="mt-5 space-y-3">
                @forelse($candidate->messages->whereNull('purged_at') as $message)
                    <div class="flex {{ $message->direction === 'out' ? 'justify-end' : 'justify-start' }}"><div class="max-w-[80%] rounded-2xl border border-[color:var(--sf-border)] bg-[color:var(--sf-surface-muted)] px-4 py-3"><div class="text-sm">{{ $message->body ?: '['.ucfirst($message->message_type).']' }}</div><div class="mt-1 text-xs text-[color:var(--sf-muted)]">{{ $message->message_timestamp?->format('d M Y H:i') }} · historical</div></div></div>
                @empty<p class="text-sm text-[color:var(--sf-muted)]">Staged content has been purged or is unavailable.</p>@endforelse
            </div>
        @else
            <div class="sf-alert-warning mt-4">Conversation content becomes available only after this unique contact is selected for analysis under the plan allowance.</div>
            @if($quota['remaining'] === null || $quota['remaining'] > 0)
                <form method="POST" action="{{ route('admin.messaging.whatsapp.history.analyse', $candidate) }}" class="mt-4">@csrf<button class="sf-btn-primary">Analyse this contact</button></form>
            @endif
        @endif
    </section>

    <section class="sf-card mt-6 p-6">
        <h2 class="sf-section-title">Your decision</h2><p class="mt-2 text-sm text-[color:var(--sf-muted)]">AI and deterministic signals are suggestions. You decide whether this relationship belongs in the CRM.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            @if($canPreview)<form method="POST" action="{{ route('admin.messaging.whatsapp.history.decision', $candidate) }}">@csrf<input type="hidden" name="decision" value="track"><button class="sf-btn-primary">Track</button></form>@endif
            <form method="POST" action="{{ route('admin.messaging.whatsapp.history.decision', $candidate) }}" onsubmit="return confirm('Stop future CRM tracking for this number?');">@csrf<input type="hidden" name="decision" value="dont_track"><button class="sf-btn-danger">Don't Track</button></form>
        </div>
        @if($candidate->imported_client_id)
            <details class="mt-5 rounded-xl border border-[color:var(--sf-border)] p-4"><summary class="cursor-pointer font-semibold">Stop future tracking and remove imported WhatsApp history</summary><form method="POST" action="{{ route('admin.messaging.whatsapp.history.decision', $candidate) }}" class="mt-4" onsubmit="return confirm('Remove imported WhatsApp history only? Business records such as invoices and bookings remain.');">@csrf<input type="hidden" name="decision" value="dont_track"><input type="hidden" name="remove_imported_history" value="1"><button class="sf-btn-danger">Remove imported message history</button></form></details>
        @endif
    </section>
</div>
@endsection
