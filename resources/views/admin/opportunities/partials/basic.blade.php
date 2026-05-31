@extends('layouts.app')

@section('title', 'Create Opportunity')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Sales Pipeline
            </div>

            <h1 class="sf-page-title mt-3">
                Create Opportunity
            </h1>

            <p class="sf-page-subtitle">
                Create a new sales opportunity and capture client, lead, vehicle, service, value, follow-up, and conversion details.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.opportunities.index'))
                <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">
                    ← Back to Opportunities
                </a>
            @endif
        </div>
    </div>

    {{-- Errors --}}
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

    {{-- Form --}}
    <form action="{{ route('admin.opportunities.store') }}" method="POST" id="opportunityForm" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main Column --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- Basic Details --}}
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Basic Details
                        </h2>

                        <p class="sf-section-subtitle">
                            Link this opportunity to a client and optional lead.
                        </p>
                    </div>

                    <div class="sf-card-body">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            {{-- Client --}}
                            <div>
                                <label for="client_id" class="sf-label">
                                    Client <span class="text-red-300">*</span>
                                </label>

                                <div class="flex gap-2">
                                    <select id="client_id"
                                            name="client_id"
                                            class="client-select sf-select w-full"
                                            required>
                                        <option value="">-- Select Client --</option>

                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                                {{ $client->name }} - {{ $client->phone }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="button"
                                            onclick="openClientModal()"
                                            class="sf-btn-primary shrink-0 px-4">
                                        +
                                    </button>
                                </div>

                                @error('client_id')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Lead --}}
                            <div>
                                <label for="lead_id" class="sf-label">
                                    Lead
                                </label>

                                <select name="lead_id" id="lead_id" class="sf-select">
                                    <option value="">-- None --</option>

                                    @foreach($leads as $lead)
                                        <option value="{{ $lead->id }}" @selected(old('lead_id') == $lead->id)>
                                            {{ $lead->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('lead_id')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Title --}}
                            <div class="md:col-span-2">
                                <label class="sf-label">
                                    Title <span class="text-red-300">*</span>
                                </label>

                                <input type="text"
                                       name="title"
                                       value="{{ old('title') }}"
                                       required
                                       class="sf-input"
                                       placeholder="Example: Ronald - Tinting">

                                @error('title')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Pipeline --}}
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Pipeline Details
                        </h2>

                        <p class="sf-section-subtitle">
                            Set stage, priority, owner, value, duration, and follow-up.
                        </p>
                    </div>

                    <div class="sf-card-body">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            {{-- Stage --}}
                            <div>
                                <label class="sf-label">
                                    Stage <span class="text-red-300">*</span>
                                </label>

                                <select name="stage" required class="sf-select">
                                    @foreach(['new','attempting_contact','appointment','offer','closed_won','closed_lost'] as $stage)
                                        <option value="{{ $stage }}" @selected(old('stage') == $stage)>
                                            {{ ucfirst(str_replace('_', ' ', $stage)) }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('stage')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Priority --}}
                            <div>
                                <label class="sf-label">
                                    Priority
                                </label>

                                <select name="priority" class="sf-select">
                                    @foreach(['low','medium','high'] as $priority)
                                        <option value="{{ $priority }}" @selected(old('priority') == $priority)>
                                            {{ ucfirst($priority) }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('priority')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Assigned To --}}
                            <div>
                                <label class="sf-label">
                                    Assigned To (User ID)
                                </label>

                                <input type="number"
                                       name="assigned_to"
                                       value="{{ old('assigned_to') }}"
                                       class="sf-input"
                                       placeholder="User ID">

                                @error('assigned_to')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Estimated Value --}}
                            <div>
                                <label class="sf-label">
                                    Estimated Value (AED)
                                </label>

                                <input type="number"
                                       name="estimated_value"
                                       value="{{ old('estimated_value') }}"
                                       class="sf-input"
                                       placeholder="0.00">

                                @error('estimated_value')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Expected Duration --}}
                            <div>
                                <label class="sf-label">
                                    Expected Duration (Days)
                                </label>

                                <input type="number"
                                       name="expected_duration"
                                       value="{{ old('expected_duration') }}"
                                       class="sf-input"
                                       placeholder="Example: 3">

                                @error('expected_duration')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Next Follow-Up --}}
                            <div>
                                <label class="sf-label">
                                    Next Follow-Up
                                </label>

                                <input type="date"
                                       name="next_follow_up"
                                       value="{{ old('next_follow_up') }}"
                                       class="sf-input">

                                @error('next_follow_up')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Vehicle --}}
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Vehicle Details
                        </h2>

                        <p class="sf-section-subtitle">
                            Capture the customer vehicle linked to this opportunity.
                        </p>
                    </div>

                    <div class="sf-card-body">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            {{-- Vehicle Make --}}
                            <div>
                                <label class="sf-label">
                                    Vehicle Make
                                </label>

                                <select name="vehicle_make_id" id="vehicle_make_id" class="sf-select">
                                    <option value="">-- Select Make --</option>

                                    @foreach($makes as $make)
                                        <option value="{{ $make->id }}" @selected(old('vehicle_make_id') == $make->id)>
                                            {{ $make->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <input type="text"
                                       name="vehicle_make_other"
                                       placeholder="Other make"
                                       value="{{ old('vehicle_make_other') }}"
                                       class="sf-input mt-3">

                                @error('vehicle_make_id')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror

                                @error('vehicle_make_other')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Vehicle Model --}}
                            <div>
                                <label class="sf-label">
                                    Vehicle Model
                                </label>

                                <select name="vehicle_model_id" id="vehicle_model_id" class="sf-select">
                                    <option value="">-- Select Model --</option>

                                    @foreach($models as $model)
                                        <option value="{{ $model->id }}" @selected(old('vehicle_model_id') == $model->id)>
                                            {{ $model->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <input type="text"
                                       name="vehicle_model_other"
                                       placeholder="Other model"
                                       value="{{ old('vehicle_model_other') }}"
                                       class="sf-input mt-3">

                                @error('vehicle_model_id')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror

                                @error('vehicle_model_other')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Services Opted --}}
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Services Opted
                        </h2>

                        <p class="sf-section-subtitle">
                            Select the services requested by the customer.
                        </p>
                    </div>

                    <div class="sf-card-body">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach(['Oil Change', 'Battery Check', 'Transmission Service', 'Car Wash', 'Polishing', 'Emissions Test', 'AC Repair', 'Detailing', 'Interior Cleaning', 'Registration Renewal', 'Suspension Work', 'Tinting', 'Vehicle Inspection', 'Other'] as $service)
                                <label class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox"
                                               name="services[]"
                                               value="{{ $service }}"
                                               @checked(is_array(old('services')) && in_array($service, old('services')))
                                               class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                                        <span class="text-sm font-bold text-slate-200">
                                            {{ $service }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @error('services')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Notes & Close Reason
                        </h2>
                    </div>

                    <div class="sf-card-body space-y-5">
                        <div>
                            <label class="sf-label">
                                Notes
                            </label>

                            <textarea name="notes"
                                      rows="3"
                                      class="sf-textarea"
                                      placeholder="Add notes about customer requirement, quote, service preference, or follow-up...">{{ old('notes') }}</textarea>

                            @error('notes')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Close Reason
                            </label>

                            <textarea name="close_reason"
                                      rows="2"
                                      class="sf-textarea"
                                      placeholder="Only required when opportunity is closed lost...">{{ old('close_reason') }}</textarea>

                            @error('close_reason')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Score & Converted --}}
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Score & Conversion
                        </h2>
                    </div>

                    <div class="sf-card-body">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="sf-label">
                                    Opportunity Score
                                </label>

                                <input type="number"
                                       name="opportunity_score"
                                       value="{{ old('opportunity_score') }}"
                                       class="sf-input"
                                       placeholder="0 - 100">

                                @error('opportunity_score')
                                    <div class="sf-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox"
                                           name="converted_to_job"
                                           value="1"
                                           @checked(old('converted_to_job'))
                                           class="mt-1 rounded border-white/10 bg-slate-950 text-green-500 shadow-sm focus:ring-green-400">

                                    <span>
                                        <span class="block text-sm font-extrabold text-green-300">
                                            Converted to Job/Booking
                                        </span>

                                        <span class="mt-1 block text-xs font-medium leading-5 text-green-100/80">
                                            Mark this only when the opportunity has been confirmed.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="sf-card">
                    <div class="sf-card-body">
                        <div class="flex flex-wrap justify-end gap-2">
                            @if(Route::has('admin.opportunities.index'))
                                <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">
                                    Cancel
                                </a>
                            @endif

                            <button type="submit" class="sf-btn-primary">
                                Create Opportunity
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Side Column --}}
            <div class="space-y-6">

                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Pipeline Notes
                        </h2>
                    </div>

                    <div class="sf-card-body">
                        <ul class="space-y-3 text-sm text-slate-300">
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">1</span>
                                <span>Select the correct client before creating the opportunity.</span>
                            </li>

                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">2</span>
                                <span>Use services to understand demand and future campaigns.</span>
                            </li>

                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">3</span>
                                <span>Close reason helps improve retargeting and lost-lead analysis.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                    <h3 class="font-extrabold text-blue-300">
                        Vehicle Tip
                    </h3>

                    <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                        Select make and model from the list where possible. Use the “Other” fields only when the vehicle is missing from master data.
                    </p>
                </div>

                <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                    <h3 class="font-extrabold text-orange-300">
                        Quick Client Add
                    </h3>

                    <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                        Use the + button beside Client to add a new client without leaving this page.
                    </p>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- Add Client Modal --}}
