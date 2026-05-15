@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Revenue Tracking
            </div>

            <h1 class="sf-page-title mt-3">
                Create Invoice
            </h1>

            <p class="sf-page-subtitle">
                Capture invoice number and amount for revenue and ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                All Invoices
            </a>

            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Open Jobs
            </a>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info Note --}}
    <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-orange-300">
            ROI-focused invoice record
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
            SayaraForce does not need itemized billing here. We only capture invoice number, amount, status, client and linked job so revenue can be connected later to lead source, Meta campaigns and WhatsApp campaigns.
        </p>
    </div>

    <form method="POST"
          action="{{ route('admin.invoices.store') }}"
          class="space-y-6">

        @csrf

        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    Invoice Information
                </h2>

                <p class="sf-section-subtitle">
                    Create a lightweight invoice record for revenue and attribution.
                </p>
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- Client --}}
                    <div>
                        <label class="sf-label">
                            Client <span class="text-red-300">*</span>
                        </label>

                        <select id="client_id"
                                name="client_id"
                                class="sf-select"
                                required>
                            <option value="">Select Client</option>

                            @foreach($clients as $client)
                                <option value="{{ $client->id }}"
                                        {{ (int) old('client_id') === (int) $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                    @if($client->phone)
                                        — {{ $client->phone }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @error('client_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Linked Job --}}
                    <div>
                        <label class="sf-label">
                            Linked Job
                        </label>

                        <select id="job_id"
                                name="job_id"
                                class="sf-select">
                            <option value="">No linked job</option>

                            @foreach($jobs ?? [] as $job)
                                <option value="{{ $job->id }}"
                                        data-client-id="{{ $job->client_id }}"
                                        {{ (int) old('job_id') === (int) $job->id ? 'selected' : '' }}>
                                    {{ $job->job_code ?? 'Job #' . $job->id }}
                                    — {{ ucwords(str_replace('_', ' ', $job->status ?? '')) }}
                                </option>
                            @endforeach
                        </select>

                        <p class="sf-help">
                            Link a job to make this invoice usable for campaign ROI attribution.
                        </p>

                        @error('job_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Invoice Number --}}
                    <div>
                        <label class="sf-label">
                            Invoice Number <span class="text-red-300">*</span>
                        </label>

                        <input type="text"
                               name="number"
                               value="{{ old('number') }}"
                               class="sf-input"
                               placeholder="Example: INV-1001"
                               required>

                        @error('number')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label class="sf-label">
                            Invoice Amount <span class="text-red-300">*</span>
                        </label>

                        <input type="number"
                               name="amount"
                               value="{{ old('amount') }}"
                               min="1"
                               step="0.01"
                               class="sf-input"
                               placeholder="Example: 7000"
                               required>

                        @error('amount')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="sf-label">
                            Status <span class="text-red-300">*</span>
                        </label>

                        <select name="status"
                                class="sf-select"
                                required>
                            <option value="pending" {{ old('status', 'paid') === 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>

                            <option value="paid" {{ old('status', 'paid') === 'paid' ? 'selected' : '' }}>
                                Paid
                            </option>

                            <option value="overdue" {{ old('status') === 'overdue' ? 'selected' : '' }}>
                                Overdue
                            </option>
                        </select>

                        <p class="sf-help">
                            Paid invoices are included in ROI revenue.
                        </p>

                        @error('status')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Currency --}}
                    <div>
                        <label class="sf-label">
                            Currency
                        </label>

                        <input type="text"
                               name="currency"
                               value="{{ old('currency', 'AED') }}"
                               class="sf-input"
                               maxlength="10"
                               placeholder="AED">

                        @error('currency')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Invoice Date --}}
                    <div>
                        <label class="sf-label">
                            Invoice Date
                        </label>

                        <input type="date"
                               name="invoice_date"
                               value="{{ old('invoice_date', now()->toDateString()) }}"
                               class="sf-input">

                        @error('invoice_date')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="sf-label">
                            Due Date
                        </label>

                        <input type="date"
                               name="due_date"
                               value="{{ old('due_date') }}"
                               class="sf-input">

                        @error('due_date')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- ROI Preview --}}
        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    ROI Readiness
                </h2>

                <p class="sf-section-subtitle">
                    ROI becomes strongest when the invoice is paid, amount is captured, and invoice is linked to a job.
                </p>
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Amount
                        </div>

                        <div class="mt-2 font-extrabold text-green-300">
                            Required
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Paid Status
                        </div>

                        <div class="mt-2 font-extrabold text-green-300">
                            Recommended
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Linked Job
                        </div>

                        <div class="mt-2 font-extrabold text-yellow-300">
                            Recommended
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="sf-btn-primary">
                Create Invoice
            </button>

            <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                Cancel
            </a>
        </div>

    </form>

</div>

@if(\Illuminate\Support\Facades\Route::has('admin.ajax.jobs-by-client'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('client_id');
    const jobSelect = document.getElementById('job_id');
    const selectedJobId = @json(old('job_id'));
    const urlTemplate = @json(route('admin.ajax.jobs-by-client', ['client' => 'CLIENT_ID']));

    async function loadJobs(clientId, selectedId = null) {
        jobSelect.innerHTML = '<option value="">Loading jobs...</option>';

        if (!clientId) {
            jobSelect.innerHTML = '<option value="">No linked job</option>';
            return;
        }

        try {
            const response = await fetch(urlTemplate.replace('CLIENT_ID', clientId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const jobs = await response.json();

            jobSelect.innerHTML = '<option value="">No linked job</option>';

            if (!Array.isArray(jobs) || jobs.length === 0) {
                const option = new Option('No jobs found for this client', '');
                jobSelect.add(option);
                return;
            }

            jobs.forEach(function (job) {
                const label = `${job.job_code || ('Job #' + job.id)} — ${String(job.status || '').replace('_', ' ')}`;
                const option = new Option(label, job.id);

                if (String(selectedId) === String(job.id)) {
                    option.selected = true;
                }

                jobSelect.add(option);
            });
        } catch (error) {
            jobSelect.innerHTML = '<option value="">Failed to load jobs</option>';
        }
    }

    clientSelect.addEventListener('change', function () {
        loadJobs(this.value, null);
    });

    if (clientSelect.value) {
        loadJobs(clientSelect.value, selectedJobId);
    }
});
</script>
@else
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('client_id');
    const jobSelect = document.getElementById('job_id');
    const allOptions = Array.from(jobSelect.querySelectorAll('option[data-client-id]'));

    function filterJobs(clientId) {
        const selected = jobSelect.value;

        jobSelect.innerHTML = '<option value="">No linked job</option>';

        allOptions.forEach(function (option) {
            if (!clientId || String(option.dataset.clientId) === String(clientId)) {
                jobSelect.add(option.cloneNode(true));
            }
        });

        if (selected) {
            jobSelect.value = selected;
        }
    }

    clientSelect.addEventListener('change', function () {
        filterJobs(this.value);
    });

    filterJobs(clientSelect.value);
});
</script>
@endif

@endsection