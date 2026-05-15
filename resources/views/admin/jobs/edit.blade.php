@extends('layouts.app')

@section('title', 'Edit Job')

@section('content')
<div class="sf-page space-y-6">

    @php
        $status = $job->status ?? 'pending';

        $statusBadge = match($status) {
            'completed' => 'sf-badge-green',
            'in_progress' => 'sf-badge-blue',
            default => 'sf-badge-yellow',
        };

        $jobText = strtolower(trim(
            ($job->description ?? '') . ' ' .
            ($job->work_summary ?? '') . ' ' .
            ($job->issues_found ?? '') . ' ' .
            ($job->parts_used ?? '')
        ));

        $serviceBucket = 'General Service';

        if (str_contains($jobText, 'oil')) {
            $serviceBucket = 'Oil Service';
        } elseif (str_contains($jobText, 'battery')) {
            $serviceBucket = 'Battery Service';
        } elseif (str_contains($jobText, 'tyre') || str_contains($jobText, 'tire')) {
            $serviceBucket = 'Tyre Service';
        } elseif (str_contains($jobText, 'ac') || str_contains($jobText, 'a/c') || str_contains($jobText, 'air condition')) {
            $serviceBucket = 'AC Service';
        } elseif (str_contains($jobText, 'brake')) {
            $serviceBucket = 'Brake Service';
        } elseif (str_contains($jobText, 'wash') || str_contains($jobText, 'detailing')) {
            $serviceBucket = 'Car Wash / Detailing';
        }

        $serviceBadge = match($serviceBucket) {
            'Oil Service' => 'sf-badge-orange',
            'Battery Service' => 'sf-badge-blue',
            'Tyre Service' => 'sf-badge-slate',
            'AC Service' => 'sf-badge-blue',
            'Brake Service' => 'sf-badge-red',
            'Car Wash / Detailing' => 'sf-badge-green',
            default => 'sf-badge-slate',
        };

        $invoice = $job->invoice ?? $job->primaryInvoice ?? $job->invoices?->first();

        $invoiceNumber = $invoice?->invoice_number ?? $invoice?->number ?? '';
        $invoiceAmount = $invoice?->amount ?? '';
    @endphp

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">
                    Job Management
                </div>

                <span class="{{ $statusBadge }}">
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </span>

                <span class="{{ $serviceBadge }}">
                    {{ $serviceBucket }}
                </span>
            </div>

            <h1 class="sf-page-title mt-3">
                Edit {{ $job->job_code ?? 'Job' }}
            </h1>

            <p class="sf-page-subtitle">
                Update the job. To mark it completed, invoice number and amount are required.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.show', $job->id) }}" class="sf-btn-secondary">
                Back to Job
            </a>

            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Open Jobs
            </a>
        </div>
    </div>

    {{-- Notice --}}
    <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-orange-300">
            Job closure rule
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
            Select <strong>Completed</strong> only when the job is actually closed. The system will ask for invoice number and amount before saving completion.
            No itemized bill is required.
        </p>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="jobEditForm"
          method="POST"
          action="{{ route('admin.jobs.update', $job->id) }}"
          class="space-y-6">

        @csrf
        @method('PUT')

        <input type="hidden" name="invoice_number" id="hidden_invoice_number" value="{{ old('invoice_number', $invoiceNumber) }}">
        <input type="hidden" name="invoice_amount" id="hidden_invoice_amount" value="{{ old('invoice_amount', $invoiceAmount) }}">

        {{-- Main Form --}}
        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    Job Information
                </h2>

                <p class="sf-section-subtitle">
                    Update client, owner, timing, job status, and service notes.
                </p>
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- Client --}}
                    <div>
                        <label class="sf-label">
                            Client <span class="text-red-300">*</span>
                        </label>

                        <select name="client_id"
                                class="sf-select"
                                required>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ (int) $client->id === (int) $job->client_id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('client_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Owner --}}
                    <div>
                        <label class="sf-label">
                            Internal Owner
                        </label>

                        <select name="assigned_to" class="sf-select">
                            <option value="">Unassigned</option>

                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ (int) $user->id === (int) $job->assigned_to ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('assigned_to')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Start Time --}}
                    <div>
                        <label class="sf-label">
                            Start Time
                        </label>

                        <input type="datetime-local"
                               name="start_time"
                               value="{{ old('start_time', optional($job->start_time)->format('Y-m-d\TH:i')) }}"
                               class="sf-input">

                        @error('start_time')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="sf-label">
                            Current Stage
                        </label>

                        <select name="status"
                                id="job_status"
                                class="sf-select"
                                required>
                            <option value="pending" {{ $job->status === 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>

                            <option value="in_progress" {{ $job->status === 'in_progress' ? 'selected' : '' }}>
                                In Progress
                            </option>

                            <option value="completed" {{ $job->status === 'completed' ? 'selected' : '' }}>
                                Completed
                            </option>
                        </select>

                        <p class="sf-help">
                            Completed requires invoice number and amount.
                        </p>

                        @error('status')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label class="sf-label">
                            Service / Job Description <span class="text-red-300">*</span>
                        </label>

                        <textarea name="description"
                                  class="sf-textarea"
                                  rows="3"
                                  required>{{ old('description', $job->description) }}</textarea>

                        @error('description')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Work Summary --}}
                    <div class="md:col-span-2">
                        <label class="sf-label">
                            Work Summary
                        </label>

                        <textarea name="work_summary"
                                  class="sf-textarea"
                                  rows="3">{{ old('work_summary', $job->work_summary) }}</textarea>

                        @error('work_summary')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Issues Found --}}
                    <div>
                        <label class="sf-label">
                            Issues Found
                        </label>

                        <textarea name="issues_found"
                                  class="sf-textarea"
                                  rows="3">{{ old('issues_found', $job->issues_found) }}</textarea>

                        @error('issues_found')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Parts Used --}}
                    <div>
                        <label class="sf-label">
                            Parts Used
                        </label>

                        <textarea name="parts_used"
                                  class="sf-textarea"
                                  rows="3">{{ old('parts_used', $job->parts_used) }}</textarea>

                        @error('parts_used')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Service Signal Preview --}}
        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    Current Service Bucket
                </h2>

                <p class="sf-section-subtitle">
                    This bucket is detected from description, work summary, issues found, and parts used.
                </p>
            </div>

            <div class="sf-card-body">
                <span class="{{ $serviceBadge }}">
                    {{ $serviceBucket }}
                </span>
            </div>
        </div>

        {{-- Existing Invoice Info --}}
        @if($invoiceNumber || $invoiceAmount)
            <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5 shadow-xl shadow-black/20">
                <div class="font-extrabold text-green-300">
                    Invoice details captured
                </div>

                <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                    Invoice No:
                    <strong class="text-white">{{ $invoiceNumber ?: '—' }}</strong>,
                    Amount:
                    <strong class="text-white">{{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : '—' }}</strong>
                </p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="sf-btn-primary">
                Update Job
            </button>

            <a href="{{ route('admin.jobs.show', $job->id) }}" class="sf-btn-secondary">
                Cancel
            </a>
        </div>

    </form>

