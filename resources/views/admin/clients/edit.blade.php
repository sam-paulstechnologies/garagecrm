@extends('layouts.app')

@section('title', 'Edit Client')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Client Management
            </div>

            <h1 class="sf-page-title mt-3">
                Edit Client
            </h1>

            <p class="sf-page-subtitle">
                Update client profile, contact details, address, preferences, and CRM information.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.clients.show'))
                <a href="{{ route('admin.clients.show', $client->id) }}" class="sf-btn-secondary">
                    View Client
                </a>
            @endif

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
            <form action="{{ route('admin.clients.update', $client->id) }}" method="POST" class="sf-card">
                @csrf
                @method('PUT')

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Client Information
                    </h2>

                    <p class="sf-section-subtitle">
                        Keep this profile clean so bookings, invoices, reminders, and service history stay accurate.
                    </p>
                </div>

                <div class="sf-card-body space-y-8">

                    {{-- Basic --}}
                    <section class="space-y-5">
                        <div>
                            <h3 class="sf-section-title">
                                Basic Details
                            </h3>

                            <p class="sf-section-subtitle">
                                Primary customer contact information.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="sf-label">
                                    Name <span class="text-red-300">*</span>
                                </label>

                                <input name="name"
                                       type="text"
                                       value="{{ old('name', $client->name) }}"
                                       class="sf-input"
                                       required>

                                @error('name')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="sf-label">
                                    Email
                                </label>

                                <input name="email"
                                       type="email"
                                       value="{{ old('email', $client->email) }}"
                                       class="sf-input">

                                @error('email')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="sf-label">
                                    Phone
                                </label>

                                <input name="phone"
                                       type="text"
                                       value="{{ old('phone', $client->phone) }}"
                                       class="sf-input"
                                       placeholder="971586934377">

                                @error('phone')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="sf-label">
                                    WhatsApp
                                </label>

                                <input name="whatsapp"
                                       type="text"
                                       value="{{ old('whatsapp', $client->whatsapp) }}"
                                       class="sf-input"
                                       placeholder="971586934377">

                                @error('whatsapp')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div class="sf-divider"></div>

                    {{-- Identity --}}
                    <section class="space-y-5">
                        <div>
                            <h3 class="sf-section-title">
                                Identity & Preference
                            </h3>

                            <p class="sf-section-subtitle">
                                Optional profile fields for better segmentation and personalization.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            <div>
                                <label class="sf-label">
                                    Gender
                                </label>

                                <select name="gender" class="sf-select">
                                    <option value="">—</option>

                                    @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('gender', $client->gender) == $val)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('gender')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="sf-label">
                                    Date of Birth
                                </label>

                                <input name="dob"
                                       type="date"
                                       value="{{ old('dob', optional($client->dob)->format('Y-m-d')) }}"
                                       class="sf-input">

                                @error('dob')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                                <label class="flex items-start gap-3">
                                    <input id="is_vip"
                                           name="is_vip"
                                           type="checkbox"
                                           value="1"
                                           @checked(old('is_vip', $client->is_vip))
                                           class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                                    <span>
                                        <span class="block text-sm font-extrabold text-orange-300">
                                            VIP Client
                                        </span>

                                        <span class="mt-1 block text-xs font-medium leading-5 text-orange-100/80">
                                            Mark this client for priority service and reporting.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <div class="sf-divider"></div>

                    {{-- Address / Location --}}
                    <section class="space-y-5">
                        <div>
                            <h3 class="sf-section-title">
                                Address & Location
                            </h3>

                            <p class="sf-section-subtitle">
                                Useful for pickup, drop-off, service area, and customer segmentation.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="sf-label">
                                    Location
                                </label>

                                <input name="location"
                                       type="text"
                                       value="{{ old('location', $client->location) }}"
                                       class="sf-input">

                                @error('location')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="sf-label">
                                    Preferred Channel
                                </label>

                                <select name="preferred_channel" class="sf-select">
                                    <option value="">—</option>

                                    @foreach(['Call', 'WhatsApp', 'Email', 'SMS'] as $opt)
                                        <option value="{{ $opt }}" @selected(old('preferred_channel', $client->preferred_channel) == $opt)>
                                            {{ $opt }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('preferred_channel')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            <div>
                                <label class="sf-label">
                                    Address
                                </label>

                                <input name="address"
                                       type="text"
                                       value="{{ old('address', $client->address) }}"
                                       class="sf-input">
                            </div>

                            <div>
                                <label class="sf-label">
                                    City
                                </label>

                                <input name="city"
                                       type="text"
                                       value="{{ old('city', $client->city) }}"
                                       class="sf-input">
                            </div>

                            <div>
                                <label class="sf-label">
                                    State
                                </label>

                                <input name="state"
                                       type="text"
                                       value="{{ old('state', $client->state) }}"
                                       class="sf-input">
                            </div>

                            <div>
                                <label class="sf-label">
                                    Postal Code
                                </label>

                                <input name="postal_code"
                                       type="text"
                                       value="{{ old('postal_code', $client->postal_code) }}"
                                       class="sf-input">
                            </div>

                            <div>
                                <label class="sf-label">
                                    Country
                                </label>

                                <input name="country"
                                       type="text"
                                       value="{{ old('country', $client->country) }}"
                                       class="sf-input">
                            </div>
                        </div>
                    </section>

                    <div class="sf-divider"></div>

                    {{-- CRM Fields --}}
                    <section class="space-y-5">
                        <div>
                            <h3 class="sf-section-title">
                                CRM Fields
                            </h3>

                            <p class="sf-section-subtitle">
                                Source and status help reporting, filtering, and segmentation.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="sf-label">
                                    Source
                                </label>

                                <input name="source"
                                       type="text"
                                       value="{{ old('source', $client->source) }}"
                                       class="sf-input"
                                       placeholder="website, whatsapp, walk-in, referral">

                                @error('source')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="sf-label">
                                    Status
                                </label>

                                <input name="status"
                                       type="text"
                                       value="{{ old('status', $client->status) }}"
                                       class="sf-input"
                                       placeholder="active">

                                @error('status')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div class="sf-divider"></div>

                    {{-- Notes --}}
                    <section class="space-y-5">
                        <div>
                            <h3 class="sf-section-title">
                                Internal Notes
                            </h3>

                            <p class="sf-section-subtitle">
                                Private notes visible to the garage team.
                            </p>
                        </div>

                        <div>
                            <label class="sf-label">
                                Notes
                            </label>

                            <textarea name="notes"
                                      rows="4"
                                      class="sf-textarea"
                                      placeholder="Add internal notes about this client...">{{ old('notes', $client->notes) }}</textarea>

                            @error('notes')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </section>
                </div>

                <div class="sf-card-footer">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="sf-btn-primary">
                            Update Client
                        </button>

                        @if(Route::has('admin.clients.show'))
                            <a href="{{ route('admin.clients.show', $client->id) }}" class="sf-btn-secondary">
                                Cancel
                            </a>
                        @else
                            <a href="{{ route('admin.clients.index') }}" class="sf-btn-secondary">
                                Cancel
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Side Panel --}}
        <div class="space-y-6">

            {{-- Snapshot --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Client Snapshot
                    </h2>

                    <p class="sf-section-subtitle">
                        Current profile context.
                    </p>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Name
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $client->name ?? 'Unnamed Client' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Contact
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $client->phone ?? $client->whatsapp ?? $client->email ?? 'No contact available' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Source
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $client->source ?? 'N/A' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Status
                        </div>

                        <div class="mt-1">
                            <span class="sf-badge-blue">
                                {{ ucfirst(str_replace('_', ' ', $client->status ?? 'active')) }}
                            </span>
                        </div>
                    </div>

                    @if($client->is_vip ?? false)
                        <div>
                            <span class="sf-badge-orange">
                                VIP Client
                            </span>
                        </div>
                    @endif

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Created
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $client->created_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Guidelines --}}
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
                            <span>Keep phone and WhatsApp numbers with country code where possible.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                2
                            </span>
                            <span>Use source consistently for reporting and segmentation.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                                3
                            </span>
                            <span>VIP flag should be used only for high-priority customers.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Next Step --}}
            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-orange-300">
                    After Updating
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Review vehicles, bookings, invoices, and documents from the client profile page.
                </p>
            </div>

        </div>
    </div>

</div>
@endsection