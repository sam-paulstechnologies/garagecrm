{{-- resources/views/admin/jobs/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

  {{-- Header --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <h2 class="text-xl font-bold">Job #{{ $job->job_code }}</h2>
      <p class="text-sm text-gray-500">
        Created for <span class="font-medium">{{ $job->client?->name ?? 'N/A' }}</span>
      </p>
    </div>
    <div class="flex items-center gap-2">
      @php
        $status = $job->status ?? 'pending';
        $badge = match($status) {
          'completed'   => 'bg-green-100 text-green-800',
          'in_progress' => 'bg-blue-100 text-blue-800',
          default       => 'bg-yellow-100 text-yellow-800'
        };
      @endphp
      <span class="px-2 py-1 rounded text-xs {{ $badge }}">
        {{ str_replace('_',' ', ucfirst($status)) }}
      </span>
      <a href="{{ route('admin.jobs.edit', $job->id) }}" class="px-3 py-2 bg-gray-900 text-white rounded">Edit Job</a>
      <a href="{{ route('admin.jobs.index') }}" class="text-blue-600">Back to Jobs</a>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- LEFT --}}
    <div class="lg:col-span-2 space-y-6">

      {{-- Details --}}
      <div class="bg-white rounded border p-5">
        <h3 class="font-semibold mb-3">Details</h3>
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
          <div><dt class="text-gray-500">Description</dt><dd class="font-medium">{{ $job->description ?? '—' }}</dd></div>
          <div><dt class="text-gray-500">Assigned To</dt><dd class="font-medium">{{ $job->assignedUser?->name ?? 'Unassigned' }}</dd></div>
          <div><dt class="text-gray-500">Start</dt><dd class="font-medium">{{ $job->start_time?->format('Y-m-d H:i') ?? '—' }}</dd></div>
          <div><dt class="text-gray-500">End</dt><dd class="font-medium">{{ $job->end_time?->format('Y-m-d H:i') ?? '—' }}</dd></div>
          <div><dt class="text-gray-500">Issues Found</dt><dd class="font-medium">{{ $job->issues_found ?: '—' }}</dd></div>
          <div><dt class="text-gray-500">Parts Used</dt><dd class="font-medium">{{ $job->parts_used ?: '—' }}</dd></div>
          <div><dt class="text-gray-500">Total Time (min)</dt><dd class="font-medium">{{ $job->total_time_minutes ?? '—' }}</dd></div>
        </dl>
      </div>

      {{-- Job Card --}}
      <div class="bg-white rounded border p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold">Job Card</h3>
        </div>

        <form method="POST" action="{{ route('admin.jobs.card.upload', $job) }}" enctype="multipart/form-data" class="space-y-3 mb-6">
          @csrf
          <div class="border border-dashed rounded p-4 bg-gray-50">
            <label class="block text-sm font-medium mb-1">Upload job card (PDF / Image)</label>
            <input type="file" name="file" required class="block w-full">
            <p class="text-xs text-gray-500 mt-1">pdf, jpg, jpeg, png, webp • max 5MB</p>
            <input type="text" name="description" class="border w-full mt-3 px-3 py-2 rounded" placeholder="Description (optional)">
          </div>
          <button class="px-3 py-2 bg-gray-900 text-white rounded">Upload Job Card</button>
        </form>

        @php($cards = $job->jobCards()->latest('id')->get())

        <ul class="divide-y">
          @forelse($cards as $c)
            <li class="py-3 flex items-center justify-between">
              <div class="min-w-0">
                <div class="font-medium truncate">{{ $c->description ?: 'Job Card' }}</div>
                <div class="text-xs text-gray-500">{{ $c->file_type }} • {{ basename($c->file_path) }}</div>
              </div>
              <div class="shrink-0">
                @php
                  $doc = $job->jobDocuments()
                              ->where('path', $c->file_path)
                              ->latest('id')
                              ->first();
                  $url = $doc?->url ?? ($doc?->path ? \Illuminate\Support\Facades\Storage::url($doc->path) : null);
                @endphp
                @if($url)
                  <a class="text-blue-600" href="{{ $url }}" target="_blank">View</a>
                @else
                  <span class="text-gray-400">No link</span>
                @endif
              </div>
            </li>
          @empty
            <li class="py-3 text-gray-500">No job card uploaded yet.</li>
          @endforelse
        </ul>
      </div>

      {{-- Invoices --}}
      <div class="bg-white rounded border p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold">Invoices</h3>
          <a href="{{ route('admin.invoices.create') }}?client_id={{ $job->client_id }}&job_id={{ $job->id }}"
             class="text-sm text-blue-600 underline">Create via full form</a>
        </div>

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
            <input type="checkbox" name="is_primary" value="1"><span>Set as Primary</span>
          </label>
          <button class="px-3 py-2 bg-gray-900 text-white rounded">Upload</button>
        </form>

        @php($invoices = $job->invoices()->latest('id')->get())

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Invoice</th>
                <th class="px-3 py-2 text-left">Date</th>
                <th class="px-3 py-2 text-left">Amount</th>
                <th class="px-3 py-2 text-left">Status</th>
                <th class="px-3 py-2 text-left">Source</th>
                <th class="px-3 py-2 text-left">Ver.</th>
                <th class="px-3 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($invoices as $inv)
                @php
                  $invStatus = $inv->status ?? 'pending';
                  $amountStr = $inv->amount !== null
                    ? number_format((float)$inv->amount, 2).' '.($inv->currency ?: 'AED')
                    : '—';
                @endphp
                <tr class="border-t">
                  <td class="px-3 py-2">
                    @if($inv->is_primary)
                      <span class="px-1.5 py-0.5 text-[11px] bg-green-100 text-green-700 rounded mr-2 align-middle">Primary</span>
                    @endif
                    @if($inv->file_path)
                      <a href="{{ route('admin.invoices.view', $inv) }}" target="_blank" class="text-blue-700 underline">
                        {{ $inv->number ?? basename($inv->file_path) ?? ('Invoice #'.$inv->id) }}
                      </a>
                    @else
                      <span class="text-gray-800">{{ $inv->number ?? ('Invoice #'.$inv->id) }}</span>
                    @endif
                  </td>
                  <td class="px-3 py-2">{{ $inv->invoice_date?->toDateString() ?? '—' }}</td>
                  <td class="px-3 py-2">{{ $amountStr }}</td>
                  <td class="px-3 py-2">{{ ucfirst($invStatus) }}</td>
                  <td class="px-3 py-2">{{ ucfirst($inv->source ?? 'upload') }}</td>
                  <td class="px-3 py-2">v{{ $inv->version ?? 1 }}</td>
                  <td class="px-3 py-2">
                    <div class="flex justify-end gap-3">
                      @if($inv->file_path)
                        <a class="text-blue-600" href="{{ route('admin.invoices.download', $inv) }}">Download</a>
                        <a class="text-blue-600" href="{{ route('admin.invoices.view', $inv) }}" target="_blank">View</a>
                      @endif
                      @if(!$inv->is_primary)
                        <form method="POST" action="{{ route('admin.invoices.primary', $inv) }}">
                          @csrf
                          <button class="text-blue-700">Make Primary</button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr class="border-t">
                  <td class="px-3 py-4 text-gray-500" colspan="7">No invoices yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- RIGHT --}}
    <aside class="space-y-6">
      <div class="bg-white rounded border p-5">
        <h3 class="font-semibold mb-3">Client</h3>
        <div class="text-sm">
          <div class="font-medium">{{ $job->client?->name ?? '—' }}</div>
          @if($job->client)
            <div class="text-gray-500">{{ $job->client->phone ?? '' }}</div>
            <div class="text-gray-500">{{ $job->client->email ?? '' }}</div>
            <a href="{{ route('admin.clients.show', $job->client->id) }}" class="text-blue-600 text-sm mt-2 inline-block">View Client</a>
          @endif
        </div>
      </div>

      <div class="bg-white rounded border p-5">
        <h3 class="font-semibold mb-3">Actions</h3>
        <p class="text-sm text-gray-500 mb-3">Status changes & edits are done from the edit screen.</p>
        <a href="{{ route('admin.jobs.edit', $job->id) }}" class="px-3 py-2 bg-blue-600 text-white rounded w-full inline-block text-center">Update Job</a>
      </div>
    </aside>
  </div>
</div>
@endsection
