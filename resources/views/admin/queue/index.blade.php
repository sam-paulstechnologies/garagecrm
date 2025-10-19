@extends('layouts.app') {{-- or your admin layout --}}

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Queue Monitor</h1>
        <div class="text-sm text-gray-500">
            Server time: {{ now()->toDayDateTimeString() }}
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @unless ($metrics['has_jobs_table'])
        <div class="mb-6 rounded-md bg-yellow-50 p-4 text-yellow-800">
            <p class="font-medium">Jobs table not found.</p>
            <p>Create it with:
                <code class="bg-black/5 px-2 py-1 rounded">php artisan queue:table && php artisan migrate</code>
            </p>
        </div>
    @endunless

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="rounded-2xl border p-4">
            <div class="text-sm text-gray-500">Queued (ready)</div>
            <div class="text-2xl font-bold">{{ $metrics['queued'] }}</div>
        </div>
        <div class="rounded-2xl border p-4">
            <div class="text-sm text-gray-500">Processing (reserved)</div>
            <div class="text-2xl font-bold">{{ $metrics['reserved'] }}</div>
        </div>
        <div class="rounded-2xl border p-4">
            <div class="text-sm text-gray-500">Delayed</div>
            <div class="text-2xl font-bold">{{ $metrics['delayed'] }}</div>
        </div>
        <div class="rounded-2xl border p-4">
            <div class="text-sm text-gray-500">Failed</div>
            <div class="text-2xl font-bold">{{ $metrics['failed'] }}</div>
        </div>
        <div class="rounded-2xl border p-4">
            <div class="text-sm text-gray-500">Total in Jobs Table</div>
            <div class="text-2xl font-bold">{{ $metrics['total'] }}</div>
            @if($jobsTable)
                <div class="text-xs text-gray-400 mt-1">Table: {{ $jobsTable }}</div>
            @endif
        </div>
    </div>

    {{-- Actions for failed jobs --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <form method="POST" action="{{ route('admin.queue.retryAll') }}">
            @csrf
            <button class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Retry All Failed</button>
        </form>
        <form method="POST" action="{{ route('admin.queue.flush') }}">
            @csrf
            <button class="px-3 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">Flush All Failed</button>
        </form>
    </div>

    {{-- Failed jobs table --}}
    <div class="rounded-2xl border overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 font-medium">Recent Failed Jobs</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">ID</th>
                        <th class="px-4 py-2 text-left font-semibold">Connection / Queue</th>
                        <th class="px-4 py-2 text-left font-semibold">Exception</th>
                        <th class="px-4 py-2 text-left font-semibold">Failed At</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($failedJobs as $fj)
                        @php
                            // exception payload might be long; show first line only
                            $ex = $fj->exception ?? '';
                            $firstLine = strtok($ex, "\n");
                        @endphp
                        <tr>
                            <td class="px-4 py-2 align-top">{{ $fj->id }}</td>
                            <td class="px-4 py-2 align-top">
                                <div class="text-gray-900">{{ $fj->connection ?? '—' }}</div>
                                <div class="text-gray-500 text-xs">{{ $fj->queue ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-2 align-top">
                                <div class="text-gray-900">{{ \Illuminate\Support\Str::limit($firstLine, 140) }}</div>
                            </td>
                            <td class="px-4 py-2 align-top text-gray-600">{{ \Carbon\Carbon::parse($fj->failed_at)->toDayDateTimeString() }}</td>
                            <td class="px-4 py-2 align-top">
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.queue.retry', $fj->id) }}">
                                        @csrf
                                        <button class="px-2 py-1 rounded-md bg-blue-600 text-white text-xs hover:bg-blue-700">Retry</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.queue.forget', $fj->id) }}">
                                        @csrf
                                        <button class="px-2 py-1 rounded-md bg-gray-200 text-gray-800 text-xs hover:bg-gray-300">Forget</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No failed jobs 🎉</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Optional help --}}
    <div class="mt-6 text-xs text-gray-500">
        Tips: Worker must be running (WebJob/Daemon). Delayed = available_at &gt; now(). Queued = ready to pick up. Reserved = being processed.
    </div>
</div>
@endsection
