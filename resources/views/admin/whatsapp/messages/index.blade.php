@extends('layouts.app')

@section('title', 'WhatsApp Message Logs')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">📱 WhatsApp Message Logs</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:underline">← Back to Dashboard</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dir</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Template</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Body</th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($messages as $msg)
                    <tr>
                        <td class="px-3 py-3 text-sm text-gray-700">{{ $msg->id }}</td>
                        <td class="px-3 py-3 text-sm text-gray-500">{{ optional($msg->created_at)->format('Y-m-d H:i:s') }}</td>
                        <td class="px-3 py-3 text-sm">
                            <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">
                                {{ $msg->direction ?? 'out' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-700">{{ $msg->to_number }}</td>
                        <td class="px-3 py-3 text-sm text-gray-700">{{ $msg->from_number ?? '—' }}</td>
                        <td class="px-3 py-3 text-sm text-gray-700">{{ $msg->template ?? '—' }}</td>
                        <td class="px-3 py-3 text-sm">
                            @php $st = strtolower($msg->provider_status ?? 'queued'); @endphp
                            <span class="px-2 py-0.5 text-xs rounded
                                {{ $st === 'delivered' || $st === 'read' ? 'bg-green-100 text-green-800' :
                                   ($st === 'failed' || $st === 'undelivered' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $st }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-700 truncate max-w-[24rem]">
                            {{ Str::limit($msg->body, 120) }}
                        </td>
                        <td class="px-3 py-3 text-right">
                            <a href="{{ route('admin.whatsapp.logs.show', $msg->id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500 text-sm">
                            No WhatsApp logs yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $messages->links() }}
    </div>
</div>
@endsection
