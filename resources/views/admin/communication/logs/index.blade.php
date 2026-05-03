@extends('layouts.app')

@section('title', 'Communication Logs')

@section('content')
<div class="max-w-7xl mx-auto p-6">

    <h1 class="text-xl font-semibold mb-4">Communication Logs</h1>

    @if($logs->isEmpty())
        <div class="border rounded p-4 text-gray-600">
            No communication logs found.
        </div>
    @else
        <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Channel</th>
                        <th class="px-4 py-2 text-left">Direction</th>
                        <th class="px-4 py-2 text-left">To</th>
                        <th class="px-4 py-2 text-left">Template</th>
                        <th class="px-4 py-2 text-left">Provider SID</th>
                        <th class="px-4 py-2 text-left">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($logs as $log)
                    <tr>
                        <td class="px-4 py-2">
                            {{ optional($log->communication_date)->format('d M Y H:i') }}
                        </td>
                        <td class="px-4 py-2 capitalize">{{ $log->channel }}</td>
                        <td class="px-4 py-2 capitalize">{{ $log->direction }}</td>
                        <td class="px-4 py-2">
                            {{ $log->to_phone ?? $log->to_email ?? '—' }}
                        </td>
                        <td class="px-4 py-2">{{ $log->template ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">
                            {{ $log->provider_sid ?? '—' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ \Illuminate\Support\Str::limit($log->body, 60) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif

</div>
@endsection
