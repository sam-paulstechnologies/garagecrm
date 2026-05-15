@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Manager Dashboard
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Track escalations, bookings, jobs, leads, and customer conversations.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('manager.escalations') }}"
               class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                View Escalations
            </a>

            <a href="{{ route('manager.bookings.index') }}"
               class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                View Bookings
            </a>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <a href="{{ route('manager.escalations') }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Human Escalations</p>
            <p class="text-3xl font-bold text-red-600 mt-2">
                {{ $stats['human_escalations'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Customers waiting for manager
            </p>
        </a>

        <a href="{{ route('manager.bookings.index') }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Pending Bookings</p>
            <p class="text-3xl font-bold text-amber-600 mt-2">
                {{ $stats['pending_bookings'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Need confirmation
            </p>
        </a>

        <a href="{{ route('manager.bookings.index') }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Scheduled Bookings</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">
                {{ $stats['scheduled_bookings'] ?? 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Confirmed appointments
            </p>
        </a>

        <a href="{{ route('manager.jobs.index') }}"
           class="bg-white rounded-xl border shadow-sm p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Active Jobs</p>
            <p class="text-3xl font-bold text-green-600 mt-2">
                {{ ($stats['jobs_pending'] ?? 0) + ($stats['jobs_in_progress'] ?? 0) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Pending / in progress
            </p>
        </a>

    </div>

    {{-- Secondary Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <a href="{{ route('manager.leads.index') }}"
           class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Open Leads</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ $stats['open_leads'] ?? 0 }}
            </p>
        </a>

        <a href="{{ route('manager.bookings.index') }}"
           class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Converted Bookings</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ $stats['converted_bookings'] ?? 0 }}
            </p>
        </a>

        <a href="{{ route('manager.jobs.index') }}"
           class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Completed Jobs</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ $stats['jobs_completed'] ?? 0 }}
            </p>
        </a>

        <a href="{{ route('manager.escalations') }}"
           class="bg-white rounded-xl border shadow-sm p-4 hover:shadow-md transition">
            <p class="text-sm text-gray-500">Unread Messages</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ $stats['unread_messages'] ?? 0 }}
            </p>
        </a>

    </div>

    {{-- Main Work Queues --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Pending Bookings --}}
        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-900">Pending / Scheduled Bookings</h2>
                    <p class="text-xs text-gray-500 mt-1">Latest booking actions</p>
                </div>

                <a href="{{ route('manager.bookings.index') }}"
                   class="text-sm text-blue-600 hover:underline">
                    View all
                </a>
            </div>

            <div class="divide-y">
                @forelse($pendingBookings ?? [] as $booking)
                    <div class="p-4">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $booking->name ?? 'Customer' }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $booking->booking_date ?? 'No date' }}
                                    @if(!empty($booking->slot))
                                        · {{ ucfirst(str_replace('_', ' ', $booking->slot)) }}
                                    @endif
                                </p>
                            </div>

                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 h-fit">
                                {{ ucfirst(str_replace('_', ' ', $booking->status ?? 'pending')) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-sm text-gray-500">
                        No pending bookings.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Escalated Leads --}}
        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-900">Escalated Leads</h2>
                    <p class="text-xs text-gray-500 mt-1">Customers needing human takeover</p>
                </div>

                <a href="{{ route('manager.escalations') }}"
                   class="text-sm text-blue-600 hover:underline">
                    View all
                </a>
            </div>

            <div class="divide-y">
                @forelse($escalatedLeads ?? [] as $lead)
                    <div class="p-4">
                        <p class="font-medium text-gray-900">
                            {{ $lead->name ?? 'Lead #' . $lead->id }}
                        </p>

                        <p class="text-sm text-gray-500">
                            {{ $lead->phone_norm ?? $lead->phone ?? 'No phone' }}
                        </p>

                        <div class="mt-2">
                            <a href="{{ route('manager.conversation', $lead->id) }}"
                               class="text-sm text-blue-600 hover:underline">
                                Open conversation
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-sm text-gray-500">
                        No escalations right now.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Active Jobs --}}
        <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-900">Active Jobs</h2>
                    <p class="text-xs text-gray-500 mt-1">Pending and in-progress jobs</p>
                </div>

                <a href="{{ route('manager.jobs.index') }}"
                   class="text-sm text-blue-600 hover:underline">
                    View all
                </a>
            </div>

            <div class="divide-y">
                @forelse($activeJobs ?? [] as $job)
                    <div class="p-4">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $job->job_code ?? 'Job #' . $job->id }}
                                </p>

                                <p class="text-sm text-gray-500">
                                    {{ $job->description ?? 'Service job' }}
                                </p>
                            </div>

                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 h-fit">
                                {{ ucfirst(str_replace('_', ' ', $job->status ?? 'pending')) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-sm text-gray-500">
                        No active jobs.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Quick Links --}}
    <div class="bg-white rounded-xl border shadow-sm p-5">
        <h2 class="font-semibold text-gray-900 mb-4">
            Manager Work Areas
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">

            <a href="{{ route('manager.escalations') }}"
               class="border rounded-lg p-3 text-center hover:bg-gray-50">
                <div class="text-xl">🔥</div>
                <div class="text-sm font-medium mt-1">Escalations</div>
            </a>

            <a href="{{ route('manager.bookings.index') }}"
               class="border rounded-lg p-3 text-center hover:bg-gray-50">
                <div class="text-xl">📅</div>
                <div class="text-sm font-medium mt-1">Bookings</div>
            </a>

            <a href="{{ route('manager.jobs.index') }}"
               class="border rounded-lg p-3 text-center hover:bg-gray-50">
                <div class="text-xl">🛠️</div>
                <div class="text-sm font-medium mt-1">Jobs</div>
            </a>

            <a href="{{ route('manager.leads.index') }}"
               class="border rounded-lg p-3 text-center hover:bg-gray-50">
                <div class="text-xl">📈</div>
                <div class="text-sm font-medium mt-1">Leads</div>
            </a>

            <a href="{{ route('manager.opportunities.index') }}"
               class="border rounded-lg p-3 text-center hover:bg-gray-50">
                <div class="text-xl">🎯</div>
                <div class="text-sm font-medium mt-1">Opportunities</div>
            </a>

            <a href="{{ route('manager.clients.index') }}"
               class="border rounded-lg p-3 text-center hover:bg-gray-50">
                <div class="text-xl">👤</div>
                <div class="text-sm font-medium mt-1">Clients</div>
            </a>

            <a href="{{ route('manager.team.index') }}"
               class="border rounded-lg p-3 text-center hover:bg-gray-50">
                <div class="text-xl">👥</div>
                <div class="text-sm font-medium mt-1">Team</div>
            </a>

        </div>
    </div>

</div>
@endsection