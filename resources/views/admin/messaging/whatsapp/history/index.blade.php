@extends('layouts.app')

@section('title', 'WhatsApp History Review')

@section('content')
@php
    $limitLabel = $quota['limit'] === null ? 'Custom / fair use' : number_format($quota['limit']);
    $remaining = $quota['remaining'];
    $locked = $batch ? $batch->candidates()->where('intelligence_status', 'locked')->count() : 0;
    $stillInHistory = $batch ? max(0, $batch->contacts_discovered - $quota['used']) : 0;
    $modeLabel = str($intelligence->mode ?: 'preview')->replace('_', ' ')->title();
    $classificationLabels = [
        'likely_customer' => 'Likely Customer',
        'possible_personal' => 'Possible Personal',
        'possible_colleague' => 'Possible Colleague / Staff',
        'unknown' => 'Unknown',
    ];
@endphp

<div class="sf-page">
    @foreach(['success' => 'sf-alert-success', 'warning' => 'sf-alert-warning', 'error' => 'sf-alert-danger'] as $key => $class)
        @if(session($key))<div class="{{ $class }} mb-5">{{ session($key) }}</div>@endif
    @endforeach

    <header class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="sf-badge-orange uppercase tracking-[.12em]">Coexistence history intelligence</div>
            <h1 class="sf-page-title mt-4">Build a clean customer database</h1>
            <p class="sf-page-subtitle max-w-3xl">SayaraForce stages supported WhatsApp history here. Nothing becomes a Client, Lead, Opportunity or Booking until you review it.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.messaging.whatsapp.index') }}" class="sf-btn-secondary min-h-11">WhatsApp setup</a>
            @if($company->whatsapp_coexistence_enabled && $company->is_whatsapp_active)
                <form method="POST" action="{{ route('admin.messaging.whatsapp.history.sync') }}">@csrf
                    <button class="sf-btn-primary min-h-11">Discover WhatsApp history</button>
                </form>
            @endif
        </div>
    </header>

    @unless($batch)
        <section class="sf-card p-8 text-center">
            <h2 class="sf-section-title text-2xl">No history has been staged</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-[color:var(--sf-muted)]">Connect an eligible WhatsApp Business App number using coexistence, then request history discovery. New live messages remain separate.</p>
        </section>
    @else
        <section class="sf-card overflow-hidden">
            <div class="sf-card-header flex flex-col gap-4 py-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="{{ in_array($batch->status, ['completed'], true) ? 'sf-badge-green' : 'sf-badge-yellow' }}">{{ str($batch->status)->replace('_', ' ')->title() }}</div>
                    <h2 class="sf-section-title mt-3 text-2xl">{{ number_format($batch->contacts_discovered) }} contacts discovered</h2>
                    <p class="sf-section-subtitle mt-1 text-sm">{{ number_format($quota['used']) }} / {{ $limitLabel }} unique contacts deeply analysed · {{ $modeLabel }} retention intelligence</p>
                </div>
                <div class="text-left lg:text-right">
                    <div class="text-sm font-semibold text-[color:var(--sf-text-strong)]">{{ $remaining === null ? 'Custom / fair-use allowance' : number_format($remaining).' analysis slots remaining' }}</div>
                    <div class="text-sm text-[color:var(--sf-muted)]">{{ number_format($stillInHistory) }} discovered contacts remain in history</div>
                </div>
            </div>
            <div class="grid gap-3 p-6 sm:grid-cols-2 xl:grid-cols-5">
                @foreach([
                    ['Likely Customers', $breakdown['likely_customer'] ?? 0, 'sf-badge-green'],
                    ['Possible Colleagues', $breakdown['possible_colleague'] ?? 0, 'sf-badge-blue'],
                    ['Possible Personal', $breakdown['possible_personal'] ?? 0, 'sf-badge-yellow'],
                    ['Unknown', $breakdown['unknown'] ?? 0, 'sf-badge-gray'],
                    ['High Retention', $breakdown['high_retention'] ?? 0, 'sf-badge-red'],
                ] as [$label, $value, $badge])
                    <div class="sf-mini-card p-4"><span class="{{ $badge }}">{{ $label }}</span><div class="mt-3 text-2xl font-extrabold text-[color:var(--sf-text-strong)]">{{ number_format($value) }}</div></div>
                @endforeach
            </div>
            @if($locked > 0 || ($remaining !== null && $remaining < 1 && $stillInHistory > 0))
                <div class="mx-6 mb-6 rounded-xl border border-[color:var(--sf-orange)]/30 bg-[color:var(--sf-orange)]/10 p-4 text-sm">
                    <strong>{{ number_format($batch->contacts_discovered) }} WhatsApp contacts discovered.</strong>
                    {{ number_format($quota['used']) }} have received deep intelligence. Upgrade to expand the cumulative unique-contact allowance; Track and Don't Track never change this meter.
                    @if(Route::has('admin.billing.index'))<a href="{{ route('admin.billing.index') }}" class="ml-2 font-semibold text-[color:var(--sf-orange)]">Compare plans</a>@endif
                </div>
            @endif
        </section>

        <section class="sf-card mt-6 p-5">
            <form method="GET" class="grid gap-3 md:grid-cols-[1fr_240px_auto]">
                <input class="sf-input" name="q" value="{{ request('q') }}" placeholder="Search name or phone">
                <select class="sf-input" name="filter">
                    @foreach([
                        'all' => 'All', 'pending' => 'Pending review', 'likely_customer' => 'Likely customers',
                        'possible_personal' => 'Possible personal', 'possible_colleague' => 'Possible colleagues',
                        'unknown' => 'Unknown', 'high_retention' => 'High retention', 'medium_retention' => 'Medium retention',
                        'track' => 'Track', 'dont_track' => "Don't Track", 'locked' => 'Locked by plan',
                    ] as $value => $label)<option value="{{ $value }}" @selected(request('filter', 'all') === $value)>{{ $label }}</option>@endforeach
                </select>
                <button class="sf-btn-secondary">Filter</button>
            </form>
        </section>

        <form method="POST" action="{{ route('admin.messaging.whatsapp.history.decision.bulk', $batch) }}" class="mt-6">
            @csrf
            <div class="mb-3 flex flex-wrap items-center gap-3">
                <button formaction="{{ route('admin.messaging.whatsapp.history.analyse.bulk', $batch) }}" class="sf-btn-secondary min-h-10">Analyse selected</button>
                <button name="decision" value="track" class="sf-btn-primary min-h-10">Track selected</button>
                <button name="decision" value="dont_track" class="sf-btn-danger min-h-10">Don't Track selected</button>
                <span class="text-xs text-[color:var(--sf-muted)]">Analysis is idempotent and consumes a unique-contact allowance only once.</span>
            </div>
            <div class="space-y-4">
                @forelse($candidates as $candidate)
                    @php
                        $analysed = in_array($candidate->intelligence_status, ['analysed', 'deterministic'], true);
                        $isLocked = $candidate->intelligence_status === 'locked' || (!$analysed && $remaining !== null && $remaining < 1);
                    @endphp
                    <article class="sf-card p-5">
                        <div class="grid gap-4 lg:grid-cols-[auto_minmax(180px,.8fr)_minmax(180px,1fr)_minmax(180px,1fr)_auto] lg:items-center">
                            <input type="checkbox" name="candidates[]" value="{{ $candidate->public_id }}" aria-label="Select {{ $candidate->display_name ?: 'contact' }}">
                            <div>
                                <div class="font-heading text-lg font-semibold text-[color:var(--sf-text-strong)]">{{ $candidate->display_name ?: 'Unnamed WhatsApp contact' }}</div>
                                <div class="mt-1 font-mono text-sm text-[color:var(--sf-muted)]">{{ $candidate->phone_e164 }}</div>
                                <div class="mt-2 text-xs text-[color:var(--sf-muted)]">{{ number_format($candidate->message_count) }} messages · Last chat {{ $candidate->last_message_at?->diffForHumans() ?: 'unknown' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--sf-muted)]">Suggestion</div>
                                <div class="mt-2 font-semibold text-[color:var(--sf-text-strong)]">{{ $analysed ? ($classificationLabels[$candidate->classification] ?? 'Unknown') : ($isLocked ? 'Detailed intelligence locked' : 'Ready to analyse') }}</div>
                                @if($analysed)<p class="mt-1 text-sm leading-5 text-[color:var(--sf-muted)]">{{ $candidate->classification_reason }}</p>@endif
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--sf-muted)]">Retention</div>
                                <div class="mt-2 font-semibold text-[color:var(--sf-text-strong)]">{{ $analysed ? str($candidate->retention_level)->title().' potential' : ($isLocked ? 'Locked' : 'Pending') }}</div>
                                @if($analysed)<p class="mt-1 text-sm leading-5 text-[color:var(--sf-muted)]">{{ $candidate->retention_reason }}</p>@endif
                            </div>
                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                @unless($analysed || $isLocked || $candidate->review_decision === 'dont_track')
                                    <button formaction="{{ route('admin.messaging.whatsapp.history.analyse', $candidate) }}" class="sf-btn-secondary min-h-10">Analyse</button>
                                @endunless
                                <a href="{{ route('admin.messaging.whatsapp.history.show', $candidate) }}" class="sf-btn-secondary min-h-10">Review</a>
                                <span class="{{ $candidate->review_decision === 'track' ? 'sf-badge-green' : ($candidate->review_decision === 'dont_track' ? 'sf-badge-red' : 'sf-badge-yellow') }}">{{ str($candidate->review_decision)->replace('_', ' ')->title() }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="sf-card p-8 text-center text-sm text-[color:var(--sf-muted)]">No contacts match this filter.</div>
                @endforelse
            </div>
        </form>
        <div class="mt-5">{{ $candidates->links() }}</div>

        @if($batch->track_selected > 0)
            <section class="sf-card mt-6 flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="sf-section-title">Import approved contacts</h2><p class="mt-2 text-sm text-[color:var(--sf-muted)]">Creates or matches Clients and imports historical messages only. No Lead, Opportunity, Booking or automation is created.</p></div>
                <form method="POST" action="{{ route('admin.messaging.whatsapp.history.import', $batch) }}" onsubmit="return confirm('Import only the contacts marked Track?');">@csrf<button class="sf-btn-primary min-h-11">Import tracked contacts</button></form>
            </section>
        @endif
    @endunless
</div>
@endsection
