@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Tabs Navigation --}}
    @include('admin.clients.partials.tabs')

    {{-- ⚡ KPIs --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials._kpis')
    </div>

    {{-- 🚗 Vehicles --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials._vehicles', ['vehicles' => $client->vehicles])
    </div>

    {{-- Client Details --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.details')
    </div>

    {{-- Leads --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.leads')
    </div>

    {{-- Opportunities --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.opportunities')
    </div>

    {{-- Files --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.files')
    </div>

    {{-- 🗨️ Communications (client-scoped list + quick add) --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Communications</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.communications.create', ['client_id' => $client->id]) }}"
                   class="text-sm text-blue-600 underline">Add Communication</a>
                <a href="{{ route('admin.communications.index', ['client_id' => $client->id]) }}"
                   class="text-sm text-blue-600 underline">Open Log</a>
            </div>
        </div>

        @php
            $communications = \App\Models\Shared\Communication::where('company_id', company_id())
                ->where('client_id', $client->id)
                ->orderByDesc('communication_date')
                ->orderByDesc('id')
                ->paginate(10);
        @endphp

       
    </div>

    {{-- 🆕 Documents (JobDocuments assigned to this client) --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @php($docs = $client->jobDocuments()->latest('received_at')->limit(10)->get())
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Documents</h2>
            @if(\Illuminate\Support\Facades\Route::has('admin.documents.index'))
                <a href="{{ route('admin.documents.index', ['q' => $client->name]) }}" class="text-blue-600 underline">Open Inbox</a>
            @endif
        </div>

        @if($docs->isEmpty())
            <p class="text-gray-500">No documents assigned yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Received</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-left">Source</th>
                            <th class="px-3 py-2 text-left">File</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($docs as $doc)
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ $doc->received_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-2">{{ $doc->type }}</td>
                                <td class="px-3 py-2">{{ $doc->source }}</td>
                                <td class="px-3 py-2">
                                    <div class="truncate max-w-xs" title="{{ $doc->original_name }}">{{ $doc->original_name }}</div>
                                    @if($doc->public_url)
                                        <a href="{{ $doc->public_url }}" target="_blank" class="text-blue-600 underline">Open</a>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if(\Illuminate\Support\Facades\Route::has('admin.documents.show'))
                                        <a href="{{ route('admin.documents.show', $doc) }}" class="text-indigo-600 underline">View</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Inline upload-to-client shortcut --}}
        @if(\Illuminate\Support\Facades\Route::has('admin.clients.documents.upload'))
            <form class="mt-4" method="POST" action="{{ route('admin.clients.documents.upload', $client) }}" enctype="multipart/form-data">
                @csrf
                <div class="flex flex-wrap gap-2 items-center">
                    <label class="text-sm">Type</label>
                    <select name="type" class="border rounded px-3 py-2">
                        @foreach(['invoice','job_card','other'] as $tp)
                            <option value="{{ $tp }}">{{ $tp }}</option>
                        @endforeach
                    </select>

                    <input type="file" name="file" required class="border rounded px-3 py-2">

                    <button class="bg-gray-900 text-white px-4 py-2 rounded">
                        Upload & Assign
                    </button>
                </div>
                @error('file')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </form>
        @endif
    </div>

    {{-- 🧾 Invoices (client-wide, optional Job link) --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Invoices</h2>
            <a href="{{ route('admin.invoices.create') }}?client_id={{ $client->id }}"
               class="text-sm text-blue-600 underline">Create via full form</a>
        </div>

        {{-- quick upload to this Client (optional Job attach) --}}
        <form method="POST" action="{{ route('admin.clients.invoices.upload', $client) }}" enctype="multipart/form-data" class="space-y-3 mb-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Invoice file</label>
                <input type="file" name="invoice_file" required class="block w-full">
                <p class="text-xs text-gray-500 mt-1">pdf, jpg, jpeg, png, webp • max 5MB</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                <input type="number" name="job_id" placeholder="Attach to Job ID (optional)" class="border p-2 rounded">
                <input type="text"  name="number"       placeholder="Invoice #" class="border p-2 rounded">
                <input type="date"  name="invoice_date" class="border p-2 rounded">
                <input type="date"  name="due_date"     class="border p-2 rounded">
                <input type="text"  name="currency"     placeholder="Currency (AED)" class="border p-2 rounded">
                <input type="number" step="0.01" name="amount" placeholder="Amount" class="border p-2 rounded">
            </div>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_primary" value="1">
                <span>Set as Primary (if Job selected)</span>
            </label>

            <button class="px-3 py-2 bg-gray-900 text-white rounded">Upload</button>
        </form>

        {{-- list of client invoices --}}
        @php($invoices = $client->invoices()->latest('id')->get())
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
                                {{ $inv->job_id ? '• Job #'.$inv->job_id : '• (No Job)' }}
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

                            @if(!$inv->is_primary && $inv->job_id)
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

    {{-- Notes --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.notes')
    </div>

    {{-- Activity --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.activity')
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('admin.clients.index') }}" class="text-blue-600 underline hover:text-blue-800">
            ← Back to Clients
        </a>
    </div>
</div>
@endsection
