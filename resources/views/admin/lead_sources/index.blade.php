@extends('layouts.app')

@section('title', 'Lead Sources')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden transition hover:-translate-y-1 hover:border-orange-400/30 hover:shadow-orange-500/10';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Lead Capture
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Lead Sources
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Configure how leads enter SayaraForce from WhatsApp, website forms, and Meta lead ads.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Integration Settings
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.settings.launch-setup.edit'))
                <a href="{{ route('admin.settings.launch-setup.edit') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Launch Setup
                </a>
            @endif
        </div>
    </div>

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-blue-400/20 bg-blue-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-blue-200">
            Lead source setup
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            Each source controls how enquiries are captured, tagged, deduplicated, and pushed into the Lead → Client → Booking → Job journey.
        </p>
    </div>

    {{-- Source Cards --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

        {{-- WhatsApp --}}
        <div class="{{ $cardClass }}">
            <div class="border-b border-white/10 bg-green-500/5 px-6 py-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex rounded-full bg-green-500/10 px-3 py-1 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                        WhatsApp • Connected
                    </span>

                    <span class="text-3xl">💬</span>
                </div>
            </div>

            <div class="px-6 py-6">
                <h2 class="text-xl font-black text-white">
                    WhatsApp Conversations
                </h2>

                <p class="mt-3 min-h-[72px] text-sm font-medium leading-6 text-slate-400">
                    Automatically capture and manage leads directly from customer WhatsApp chats.
                </p>

                <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Status
                    </p>

                    <p class="mt-2 text-sm font-extrabold text-green-300">
                        Auto-capture enabled
                    </p>
                </div>

                <a href="{{ route('admin.lead-sources.whatsapp') }}"
                   class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-green-500/20 transition hover:bg-green-700">
                    Configure WhatsApp Flow
                </a>
            </div>
        </div>

        {{-- Website --}}
        <div class="{{ $cardClass }}">
            <div class="border-b border-white/10 bg-blue-500/5 px-6 py-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex rounded-full bg-blue-500/10 px-3 py-1 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                        Website • Ready
                    </span>

                    <span class="text-3xl">🌐</span>
                </div>
            </div>

            <div class="px-6 py-6">
                <h2 class="text-xl font-black text-white">
                    Website Forms
                </h2>

                <p class="mt-3 min-h-[72px] text-sm font-medium leading-6 text-slate-400">
                    Capture leads from contact forms and landing pages in real time.
                </p>

                <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Status
                    </p>

                    <p class="mt-2 text-sm font-extrabold text-blue-300">
                        Forms active
                    </p>
                </div>

                <a href="{{ route('admin.lead-sources.website.index') }}"
                   class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-700">
                    Manage Forms & Webhooks
                </a>
            </div>
        </div>

        {{-- Meta --}}
        <div class="{{ $cardClass }}">
            <div class="border-b border-white/10 bg-cyan-500/5 px-6 py-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex rounded-full bg-cyan-500/10 px-3 py-1 text-xs font-extrabold text-cyan-300 ring-1 ring-cyan-400/20">
                        Meta • Setup Required
                    </span>

                    <span class="text-3xl">📣</span>
                </div>
            </div>

            <div class="px-6 py-6">
                <h2 class="text-xl font-black text-white">
                    Meta Lead Ads
                </h2>

                <p class="mt-3 min-h-[72px] text-sm font-medium leading-6 text-slate-400">
                    Sync Facebook and Instagram Lead Ads automatically into SayaraForce.
                </p>

                <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Status
                    </p>

                    <p class="mt-2 text-sm font-extrabold text-cyan-300">
                        Account not connected
                    </p>
                </div>

                <a href="{{ route('admin.lead-sources.meta') }}"
                   class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-cyan-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-700">
                    Connect Meta Account
                </a>
            </div>
        </div>

    </div>

    {{-- Journey Note --}}
    <div class="mt-8 rounded-3xl border border-orange-400/20 bg-orange-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-orange-200">
            Recommended journey
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
            Start with WhatsApp and Website Forms first. Meta can be connected once the garage is ready to capture paid campaign leads.
        </p>
    </div>
</div>
@endsection