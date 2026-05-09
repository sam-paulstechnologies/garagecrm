@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                Create Invoice
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Capture invoice number and amount for revenue and ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.invoices.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                All Invoices
            </a>

            <a href="{{ route('admin.jobs.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Open Jobs
            </a>
        </div>

    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-100 text-red-800 p-4 rounded-xl text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info Note --}}
    <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-purple-900">
            ROI-focused invoice record
        </p>
        <p class="text-sm text-purple-800 mt-1">
            SayaraForce does not need itemized billing here. We only capture invoice number, amount, status, client and linked job so revenue can be connected later to lead source, Meta campaigns and WhatsApp campaigns.
        </p>
    </div>

    <form method="POST"
          action="{{ route('admin.invoices.store') }}"
          class="bg-white rounded-xl border p-5 shadow-sm">

        @csrf

        <div class="grid md:grid-cols-2 gap-4">

            {{-- Client --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Client <span class="text-red-600">*</span>
                </label>

                <select id="client_id"
                        name="client_id"
                        class="border rounded-lg px-3 py-2 w-full text-sm"
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
            </div>

            {{-- Linked Job --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Linked Job
                </label>

                <select id="job_id"
                        name="job_id"
                        class="border rounded-lg px-3 py-2 w-full text-sm">
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

                <p class="text-xs text-gray-400 mt-1">
                    Link a job to make this invoice usable for campaign ROI attribution.
                </p>
            </div>

            {{-- Invoice Number --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Invoice Number <span class="text-red-600">*</span>
                </label>

                <input type="text"
                       name="number"
                       value="{{ old('number') }}"
                       class="border rounded-lg px-3 py-2 w-full text-sm"
                       placeholder="Example: INV-1001"
                       required>
            </div>

            {{-- Amount --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Invoice Amount <span class="text-red-600">*</span>
                </label>

                <input type="number"
                       name="amount"
                       value="{{ old('amount') }}"
                       min="1"
                       step="0.01"
                       class="border rounded-lg px-3 py-2 w-full text-sm"
                       placeholder="Example: 7000"
                       required>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Status <span class="text-red-600">*</span>
                </label>

                <select name="status"
                        class="border rounded-lg px-3 py-2 w-full text-sm"
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

                <p class="text-xs text-gray-400 mt-1">
                    Paid invoices are included in ROI revenue.
                </p>
            </div>

            {{-- Currency --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Currency
                </label>

                <input type="text"
                       name="currency"
                       value="{{ old('currency', 'AED') }}"
                       class="border rounded-lg px-3 py-2 w-full text-sm"
                       maxlength="10"
                       placeholder="AED">
            </div>

            {{-- Invoice Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Invoice Date
                </label>

                <input type="date"
                       name="invoice_date"
                       value="{{ old('invoice_date', now()->toDateString()) }}"
                       class="border rounded-lg px-3 py-2 w-full text-sm">
            </div>

            {{-- Due Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Due Date
                </label>

                <input type="date"
                       name="due_date"
                       value="{{ old('due_date') }}"
                       class="border rounded-lg px-3 py-2 w-full text-sm">
            </div>

        </div>

        {{-- ROI Preview --}}
        <div class="mt-5 bg-gray-50 border rounded-xl p-4">
            <p class="text-sm font-semibold text-gray-900">
                ROI Readiness
            </p>

            <div class="grid sm:grid-cols-3 gap-3 mt-3 text-sm">

                <div class="bg-white border rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Amount</p>
                    <p class="font-semibold text-green-700 mt-1">
                        Required
                    </p>
                </div>

                <div class="bg-white border rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Paid Status</p>
                    <p class="font-semibold text-green-700 mt-1">
                        Recommended
                    </p>
                </div>

                <div class="bg-white border rounded-lg p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Linked Job</p>
                    <p class="font-semibold text-yellow-700 mt-1">
                        Recommended
                    </p>
                </div>

            </div>

            <p class="text-xs text-gray-500 mt-3">
                ROI becomes strongest when the invoice is paid, amount is captured, and invoice is linked to a job.
            </p>
        </div>

        {{-- Actions --}}
        <div class="mt-5 flex flex-wrap items-center gap-3">

            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                Create Invoice
            </button>

            <a href="{{ route('admin.invoices.index') }}"
               class="px-5 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
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