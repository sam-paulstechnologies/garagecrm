@extends('layouts.app')

@section('content')
<div class="px-6 py-4 space-y-6">
  <div class="flex items-center justify-between">
    <h2 class="text-2xl font-semibold text-gray-800">Potential Duplicates</h2>
    <a href="{{ route('admin.leads.index') }}" class="text-blue-600 hover:underline">← Back to Leads</a>
  </div>

  <form action="{{ route('admin.leads.duplicates.update-window') }}" method="POST"
        class="flex items-center gap-2 bg-white p-3 rounded shadow">
    @csrf
    <label class="text-sm text-gray-600">Duplicate window (days):</label>
    <input type="number" name="window_days" min="1" max="365" value="{{ $windowDays }}"
           class="w-24 border rounded px-2 py-1 text-sm" />
    <button class="bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 rounded text-sm">Save</button>
  </form>

  @if(session('success'))
    <div class="rounded bg-green-100 border border-green-300 text-green-800 p-3 text-sm">
      {{ session('success') }}
    </div>
  @endif

  <div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
      <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
        <tr>
          <th class="px-4 py-2">Detected</th>
          <th class="px-4 py-2">Matched On</th>
          <th class="px-4 py-2">Name</th>
          <th class="px-4 py-2">Email</th>
          <th class="px-4 py-2">Phone</th>
          <th class="px-4 py-2">Window</th>
          <th class="px-4 py-2">Reason</th>
          <th class="px-4 py-2">Primary Lead</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        @forelse($dupes as $d)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-2">{{ optional($d->detected_at)->format('d/m/Y H:i') }}</td>
            <td class="px-4 py-2">{{ ucfirst($d->matched_on ?? '—') }}</td>
            <td class="px-4 py-2">{{ $d->name ?? '—' }}</td>
            <td class="px-4 py-2">{{ $d->email ?? '—' }}</td>
            <td class="px-4 py-2">{{ $d->phone ?? '—' }}</td>
            <td class="px-4 py-2">{{ $d->window_days }} days</td>
            <td class="px-4 py-2">{{ $d->reason ?? '—' }}</td>
            <td class="px-4 py-2">
              @if($d->primary)
                <a class="text-blue-600 hover:underline" href="{{ route('admin.leads.show', $d->primary->id) }}">
                  #{{ $d->primary->id }} — {{ $d->primary->name }}
                </a>
              @else
                —
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">No duplicates found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div>{{ $dupes->links() }}</div>
</div>
@endsection
