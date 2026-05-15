@extends('layouts.app')

@section('title', 'Company Settings')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Company Settings
            </div>

            <h1 class="sf-page-title mt-3">
                Company Profile
            </h1>

            <p class="sf-page-subtitle">
                Manage company details, contact information, logo, and current subscription plan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="sf-btn-secondary">
                    ← Back to Dashboard
                </a>
            @endif
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

    {{-- Current Plan --}}
    @include('admin.company.partials.current-plan')

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Company Form --}}
        <div class="lg:col-span-2">
            <form method="POST"
                  action="{{ route('admin.company.update') }}"
                  enctype="multipart/form-data"
                  class="sf-card">
                @csrf
                @method('PUT')

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Company Information
                    </h2>

                    <p class="sf-section-subtitle">
                        These details may be used across invoices, customer communication, and account settings.
                    </p>
                </div>

                <div class="sf-card-body space-y-6">

                    {{-- Company Name --}}
                    <div>
                        <label class="sf-label">
                            Company Name <span class="text-red-300">*</span>
                        </label>

                        <input type="text"
                               name="name"
                               value="{{ old('name', $company->name) }}"
                               required
                               class="sf-input"
                               placeholder="Company name">

                        @error('name')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="sf-label">
                            Email
                        </label>

                        <input type="email"
                               name="email"
                               value="{{ old('email', $company->email) }}"
                               class="sf-input"
                               placeholder="company@example.com">

                        @error('email')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="sf-label">
                            Phone
                        </label>

                        <input type="text"
                               name="phone"
                               value="{{ old('phone', $company->phone) }}"
                               class="sf-input"
                               placeholder="9715XXXXXXXX">

                        @error('phone')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div>
                        <label class="sf-label">
                            Address
                        </label>

                        <textarea name="address"
                                  rows="4"
                                  class="sf-textarea"
                                  placeholder="Company address">{{ old('address', $company->address) }}</textarea>

                        @error('address')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Logo --}}
                    <div>
                        <label class="sf-label">
                            Logo
                        </label>

                        @if($company->logo)
                            <div class="mb-4 rounded-3xl border border-white/10 bg-slate-950/60 p-4">
                                <div class="mb-2 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                    Current Logo
                                </div>

                                <img src="{{ asset('storage/' . $company->logo) }}"
                                     alt="Company Logo"
                                     class="h-20 max-w-full rounded-2xl border border-white/10 bg-white object-contain p-2">
                            </div>
                        @endif

                        <input type="file"
                               name="logo"
                               accept="image/*"
                               class="block w-full rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">

                        <p class="sf-help">
                            Upload a clear PNG, JPG, or WEBP logo.
                        </p>

                        @error('logo')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="sf-card-footer">
                    <div class="flex flex-wrap justify-end gap-2">
                        @if(Route::has('admin.dashboard'))
                            <a href="{{ route('admin.dashboard') }}" class="sf-btn-secondary">
                                Cancel
                            </a>
                        @endif

                        <button type="submit" class="sf-btn-primary">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Side Panel --}}
        <div class="space-y-6">

            {{-- Company Snapshot --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Company Snapshot
                    </h2>

                    <p class="sf-section-subtitle">
                        Current account details.
                    </p>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Name
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $company->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Email
                        </div>

                        <div class="mt-1 break-words font-bold text-slate-200">
                            {{ $company->email ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Phone
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $company->phone ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Address
                        </div>

                        <div class="mt-1 whitespace-pre-line font-medium leading-6 text-slate-300">
                            {{ $company->address ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-orange-300">
                    Setup Note
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Keep company details accurate because they may appear on invoices, customer messages, and internal reports.
                </p>
            </div>

            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-blue-300">
                    Logo Tip
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Use a transparent PNG logo where possible for cleaner invoice and dashboard display.
                </p>
            </div>

        </div>
    </div>

</div>
@endsection