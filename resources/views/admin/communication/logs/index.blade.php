@extends('layouts.app') {{-- or admin.layouts.app if that’s your layout --}}

@section('content')
<div class="max-w-7xl mx-auto p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Communication Logs</h1>
    {{-- Optional filters/search can go here --}}
  </div>

  @if (session('success'))
    <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
  @endif

  @if (empty($logs))
    <div class="border rounded p-4 text-gray-600">
      No logs yet. Once WhatsApp messages are sent/received, they’ll appear here.
    </div>
  @else
    <div class="overflow-x-auto border rounded">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left">Time</th>
            <th class="px-4 py-2 text-left">Channel</th>
            <th class="px-4 py-2 text-left">To</th>
            <th class="px-4 py-2 text-left">Template/Subject</th>
            <th class="px-4 py-2 text-left">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @foreach ($logs as $log)
            <tr>
              <td class="px-4 py-2">{{ $log->created_at }}</td>
              <td class="px-4 py-2">{{ $log->channel ?? 'whatsapp' }}</td>
              <td class="px-4 py-2">{{ $log->to ?? '-' }}</td>
              <td class="px-4 py-2">{{ $log->template_name ?? Str::limit($log->body ?? '', 40) }}</td>
              <td class="px-4 py-2">{{ $log->status ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