</div>

{{-- Invoice Modal --}}
<div id="invoiceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4 backdrop-blur-sm">

    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-slate-950 p-6 shadow-2xl shadow-black/50">

        <h3 class="text-lg font-extrabold text-white">
            Close Job with Invoice
        </h3>

        <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
            Please enter invoice number and invoice amount to mark this job as completed.
        </p>

        <div class="mt-5 space-y-4">

            <div>
                <label class="sf-label">
                    Invoice Number <span class="text-red-300">*</span>
                </label>

                <input type="text"
                       id="modal_invoice_number"
                       value="{{ old('invoice_number', $invoiceNumber) }}"
                       class="sf-input"
                       placeholder="Example: INV-1001">
            </div>

            <div>
                <label class="sf-label">
                    Invoice Amount <span class="text-red-300">*</span>
                </label>

                <input type="number"
                       id="modal_invoice_amount"
                       value="{{ old('invoice_amount', $invoiceAmount) }}"
                       class="sf-input"
                       min="0"
                       step="0.01"
                       placeholder="Example: 850">
            </div>

            <p id="invoiceModalError" class="hidden text-sm font-bold text-red-400">
                Invoice number and amount are required to close the job.
            </p>

        </div>

        <div class="mt-6 flex items-center justify-end gap-3">

            <button type="button"
                    id="cancelInvoiceModal"
                    class="sf-btn-secondary">
                Cancel
            </button>

            <button type="button"
                    id="confirmInvoiceModal"
                    class="sf-btn-primary">
                Close Job
            </button>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('jobEditForm');
    const statusSelect = document.getElementById('job_status');

    const modal = document.getElementById('invoiceModal');
    const cancelBtn = document.getElementById('cancelInvoiceModal');
    const confirmBtn = document.getElementById('confirmInvoiceModal');

    const modalInvoiceNumber = document.getElementById('modal_invoice_number');
    const modalInvoiceAmount = document.getElementById('modal_invoice_amount');

    const hiddenInvoiceNumber = document.getElementById('hidden_invoice_number');
    const hiddenInvoiceAmount = document.getElementById('hidden_invoice_amount');

    const error = document.getElementById('invoiceModalError');

    let allowSubmit = false;

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        error.classList.add('hidden');
        setTimeout(() => modalInvoiceNumber.focus(), 100);
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        error.classList.add('hidden');
    }

    form.addEventListener('submit', function (e) {
        if (allowSubmit) {
            return true;
        }

        if (statusSelect.value === 'completed') {
            const invoiceNumber = hiddenInvoiceNumber.value.trim();
            const invoiceAmount = hiddenInvoiceAmount.value.trim();

            if (!invoiceNumber || !invoiceAmount || parseFloat(invoiceAmount) <= 0) {
                e.preventDefault();
                openModal();
                return false;
            }
        }
    });

    cancelBtn.addEventListener('click', function () {
        closeModal();
    });

    confirmBtn.addEventListener('click', function () {
        const invoiceNumber = modalInvoiceNumber.value.trim();
        const invoiceAmount = modalInvoiceAmount.value.trim();

        if (!invoiceNumber || !invoiceAmount || parseFloat(invoiceAmount) <= 0) {
            error.classList.remove('hidden');
            return;
        }

        hiddenInvoiceNumber.value = invoiceNumber;
        hiddenInvoiceAmount.value = invoiceAmount;

        allowSubmit = true;
        form.submit();
    });
});
</script>
@endsection