<div id="clientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-xl rounded-3xl border border-white/10 bg-slate-950 p-6 shadow-2xl shadow-black/50">
        <button onclick="closeClientModal()"
                class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-red-500/10 hover:text-red-300">
            ✕
        </button>

        <div class="mb-5">
            <h2 class="sf-section-title">
                Add New Client
            </h2>

            <p class="sf-section-subtitle">
                Create a client quickly and attach them to this opportunity.
            </p>
        </div>

        <form id="newClientForm">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <input type="text" name="name" placeholder="Name" required class="sf-input">
                <input type="text" name="phone" placeholder="Phone" required class="sf-input">
                <input type="email" name="email" placeholder="Email" class="sf-input">
                <input type="text" name="whatsapp" placeholder="WhatsApp" class="sf-input">
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="closeClientModal()" class="sf-btn-secondary">
                    Cancel
                </button>

                <button type="submit" class="sf-btn-primary">
                    Save Client
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .sf-page .sf-card,
    .sf-page .rounded-3xl {
        border-color: #1e293b;
    }

    .select2-container--default .select2-selection--single {
        height: 42px;
        border-radius: 0.75rem;
        border-color: rgba(255, 255, 255, 0.1);
        background: rgba(2, 6, 23, 0.7);
        color: #e2e8f0;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #e2e8f0;
        line-height: 42px;
        padding-left: 12px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px;
    }

    .select2-dropdown {
        background: #020617;
        border-color: rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: #f97316;
        color: white;
    }

    .select2-container--default .select2-results__option[aria-selected=true] {
        background: rgba(249, 115, 22, 0.25);
        color: white;
    }

    .select2-search__field {
        background: #020617;
        color: #e2e8f0;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    html[data-theme="light"] .sf-page .border-white\/10 {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-page .bg-slate-950,
    html[data-theme="light"] .sf-page .bg-slate-950\/60 {
        background-color: #ffffff !important;
    }

    html[data-theme="light"] .sf-page .text-white {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-page .text-slate-300,
    html[data-theme="light"] .sf-page .text-slate-200 {
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-page .text-orange-300 {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-page .text-orange-100\/80,
    html[data-theme="light"] .sf-page .text-orange-100\/70 {
        color: #9a3412 !important;
    }

    html[data-theme="light"] .sf-page .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-page .text-blue-100\/80 {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-page .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-page .text-green-100\/80 {
        color: #166534 !important;
    }

    html[data-theme="light"] .select2-container--default .select2-selection--single {
        border-color: #cbd5e1;
        background: #ffffff;
        color: #0f172a;
    }

    html[data-theme="light"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #0f172a;
    }

    html[data-theme="light"] .select2-dropdown,
    html[data-theme="light"] .select2-search__field {
        background: #ffffff;
        border-color: #cbd5e1 !important;
        color: #0f172a;
    }

    html[data-theme="light"] .select2-container--default .select2-results__option[aria-selected=true] {
        background: #fff7ed;
        color: #9a3412;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
    $('#client_id').select2({ width: '100%' });

    $('#vehicle_make_id').on('change', function () {
        let makeId = $(this).val();

        if (!makeId) {
            $('#vehicle_model_id').html('<option value="">-- Select Model --</option>');
            return;
        }

        $('#vehicle_model_id').html('<option value="">Loading...</option>');

        fetch(`/admin/models/by-make/${makeId}`)
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">-- Select Model --</option>';

                data.forEach(model => {
                    options += `<option value="${model.id}">${model.name}</option>`;
                });

                $('#vehicle_model_id').html(options);
            });
    });

    $('#newClientForm').on('submit', function (e) {
        e.preventDefault();

        const data = new FormData(this);

        fetch("{{ route('admin.clients.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: data
        })
        .then(res => res.json())
        .then(response => {
            if (response.id) {
                const select = $('#client_id');
                const newOption = new Option(response.name + ' - ' + response.phone, response.id, true, true);

                select.append(newOption).trigger('change.select2');
                closeClientModal();

                this.reset();
            } else {
                alert('Client creation failed.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Something went wrong.');
        });
    });
});

function openClientModal() {
    const modal = document.getElementById('clientModal');

    if (!modal) return;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeClientModal() {
    const modal = document.getElementById('clientModal');

    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endpush

@endsection
