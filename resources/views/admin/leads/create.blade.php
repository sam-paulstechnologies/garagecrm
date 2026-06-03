@extends('layouts.app')

@section('title', 'Create Lead')

@section('content')
@include('admin.leads.create-partials._styles')

<div class="sf-page sf-lead-create-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Management
            </div>

            <h1 class="sf-page-title mt-3">
                Create Lead
            </h1>

            <p class="sf-page-subtitle">
                Add a new lead manually. The system will check for existing active leads using phone or email.
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
            <form action="{{ route('admin.leads.store') }}" method="POST" class="sf-card">
                @csrf

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Lead Information
                    </h2>

                    <p class="sf-section-subtitle">
                        Enter customer details, service requirement, vehicle information, and assignment details.
                    </p>
                </div>

                <div class="sf-card-body">
                    @include('admin.leads.partials.form', ['lead' => null])
                </div>

                <div class="sf-card-footer">
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="sf-btn-primary">
                            Save Lead
                        </button>

                        <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Side Panel --}}
        <div class="space-y-6">

            {{-- Manual Lead Rules --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Manual Lead Rules
                    </h2>

                    <p class="sf-section-subtitle">
                        Keep these checks in mind before creating the lead.
                    </p>
                </div>

                <div class="sf-card-body">
                    <ul class="space-y-3 text-sm text-slate-300">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                1
                            </span>
                            <span>Name is required.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                2
                            </span>
                            <span>Phone or email is required.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                3
                            </span>
                            <span>Duplicate check will happen using phone or email.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                4
                            </span>
                            <span>New leads will be created as open leads.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- WhatsApp Trigger --}}
            <div class="sf-lead-create-info-card border-green-400/20 bg-green-500/10">
                <h3 class="font-extrabold text-green-300">
                    WhatsApp Trigger
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                    If enabled in the form, the system can send a WhatsApp welcome message and start the booking conversation.
                </p>
            </div>

            {{-- Phone Format --}}
            <div class="sf-lead-create-info-card border-blue-400/20 bg-blue-500/10">
                <h3 class="font-extrabold text-blue-300">
                    Recommended Phone Format
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Use country code where possible. Example: 971586934377 instead of 0586934377.
                </p>
            </div>

            {{-- Source Note --}}
            <div class="sf-lead-create-info-card border-orange-400/20 bg-orange-500/10">
                <h3 class="font-extrabold text-orange-300">
                    Source Tracking
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Use the source and campaign fields properly. This helps reporting, segmentation, and follow-up performance.
                </p>
            </div>

        </div>
    </div>

</div>
@endsection
