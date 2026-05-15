@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
<div class="sf-page space-y-6">

    @php
        $invoiceNumber = $invoice->invoice_number
            ?? $invoice->number
            ?? 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

        $statusValue = $invoice->status ?? 'pending';

        $statusBadge = match($statusValue) {
            'paid' => 'sf-badge-green',
            'overdue' => 'sf-badge-red',
            default => 'sf-badge-yellow',
        };

        $amount = (float) ($invoice->amount ?? 0);
        $currency = $invoice->currency ?? 'AED';

        $hasRevenue = $amount > 0;
        $hasJob = !empty($invoice->job_id);
        $roiReady = $statusValue === 'paid' && $hasRevenue && $hasJob;
    @endphp

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">
                    Revenue Tracking
                </div>

                <span class="{{ $statusBadge }}">
                    {{ ucwords($statusValue) }}
                </span>

                @if($roiReady)
                    <span class="sf-badge-orange">
                        ROI Ready
                    </span>
                @else
                    <span class="sf-badge-slate">
                        ROI Pending
                    </span>
                @endif
            </div>

            <h1 class="sf-page-title mt-3">
                Edit Invoice {{ $invoiceNumber }}
            </h1>

            <p class="sf-page-subtitle">
                Update invoice number, amount, status, client and linked job for ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-btn-secondary">
                Back to Invoice
            </a>

            <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                All Invoices
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
            SayaraForce only needs invoice number, invoice amount, status, client and linked job. No itemized bill or invoice file upload is required.
        </p>
    </div>

    <form method="POST"
          action="{{ route('admin.invoices.update', $invoice) }}"
          class="space-y-6">

        @csrf
        @method('PUT')

        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    Invoice Information
                </h2>

                <p class="sf-section-subtitle">
                    Keep this invoice clean so revenue can be used for campaign ROI.
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
                                        {{ (int) old('client_id', $invoice->client_id) === (int) $client->id ? 'selected' : '' }}>
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

                            @foreach($jobs as $job)
                                <option value="{{ $job->id }}"
                                        {{ (int) old('job_id', $invoice->job_id) === (int) $job->id ? 'selected' : '' }}>
                                    {{ $job->job_code ?? 'Job #' . $job->id }}
                                    — {{ ucwords(str_replace('_', ' ', $job->status ?? '')) }}
                                </option>
                            @endforeach
                        </select>

                        <p class="sf-help">
                            Link job to make invoice usable for campaign ROI attribution.
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
                               value="{{ old('number', $invoice->number ?? $invoice->invoice_number) }}"
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
                               value="{{ old('amount', $invoice->amount) }}"
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
                            <option value="pending" {{ old('status', $invoice->status) === 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>

                            <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>
                                Paid
                            </option>

                            <option value="overdue" {{ old('status', $invoice->status) === 'overdue' ? 'selected' : '' }}>
                                Overdue
                            </option>
                        </select>

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
                               value="{{ old('currency', $invoice->currency ?? 'AED') }}"
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
                               value="{{ old('invoice_date', $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '') }}"
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
                               value="{{ old('due_date', $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '') }}"
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
                    ROI Readiness Preview
                </h2>

                <p class="sf-section-subtitle">
                    ROI is ready only when invoice is paid, amount is captured, and invoice is linked to a job.
                </p>
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Amount
                        </div>

                        <div class="mt-2 font-extrabold {{ $hasRevenue ? 'text-green-300' : 'text-red-300' }}">
                            {{ $hasRevenue ? 'Available' : 'Missing' }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Paid Status
                        </div>

                        <div class="mt-2 font-extrabold {{ $statusValue === 'paid' ? 'text-green-300' : 'text-yellow-300' }}">
                            {{ $statusValue === 'paid' ? 'Paid' : 'Not Paid' }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Linked Job
                        </div>

                        <div class="mt-2 font-extrabold {{ $hasJob ? 'text-green-300' : 'text-red-300' }}">
                            {{ $hasJob ? 'Linked' : 'Missing' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Legacy File Notice --}}
        @if($invoice->file_path)
            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                <div class="font-extrabold text-blue-300">
                    Existing uploaded file
                </div>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    This invoice has an old uploaded file. File upload is no longer required for the SayaraForce ROI flow.
                </p>

                <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-btn-primary mt-4">
                    Download Existing File
                </a>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="sf-btn-primary">
                Update Invoice
            </button>

            <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-btn-secondary">
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
    const selectedJobId = @json(old('job_id', $invoice->job_id));
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
});
</script>
@endif

@endsection