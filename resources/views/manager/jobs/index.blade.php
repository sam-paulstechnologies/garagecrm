@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Manager Jobs
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Track jobs, assign team members, update progress, and complete work.
            </p>
        </div>

        <a href="{{ route('manager.dashboard') }}"
           class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
            Back to Dashboard
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="rounded-xl bg-green-50 border border-green-100 p-4 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Errors --}}
    @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-100 p-4 text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <a href="{{ route('manager.jobs.index', ['status' => 'pending']) }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-3xl font-bold text-amber-600 mt-2">
                {{ $counts['pending'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Waiting to start</p>
        </a>

        <a href="{{ route('manager.jobs.index', ['status' => 'in_progress']) }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">In Progress</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">
                {{ $counts['in_progress'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Currently being worked on</p>
        </a>

        <a href="{{ route('manager.jobs.index', ['status' => 'completed']) }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Completed</p>
            <p class="text-3xl font-bold text-green-600 mt-2">
                {{ $counts['completed'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Feedback should trigger</p>
        </a>

        <a href="{{ route('manager.jobs.index', ['status' => 'cancelled']) }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Cancelled</p>
            <p class="text-3xl font-bold text-red-600 mt-2">
                {{ $counts['cancelled'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Cancelled jobs</p>
        </a>

    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border shadow-sm p-4">
        <form method="GET" action="{{ route('manager.jobs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Search
                </label>
                <input type="text"
                       name="q"
                       value="{{ $q ?? '' }}"
                       placeholder="Search job code, customer, status, summary..."
                       class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Status
                </label>
                <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ ($status ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ ($status ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ ($status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                    Apply
                </button>

                <a href="{{ route('manager.jobs.index') }}"
                   class="px-4 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
                    Reset
                </a>
            </div>

        </form>
    </div>

    {{-- Jobs Table --}}
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Job</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Booking</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Assigned To</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($jobs as $job)
                        @php
                            $statusValue = $job->status ?? 'pending';

                            $statusClass = match($statusValue) {
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                'in_progress' => 'bg-blue-50 text-blue-700 border-blue-100',
                                'completed' => 'bg-green-50 text-green-700 border-green-100',
                                'cancelled' => 'bg-red-50 text-red-700 border-red-100',
                                default => 'bg-gray-50 text-gray-700 border-gray-100',
                            };
                        @endphp

                        <tr class="hover:bg-gray-50">

                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">
                                    {{ $job->job_code ?: 'Job #' . $job->id }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ \Illuminate\Support\Str::limit($job->description ?: 'Service job', 55) }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $job->client?->name ?? 'Customer not linked' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $job->client?->phone ?? $job->client?->whatsapp ?? 'No phone' }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                @if($job->booking)
                                    <div class="text-gray-900">
                                        Booking #{{ $job->booking->id }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ optional($job->booking->booking_date)->format('d M Y') ?? $job->booking->booking_date ?? 'No date' }}
                                        @if($job->booking->slot)
                                            · {{ ucfirst(str_replace('_', ' ', $job->booking->slot)) }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-500">No booking linked</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="text-gray-900">
                                    {{ $job->assignedUser?->name ?? 'Not assigned' }}
                                </div>
                                @if($job->assignedUser?->role)
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst($job->assignedUser->role) }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-medium {{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('manager.jobs.show', $job) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-medium hover:bg-gray-800">
                                    Open
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No jobs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        @if(method_exists($jobs, 'links'))
            <div class="p-4 border-t">
                {{ $jobs->links() }}
            </div>
        @endif

    </div>

</div>
@endsection