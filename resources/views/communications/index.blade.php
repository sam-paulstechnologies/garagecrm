{{-- resources/views/admin/communications/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-semibold">Communication Logs</h1>
    <a href="{{ route('admin.communications.create') }}"
       class="px-4 py-2 rounded bg-blue-600 text-white">New Log</a>
  </div>

  <form method="get" class="grid grid-cols-1 md:grid-cols-6 gap-3 bg-white p-4 rounded mb-4 shadow">
    <select name="client_id" class="border rounded p-2">
      <option value="">All Clients</option>
      @foreach($clients as $c)
        <option value="{{ $c->id }}" @selected(($filters['client_id'] ?? null) == $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>

    <input type="number" name="lead_id" placeholder="Lead ID" value="{{ $filters['lead_id'] ?? '' }}" class="border rounded p-2" />
    <input type="number" name="opportunity_id" placeholder="Opportunity ID" value="{{ $filters['opportunity_id'] ?? '' }}" class="border rounded p-2" />
    <input type="number" name="booking_id" placeholder="Booking ID" value="{{ $filters['booking_id'] ?? '' }}" class="border rounded p-2" />

    <select name="type" class="border rounded p-2">
      <option value="">All Types</option>
      @foreach(['call','email','whatsapp'] as $t)
        <option value="{{ $t }}" @selected(($filters['type'] ?? null) === $t)>{{ ucfirst($t) }}</option>
      @endforeach
    </select>

    <select name="follow_up_required" class="border rounded p-2">
      <option value="">Follow-up: Any</option>
      <option value="1" @selected(($filters['follow_up_required'] ?? null) === '1')>Required</option>
      <option value="0" @selected(($filters['follow_up_required'] ?? null) === '0')>Not Required</option>
    </select>

    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border rounded p-2" />
    <input type="date" name="date_to"   value="{{ $filters['date_to'] ?? '' }}"   class="border rounded p-2" />
    <input type="text" name="q" placeholder="Search content…" value="{{ $filters['q'] ?? '' }}" class="border rounded p-2" />

    <div class="md:col-span-6 flex gap-2">
      <button class="px-4 py-2 bg-gray-900 text-white rounded">Filter</button>
      <a href="{{ route('admin.communications.index') }}" class="px-4 py-2 bg-gray-100 rounded">Reset</a>
    </div>
  </form>

  <div class="bg-white rounded shadow overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left">Date</th>
          <th class="px-4 py-3 text-left">Client</th>
          <th class="px-4 py-3 text-left">Type</th>
          <th class="px-4 py-3 text-left">Follow-up</th>
          <th class="px-4 py-3 text-left">Snippet</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
      @forelse($communications as $row)
        <tr class="border-t">
          <td class="px-4 py-3">{{ optional($row->communication_date)->format('Y-m-d H:i') ?? '—' }}</td>
          <td class="px-4 py-3">
            @if($row->client)
              <a href="{{ route('admin.clients.show', $row->client) }}" class="text-blue-600 hover:underline">
                {{ $row->client->name }}
              </a>
            @else — @endif
          </td>
          <td class="px-4 py-3">{{ ucfirst($row->type) }}</td>
          <td class="px-4 py-3">
            @if($row->follow_up_required)
              <span class="inline-block px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">Required</span>
            @else
              <span class="inline-block px-2 py-1 text-xs rounded bg-green-100 text-green-800">No</span>
            @endif
          </td>
          <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($row->content, 80) }}</td>
          <td class="px-4 py-3 text-right">
            <a href="{{ route('admin.communications.show', $row) }}" class="text-blue-600 hover:underline">View</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No logs found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $communications->links() }}
  </div>
</div>
@endsection
