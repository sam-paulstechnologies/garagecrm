@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    @php
        $status = $job->status ?? 'pending';

        $statusBadge = match($status) {
            'completed' => 'bg-green-100 text-green-800 border-green-200',
            'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
            default => 'bg-yellow-100 text-yellow-800 border-yellow-200',
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
            'Oil Service' => 'bg-amber-50 text-amber-800 border-amber-100',
            'Battery Service' => 'bg-purple-50 text-purple-800 border-purple-100',
            'Tyre Service' => 'bg-slate-50 text-slate-800 border-slate-200',
            'AC Service' => 'bg-cyan-50 text-cyan-800 border-cyan-100',
            'Brake Service' => 'bg-red-50 text-red-800 border-red-100',
            'Car Wash / Detailing' => 'bg-green-50 text-green-800 border-green-100',
            default => 'bg-gray-50 text-gray-800 border-gray-200',
        };

        $invoice = $job->invoice ?? $job->primaryInvoice ?? $job->invoices?->first();

        $invoiceNumber = $invoice?->invoice_number ?? $invoice?->number ?? '';
        $invoiceAmount = $invoice?->amount ?? '';
    @endphp

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-bold text-gray-900">
                    Edit {{ $job->job_code ?? 'Job' }}
                </h2>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusBadge }}">
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </span>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $serviceBadge }}">
                    {{ $serviceBucket }}
                </span>
            </div>

            <p class="text-sm text-gray-500 mt-1">
                Update the job. To mark it completed, invoice number and amount are required.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.show', $job->id) }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Back to Job
            </a>

            <a href="{{ route('admin.jobs.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Open Jobs
            </a>
        </div>

    </div>

    {{-- Notice --}}
    <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-purple-900">
            Job closure rule
        </p>
        <p class="text-sm text-purple-800 mt-1">
            Select <strong>Completed</strong> only when the job is actually closed. The system will ask for invoice number and amount before saving completion.
            No itemized bill is required.
        </p>
    </div>

    <form id="jobEditForm"
          method="POST"
          action="{{ route('admin.jobs.update', $job->id) }}"
          class="bg-white rounded-xl border p-5 shadow-sm">

        @csrf
        @method('PUT')

        <input type="hidden" name="invoice_number" id="hidden_invoice_number" value="{{ old('invoice_number', $invoiceNumber) }}">
        <input type="hidden" name="invoice_amount" id="hidden_invoice_amount" value="{{ old('invoice_amount', $invoiceAmount) }}">

        <div class="grid md:grid-cols-2 gap-4">

            {{-- Client --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Client <span class="text-red-600">*</span>
                </label>

                <select name="client_id"
                        class="border rounded-lg px-3 py-2 w-full text-sm"
                        required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ (int) $client->id === (int) $job->client_id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Owner --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Internal Owner
                </label>

                <select name="assigned_to"
                        class="border rounded-lg px-3 py-2 w-full text-sm">
                    <option value="">Unassigned</option>

                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (int) $user->id === (int) $job->assigned_to ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Start Time only --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Start Time
                </label>

                <input type="datetime-local"
                       name="start_time"
                       value="{{ old('start_time', optional($job->start_time)->format('Y-m-d\TH:i')) }}"
                       class="border rounded-lg px-3 py-2 w-full text-sm">
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Current Stage
                </label>

                <select name="status"
                        id="job_status"
                        class="border rounded-lg px-3 py-2 w-full text-sm"
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

                <p class="text-xs text-gray-400 mt-1">
                    Completed requires invoice number and amount.
                </p>
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Service / Job Description <span class="text-red-600">*</span>
                </label>

                <textarea name="description"
                          class="border rounded-lg px-3 py-2 w-full text-sm"
                          rows="3"
                          required>{{ old('description', $job->description) }}</textarea>
            </div>

            {{-- Work Summary --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Work Summary
                </label>

                <textarea name="work_summary"
                          class="border rounded-lg px-3 py-2 w-full text-sm"
                          rows="3">{{ old('work_summary', $job->work_summary) }}</textarea>
            </div>

            {{-- Issues Found --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Issues Found
                </label>

                <textarea name="issues_found"
                          class="border rounded-lg px-3 py-2 w-full text-sm"
                          rows="3">{{ old('issues_found', $job->issues_found) }}</textarea>
            </div>

            {{-- Parts Used --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Parts Used
                </label>

                <textarea name="parts_used"
                          class="border rounded-lg px-3 py-2 w-full text-sm"
                          rows="3">{{ old('parts_used', $job->parts_used) }}</textarea>
            </div>

        </div>

        {{-- Service Signal Preview --}}
        <div class="mt-5 bg-gray-50 border rounded-xl p-4">
            <p class="text-sm font-semibold text-gray-900">
                Current Service Bucket
            </p>

            <div class="mt-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $serviceBadge }}">
                    {{ $serviceBucket }}
                </span>
            </div>

            <p class="text-xs text-gray-500 mt-2">
                This bucket is detected from description, work summary, issues found, and parts used.
            </p>
        </div>

        {{-- Existing invoice info --}}
        @if($invoiceNumber || $invoiceAmount)
            <div class="mt-5 bg-green-50 border border-green-100 rounded-xl p-4">
                <p class="text-sm font-semibold text-green-900">
                    Invoice details captured
                </p>
                <p class="text-sm text-green-800 mt-1">
                    Invoice No: <strong>{{ $invoiceNumber ?: '—' }}</strong>,
                    Amount: <strong>{{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : '—' }}</strong>
                </p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="mt-5 flex flex-wrap items-center gap-3">

            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                Update Job
            </button>

            <a href="{{ route('admin.jobs.show', $job->id) }}"
               class="px-5 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Cancel
            </a>

        </div>

    </form>

</div>

{{-- Invoice Modal --}}
<div id="invoiceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 px-4">

    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">

        <h3 class="text-lg font-bold text-gray-900">
            Close Job with Invoice
        </h3>

        <p class="text-sm text-gray-500 mt-1">
            Please enter invoice number and invoice amount to mark this job as completed.
        </p>

        <div class="mt-5 space-y-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Invoice Number <span class="text-red-600">*</span>
                </label>

                <input type="text"
                       id="modal_invoice_number"
                       value="{{ old('invoice_number', $invoiceNumber) }}"
                       class="border rounded-lg px-3 py-2 w-full text-sm"
                       placeholder="Example: INV-1001">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Invoice Amount <span class="text-red-600">*</span>
                </label>

                <input type="number"
                       id="modal_invoice_amount"
                       value="{{ old('invoice_amount', $invoiceAmount) }}"
                       class="border rounded-lg px-3 py-2 w-full text-sm"
                       min="0"
                       step="0.01"
                       placeholder="Example: 850">
            </div>

            <p id="invoiceModalError" class="hidden text-sm text-red-600">
                Invoice number and amount are required to close the job.
            </p>

        </div>

        <div class="mt-6 flex items-center justify-end gap-3">

            <button type="button"
                    id="cancelInvoiceModal"
                    class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Cancel
            </button>

            <button type="button"
                    id="confirmInvoiceModal"
                    class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-sm font-medium">
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