@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">WhatsApp Message Logs</h1>
        <a href="{{ route('admin.whatsapp.logs.export.csv', request()->query()) }}"
           class="inline-flex items-center px-3 py-2 border rounded-md text-sm">
            Export CSV
        </a>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4">
        <input type="text" name="company_id" value="{{ $filters['company_id'] ?? '' }}" placeholder="Company ID"
               class="border rounded px-3 py-2">
        <input type="text" name="lead_id" value="{{ $filters['lead_id'] ?? '' }}" placeholder="Lead ID"
               class="border rounded px-3 py-2">
        <select name="direction" class="border rounded px-3 py-2">
            <option value="">Direction</option>
            <option value="out" @selected(($filters['direction'] ?? '')==='out')>Outbound</option>
            <option value="in"  @selected(($filters['direction'] ?? '')==='in')>Inbound</option>
        </select>
        <input type="text" name="phone" value="{{ $filters['phone'] ?? '' }}" placeholder="Phone (to/from)"
               class="border rounded px-3 py-2">
        <input type="text" name="template" value="{{ $filters['template'] ?? '' }}" placeholder="Template"
               class="border rounded px-3 py-2">
        <div class="flex gap-2">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="border rounded px-3 py-2 w-full">
            <input type="date" name="to"   value="{{ $filters['to'] ?? '' }}"   class="border rounded px-3 py-2 w-full">
        </div>
        <div class="md:col-span-6 flex gap-2">
            <button class="px-4 py-2 rounded bg-gray-800 text-white">Filter</button>
            <a href="{{ route('admin.whatsapp.logs.index') }}" class="px-4 py-2 rounded border">Reset</a>
        </div>
    </form>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">When</th>
                    <th class="px-4 py-2 text-left">Company</th>
                    <th class="px-4 py-2 text-left">Lead</th>
                    <th class="px-4 py-2 text-left">Dir</th>
                    <th class="px-4 py-2 text-left">To</th>
                    <th class="px-4 py-2 text-left">From</th>
                    <th class="px-4 py-2 text-left">Template</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Body</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $log->id }}</td>
                        <td class="px-4 py-2">{{ $log->created_at }}</td>
                        <td class="px-4 py-2">{{ $log->company_id }}</td>
                        <td class="px-4 py-2">{{ $log->lead_id ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-block px-2 py-0.5 rounded text-xs
                                {{ $log->direction === 'out' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                {{ $log->direction }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $log->to_number }}</td>
                        <td class="px-4 py-2">{{ $log->from_number }}</td>
                        <td class="px-4 py-2">{{ $log->template ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $log->provider_status ?? '—' }}</td>
                        <td class="px-4 py-2 max-w-xs truncate" title="{{ $log->body }}">{{ $log->body }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.whatsapp.logs.show', $log) }}" class="text-indigo-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-6 text-center text-gray-500" colspan="11">No logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
@endsection
