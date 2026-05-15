@extends('layouts.app')

@section('title', 'Meta Lead Forms')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Meta Lead Source
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Meta Lead Forms
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Connect your Facebook Page and sync Facebook / Instagram lead forms into SayaraForce.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.lead-sources.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to Lead Sources
            </a>

            @if(\Illuminate\Support\Facades\Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Integration Settings
                </a>
            @endif
        </div>
    </div>

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-blue-400/20 bg-blue-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-blue-200">
            Meta lead capture
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            Once connected, Facebook and Instagram lead forms can be synced into the CRM and tagged as Meta leads for follow-up, conversion, and campaign ROI.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Facebook Page Connection
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Connect or manage the Facebook Page used for lead form sync.
                            </p>
                        </div>

                        @if(!$meta)
                            <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-extrabold text-red-300 ring-1 ring-red-400/20">
                                Not Connected
                            </span>
                        @else
                            <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                                Connected
                            </span>
                        @endif
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">

                    @if(!$meta)
                        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-5">
                            <p class="text-sm font-extrabold text-yellow-200">
                                No Facebook Page connected yet.
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-yellow-100/80">
                                Connect a Facebook Page to start syncing Meta lead forms into SayaraForce.
                            </p>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('admin.lead-sources.meta.connect') }}"
                               class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                                Connect Facebook
                            </a>
                        </div>
                    @else
                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-5">
                            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Connected Page
                            </p>

                            <p class="mt-2 text-xl font-black text-white">
                                {{ $meta->page_name }}
                            </p>

                            <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
                                Lead forms are synced from this page.
                            </p>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">

                            <form method="POST" action="{{ route('admin.lead-sources.meta.refresh') }}">
                                @csrf

                                <button class="inline-flex items-center justify-center rounded-xl bg-slate-800 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-slate-700">
                                    Refresh Forms
                                </button>
                            </form>

                            <form method="POST"
                                  action="{{ route('admin.lead-sources.meta.disconnect') }}"
                                  onsubmit="return confirm('Disconnect this Meta page?');">
                                @csrf

                                <button class="inline-flex items-center justify-center rounded-xl border border-red-400/20 bg-red-500/10 px-5 py-3 text-sm font-extrabold text-red-300 transition hover:bg-red-500/20">
                                    Disconnect
                                </button>
                            </form>

                        </div>
                    @endif

                </div>
            </section>

        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        How this works
                    </h2>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <ul class="list-disc list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                        <li>Connect a Facebook Page.</li>
                        <li>Refresh and sync available lead forms.</li>
                        <li>New leads are captured into SayaraForce.</li>
                        <li>Leads are tagged with Meta as the source.</li>
                        <li>Campaign ROI can be tracked later through jobs and invoices.</li>
                    </ul>
                </div>
            </section>

            <section class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-6 shadow-xl shadow-black/20">
                <h2 class="text-lg font-extrabold text-orange-200">
                    Recommended Setup
                </h2>

                <p class="mt-3 text-sm font-medium leading-6 text-orange-100/80">
                    Connect Meta only after WhatsApp and Website forms are tested. This keeps early UAT clean and avoids mixing paid campaign leads with test records.
                </p>
            </section>

        </aside>
    </div>
</div>
@endsection