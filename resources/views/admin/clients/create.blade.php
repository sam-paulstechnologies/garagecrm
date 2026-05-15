@extends('layouts.app')

@section('title', 'Create Client')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Client Management
            </div>

            <h1 class="sf-page-title mt-3">
                Create Client
            </h1>

            <p class="sf-page-subtitle">
                Add a new customer profile with contact details for bookings, vehicles, invoices, and service history.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.clients.index') }}" class="sf-btn-secondary">
                ← Back to Clients
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

    @if ($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.clients.store') }}" class="sf-card">
                @csrf

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Client Information
                    </h2>

                    <p class="sf-section-subtitle">
                        Enter customer details. Phone and email help with follow-ups, reminders, and communication history.
                    </p>
                </div>

                <div class="sf-card-body space-y-5">

                    {{-- Name --}}
                    <div>
                        <label for="name" class="sf-label">
                            Name <span class="text-red-300">*</span>
                        </label>

                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name') }}"
                               class="sf-input"
                               placeholder="Customer name"
                               required>

                        @error('name')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="sf-label">
                            Email <span class="text-red-300">*</span>
                        </label>

                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               class="sf-input"
                               placeholder="customer@example.com"
                               required>

                        @error('email')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="sf-label">
                            Phone <span class="text-red-300">*</span>
                        </label>

                        <input type="text"
                               name="phone"
                               id="phone"
                               value="{{ old('phone') }}"
                               class="sf-input"
                               placeholder="971586934377"
                               required>

                        <p class="sf-help">
                            Use country code where possible.
                        </p>

                        @error('phone')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="sf-card-footer">
                    <div class="flex flex-wrap justify-end gap-2">
                        <a href="{{ route('admin.clients.index') }}" class="sf-btn-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="sf-btn-primary">
                            Submit
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Side Panel --}}
        <div class="space-y-6">

            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Client Setup Notes
                    </h2>
                </div>

                <div class="sf-card-body">
                    <ul class="space-y-3 text-sm text-slate-300">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                1
                            </span>
                            <span>Create the client first.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                2
                            </span>
                            <span>Add vehicles from the client profile page.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                3
                            </span>
                            <span>Bookings, jobs, invoices, and notes will connect to this profile.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-blue-300">
                    Phone Format
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Recommended UAE format: 9715XXXXXXXX. This keeps WhatsApp and SMS workflows clean.
                </p>
            </div>

            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-orange-300">
                    Next Step
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    After creating the client, open the profile and add their first vehicle.
                </p>
            </div>

        </div>
    </div>

</div>
@endsection