@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-xl font-bold mb-4">Job Details</h2>

    <div class="space-y-4">
        <div><strong>Client:</strong> {{ $job->client->name ?? 'N/A' }}</div>
        <div><strong>Description:</strong> {{ $job->description }}</div>
        <div><strong>Status:</strong> {{ ucfirst($job->status) }}</div>
        <div><strong>Assigned To:</strong> {{ $job->assignedUser->name ?? 'Unassigned' }}</div>
    </div>

    {{-- 🧾 Invoices --}}
    <div class="mt-8 rounded border p-4 bg-white">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold">Invoices</h3>
            <a href="{{ route('admin.invoices.create') }}?client_id={{ $job->client_id }}&job_id={{ $job->id }}"
               class="text-sm text-blue-600 underline">Create via full form</a>
        </div>

        {{-- Quick upload to this Job --}}
        <form method="POST" action="{{ route('admin.jobs.invoices.upload', $job) }}" enctype="multipart/form-data" class="space-y-3 mb-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Invoice file</label>
                <input type="file" name="invoice_file" required class="block w-full">
                <p class="text-xs text-gray-500 mt-1">pdf, jpg, jpeg, png, webp • max 5MB</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                <input type="text"  name="number"       placeholder="Invoice #" class="border p-2 rounded">
                <input type="date"  name="invoice_date" class="border p-2 rounded">
                <input type="date"  name="due_date"     class="border p-2 rounded">
                <input type="text"  name="currency"     placeholder="Currency (AED)" class="border p-2 rounded">
                <input type="number" step="0.01" name="amount" placeholder="Amount" class="border p-2 rounded">
            </div>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_primary" value="1">
                <span>Set as Primary</span>
            </label>

            <button class="px-3 py-2 bg-gray-900 text-white rounded">Upload</button>
        </form>

        {{-- List of invoices for this Job --}}
        @php($invoices = $job->invoices()->latest('id')->get())
        @if($invoices->isEmpty())
            <p class="text-gray-500">No invoices yet.</p>
        @else
            <ul class="space-y-2">
                @foreach($invoices as $inv)
                    <li class="flex items-center justify-between">
                        <div class="min-w-0">
                            @if($inv->is_primary)
                                <span class="px-2 py-0.5 text-xs bg-green-100 rounded mr-2">Primary</span>
                            @endif

                            @if($inv->file_path)
                                <a href="{{ route('admin.invoices.view', $inv) }}" target="_blank"
                                   class="font-medium truncate inline-block max-w-[60ch] align-middle">
                                   {{ $inv->number ?? basename($inv->file_path) ?? ('Invoice #'.$inv->id) }}
                                </a>
                            @else
                                <span class="font-medium">{{ $inv->number ?? ('Invoice #'.$inv->id) }}</span>
                            @endif

                            <span class="text-xs text-gray-500 ml-2">
                                {{ $inv->invoice_date ? '• '.$inv->invoice_date->toDateString() : '' }}
                                {{ $inv->amount ? '• '.$inv->amount.' '.$inv->currency : '' }}
                                {{ $inv->due_date ? '• Due '.$inv->due_date->toDateString() : '' }}
                                • v{{ $inv->version ?? 1 }}
                                • {{ ucfirst($inv->source ?? 'upload') }}
                                • Status: {{ $inv->status }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            @if($inv->file_path)
                                <a class="text-sm text-blue-600" href="{{ route('admin.invoices.download', $inv) }}">Download</a>
                                <a class="text-sm text-blue-600" href="{{ route('admin.invoices.view', $inv) }}" target="_blank">View</a>
                            @endif

                            @if(!$inv->is_primary)
                                <form method="POST" action="{{ route('admin.invoices.primary', $inv) }}">
                                    @csrf
                                    <button class="text-sm text-blue-700">Make Primary</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.jobs.index') }}" class="text-blue-600">← Back to Jobs</a>
    </div>
</div>
@endsection
