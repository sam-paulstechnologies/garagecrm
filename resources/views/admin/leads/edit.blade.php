@extends('layouts.app')

@section('title', 'Edit Lead')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Management
            </div>

            <h1 class="sf-page-title mt-3">
                Edit Lead
            </h1>

            <p class="sf-page-subtitle">
                Update customer details, source, assignment, vehicle information, and lead status.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.show', $lead) }}" class="sf-btn-secondary">
                View Lead
            </a>

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

    @if($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Form --}}
        <div class="lg:col-span-2">
            <form action="{{ route('admin.leads.update', $lead) }}" method="POST" class="sf-card">
                @csrf
                @method('PUT')

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Lead Information
                    </h2>

                    <p class="sf-section-subtitle">
                        Edit the lead details carefully. Changes may impact assignment, reporting, and follow-up flow.
                    </p>
                </div>

                <div class="sf-card-body">
                    @include('admin.leads.partials.form', ['lead' => $lead])
                </div>

                <div class="sf-card-footer">
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="sf-btn-primary">
                            Update Lead
                        </button>

                        <a href="{{ route('admin.leads.show', $lead) }}" class="sf-btn-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Side Panel --}}
        <div class="space-y-6">

            {{-- Lead Snapshot --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Lead Snapshot
                    </h2>

                    <p class="sf-section-subtitle">
                        Current lead context.
                    </p>
                </div>

                <div class="sf-card-body space-y-4 text-sm">

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Name
                        </div>
                        <div class="mt-1 font-extrabold text-white">
                            {{ $lead->name ?? 'Unnamed Lead' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Contact
                        </div>
                        <div class="mt-1 font-bold text-slate-200">
                            {{ $lead->phone ?? $lead->phone_norm ?? $lead->email ?? 'No contact available' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Status
                        </div>
                        <div class="mt-1">
                            <span class="sf-badge-blue">
                                {{ ucfirst(str_replace('_', ' ', $lead->status ?? 'new')) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Source
                        </div>
                        <div class="mt-1 font-bold text-slate-200">
                            {{ $lead->source ?? $lead->leadSource?->name ?? 'Manual' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Created
                        </div>
                        <div class="mt-1 font-bold text-slate-200">
                            {{ $lead->created_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>
                    </div>

                </div>
            </div>

            {{-- Edit Notes --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Edit Guidelines
                    </h2>
                </div>

                <div class="sf-card-body">
                    <ul class="space-y-3 text-sm text-slate-300">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                1
                            </span>
                            <span>Keep phone number with country code where possible.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                2
                            </span>
                            <span>Use status updates only when the lead journey has actually changed.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                3
                            </span>
                            <span>Vehicle details help improve booking and job context.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                4
                            </span>
                            <span>Assignment changes will affect team ownership and follow-ups.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- WhatsApp Note --}}
            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-orange-300">
                    WhatsApp Note
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Editing a lead will not automatically resend WhatsApp messages. Messaging flow should continue from Inbox or automation triggers.
                </p>
            </div>

        </div>
    </div>

</div>
@endsection