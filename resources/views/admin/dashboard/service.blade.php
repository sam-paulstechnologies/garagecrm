@extends('layouts.app')

@section('title', $serviceMode ? 'Service Dashboard' : 'Free Overview')

@section('content')
@php($routePrefix = auth()->user()?->role === 'manager' ? 'manager' : 'admin')
<div class="mx-auto max-w-7xl space-y-7 px-4 py-8 sm:px-6">
    <header class="rounded-3xl border border-white/10 bg-slate-950/80 p-7 shadow-xl">
        <p class="text-xs font-black uppercase tracking-[0.24em] text-orange-300">{{ strtoupper(str_replace('_', ' ', $planCode)) }} · {{ $serviceMode ? 'MANAGE' : 'SEE' }}</p>
        <h1 class="mt-3 text-3xl font-black text-white">{{ $serviceMode ? 'Service manager dashboard' : 'See what your garage is missing' }}</h1>
        <p class="mt-3 max-w-3xl text-slate-300">
            {{ $serviceMode ? 'Keep enquiries, follow-ups and confirmed bookings moving without marketing noise.' : 'Capture customers and enquiries, monitor your first AI conversations, and upgrade when you need operational reminders.' }}
        </p>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['New enquiries', $metrics['new_enquiries'], $routePrefix.'.leads.index'],
            ['Needs follow-up', $metrics['needs_follow_up'], $routePrefix.'.leads.index'],
            ['Qualified', $metrics['qualified'], $routePrefix.'.opportunities.index'],
            ['Upcoming bookings', $metrics['upcoming_bookings'], $routePrefix.'.bookings.index'],
            ['Missed follow-ups', $metrics['missed_follow_ups'], $routePrefix.'.leads.index'],
            ['Due for service', $metrics['due_for_service'], $routePrefix.'.leads.index'],
        ] as [$label, $value, $routeName])
            <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}" class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:border-orange-400/40">
                <p class="text-sm font-bold text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-3xl font-black text-white">{{ number_format($value) }}</p>
            </a>
        @endforeach
    </section>

    @if(Route::has($routePrefix.'.notifications.index'))
        <a href="{{ route($routePrefix.'.notifications.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-white/10 px-5 py-3 font-bold text-white">
            Open notification centre
        </a>
    @endif

    <section class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-6">
            <p class="text-sm font-bold text-slate-400">Conversation response</p>
            <p class="mt-2 text-3xl font-black text-white">{{ number_format($metrics['response_rate'], 1) }}%</p>
            <p class="mt-2 text-xs text-slate-500">Based only on this tenant's inbound and outbound conversation records.</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-6">
            <p class="text-sm font-bold text-slate-400">Lead to booking</p>
            <p class="mt-2 text-3xl font-black text-white">{{ number_format($metrics['booking_conversion'], 1) }}%</p>
            <p class="mt-2 text-xs text-slate-500">Current subscription period; no marketing attribution is included.</p>
        </div>
        <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-6">
            <p class="text-sm font-bold text-orange-200">AI monitored customers</p>
            <p class="mt-2 text-3xl font-black text-white">{{ number_format($metrics['ai_used']) }} / {{ number_format($metrics['ai_limit']) }}</p>
            <p class="mt-2 text-xs text-orange-100/70">Raw enquiries continue to be captured after the AI allowance is reached.</p>
        </div>
    </section>

    @unless($serviceMode)
        <section class="rounded-3xl border border-orange-400/20 bg-gradient-to-r from-orange-500/15 to-slate-950 p-7">
            <h2 class="text-2xl font-black text-white">You've seen what you're missing. Now don't miss another booking.</h2>
            <p class="mt-2 text-slate-300">Service adds operational reminders, follow-up controls and a focused service-manager dashboard.</p>
            @if(Route::has('admin.billing.index'))
                <a href="{{ route('admin.billing.index') }}" class="mt-5 inline-flex rounded-xl bg-orange-500 px-5 py-3 font-bold text-white">View Service</a>
            @endif
        </section>
    @endunless
</div>
@endsection
