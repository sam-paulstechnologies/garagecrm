@extends('layouts.app')

@section('title', 'Lead Import Options')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Capture Setup
            </div>

            <h1 class="sf-page-title mt-3">
                Lead Import Options
            </h1>

            <p class="sf-page-subtitle">
                Choose how you want to bring leads into SayaraForce — Excel upload, website form, Meta Lead Ads, WhatsApp, and future paid channels.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                ← Back to Leads
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- Main Options --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">

        {{-- Excel / CSV --}}
        @if(\Illuminate\Support\Facades\Route::has('admin.leads.import.upload'))
            <a href="{{ route('admin.leads.import.upload') }}"
               class="group rounded-3xl border border-orange-400/20 bg-orange-500/10 p-6 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-orange-400/40 hover:bg-orange-500/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-3xl">📊</div>

                        <h2 class="mt-4 text-xl font-extrabold text-white">
                            Excel / CSV Upload
                        </h2>

                        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                            Upload existing garage leads using the approved import sheet.
                        </p>
                    </div>

                    <span class="text-2xl text-orange-300 transition group-hover:translate-x-1">
                        →
                    </span>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="sf-badge-orange">Available</span>
                    <span class="sf-badge-slate">Bulk Import</span>
                </div>
            </a>
        @endif

        {{-- Custom Website Form --}}
        @if(\Illuminate\Support\Facades\Route::has('admin.leads.custom-form'))
            <a href="{{ route('admin.leads.custom-form') }}"
               class="group rounded-3xl border border-blue-400/20 bg-blue-500/10 p-6 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-blue-400/40 hover:bg-blue-500/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-3xl">🌐</div>

                        <h2 class="mt-4 text-xl font-extrabold text-white">
                            Website Lead Form
                        </h2>

                        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                            Embed a custom SayaraForce lead form on your website.
                        </p>
                    </div>

                    <span class="text-2xl text-blue-300 transition group-hover:translate-x-1">
                        →
                    </span>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="sf-badge-blue">Available</span>
                    <span class="sf-badge-slate">Website</span>
                </div>
            </a>
        @endif

        {{-- Meta --}}
        @if(\Illuminate\Support\Facades\Route::has('admin.leads.import.meta.form'))
            <a href="{{ route('admin.leads.import.meta.form') }}"
               class="group rounded-3xl border border-purple-400/20 bg-purple-500/10 p-6 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-purple-400/40 hover:bg-purple-500/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-3xl">📣</div>

                        <h2 class="mt-4 text-xl font-extrabold text-white">
                            Meta Lead Ads
                        </h2>

                        <p class="mt-2 text-sm font-medium leading-6 text-purple-100/80">
                            Connect Facebook and Instagram lead forms to capture leads.
                        </p>
                    </div>

                    <span class="text-2xl text-purple-300 transition group-hover:translate-x-1">
                        →
                    </span>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full bg-purple-500/10 px-2.5 py-1 text-xs font-extrabold text-purple-300 ring-1 ring-purple-400/20">
                        Available
                    </span>
                    <span class="sf-badge-slate">Facebook / Instagram</span>
                </div>
            </a>
        @else
            <div class="rounded-3xl border border-purple-400/20 bg-purple-500/10 p-6 opacity-70 shadow-xl shadow-black/20">
                <div class="text-3xl">📣</div>

                <h2 class="mt-4 text-xl font-extrabold text-white">
                    Meta Lead Ads
                </h2>

                <p class="mt-2 text-sm font-medium leading-6 text-purple-100/80">
                    Route not enabled yet for Meta form setup.
                </p>

                <div class="mt-5">
                    <span class="sf-badge-slate">Setup Pending</span>
                </div>
            </div>
        @endif

        {{-- WhatsApp --}}
        @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
            <a href="{{ route('admin.whatsapp.settings.edit') }}"
               class="group rounded-3xl border border-green-400/20 bg-green-500/10 p-6 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-green-400/40 hover:bg-green-500/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-3xl">💬</div>

                        <h2 class="mt-4 text-xl font-extrabold text-white">
                            WhatsApp Number
                        </h2>

                        <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                            Configure WhatsApp settings for lead acknowledgement and follow-up.
                        </p>
                    </div>

                    <span class="text-2xl text-green-300 transition group-hover:translate-x-1">
                        →
                    </span>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="sf-badge-green">Available</span>
                    <span class="sf-badge-slate">Messaging</span>
                </div>
            </a>
        @endif

        {{-- Google Coming Soon --}}
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 opacity-70 shadow-xl shadow-black/20">
            <div class="text-3xl">🔎</div>

            <h2 class="mt-4 text-xl font-extrabold text-white">
                Google Leads
            </h2>

            <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
                Google lead integration will be added in a future release.
            </p>

            <div class="mt-5">
                <span class="sf-badge-slate">Coming Soon</span>
            </div>
        </div>

        {{-- Snapchat Coming Soon --}}
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 opacity-70 shadow-xl shadow-black/20">
            <div class="text-3xl">👻</div>

            <h2 class="mt-4 text-xl font-extrabold text-white">
                Snapchat Leads
            </h2>

            <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
                Snapchat lead import will be added after the launch version.
            </p>

            <div class="mt-5">
                <span class="sf-badge-slate">Coming Soon</span>
            </div>
        </div>

    </div>

    {{-- Guidance --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="sf-card lg:col-span-2">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    Recommended Setup Order
                </h2>

                <p class="sf-section-subtitle">
                    For launch, configure your lead sources in this sequence.
                </p>
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="font-extrabold text-white">
                            1. Excel / CSV Upload
                        </div>

                        <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                            Import your existing lead list and customer enquiries.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="font-extrabold text-white">
                            2. Website Form
                        </div>

                        <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                            Add the embed form to your website for new inbound leads.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="font-extrabold text-white">
                            3. WhatsApp
                        </div>

                        <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                            Configure messaging so new leads can be acknowledged quickly.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="font-extrabold text-white">
                            4. Meta Lead Ads
                        </div>

                        <p class="mt-1 text-sm font-medium leading-6 text-slate-400">
                            Connect Facebook and Instagram once campaign lead forms are ready.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-6 shadow-xl shadow-black/20">
            <h3 class="font-extrabold text-orange-300">
                Launch Recommendation
            </h3>

            <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                Start with Excel import and website lead form first. Then connect WhatsApp and Meta once the core flow is tested.
            </p>
        </div>

    </div>

</div>
@endsection