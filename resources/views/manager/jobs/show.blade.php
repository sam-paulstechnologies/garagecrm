@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $job->job_code ?: 'Job #' . $job->id }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Review job details, assign team member, update progress, and complete the job.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('manager.jobs.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Back to Jobs
            </a>

            <a href="{{ route('manager.dashboard') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Dashboard
            </a>
        </div>
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

    @php
        $statusValue = $job->status ?? 'pending';

        $statusClass = match($statusValue) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
            'in_progress' => 'bg-blue-50 text-blue-700 border-blue-100',
            'completed' => 'bg-green-50 text-green-700 border-green-100',
            'cancelled' => 'bg-red-50 text-red-700 border-red-100',
            default => 'bg-gray-50 text-gray-700 border-gray-100',
        };

        $booking = $job->booking;
        $make = $booking?->vehicleData?->make?->name;
        $model = $booking?->vehicleData?->model?->name;
        $vehicle = trim(implode(' ', array_filter([$make, $model])));
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Details --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Job Summary --}}
            <div class="bg-white rounded-xl border shadow-sm p-5">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Job Summary
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Current job status and linked customer information.
                        </p>
                    </div>

                    <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-medium {{ $statusClass }}">
                        {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 text-sm">

                    <div>
                        <p class="text-gray-500">Customer</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->client?->name ?? 'Customer not linked' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->client?->phone ?? $job->client?->whatsapp ?? 'No phone' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Assigned To</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->assignedUser?->name ?? 'Not assigned' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Created</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($job->created_at)->format('d M Y, h:i A') ?? 'Not available' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Start Time</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($job->start_time)->format('d M Y, h:i A') ?? $job->start_time ?? 'Not started' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">End Time</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($job->end_time)->format('d M Y, h:i A') ?? $job->end_time ?? 'Not completed' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Vehicle Mileage</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->vehicle_mileage ? number_format($job->vehicle_mileage) . ' km' : 'Not entered' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Total Time</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->total_time_minutes ? $job->total_time_minutes . ' minutes' : 'Not entered' }}
                        </p>
                    </div>

                </div>

                <div class="mt-5 pt-5 border-t">
                    <p class="text-sm text-gray-500 mb-1">Description</p>
                    <div class="rounded-lg bg-gray-50 border p-3 text-sm text-gray-700 whitespace-pre-line">
                        {{ $job->description ?: 'No description added.' }}
                    </div>
                </div>

            </div>

            {{-- Booking / Vehicle --}}
            <div class="bg-white rounded-xl border shadow-sm p-5">

                <h2 class="text-lg font-semibold text-gray-900">
                    Linked Booking & Vehicle
                </h2>

                @if($booking)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 text-sm">

                        <div>
                            <p class="text-gray-500">Booking</p>
                            <p class="font-medium text-gray-900">
                                Booking #{{ $booking->id }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Booking Status</p>
                            <p class="font-medium text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $booking->status ?? '')) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Booking Date</p>
                            <p class="font-medium text-gray-900">
                                {{ optional($booking->booking_date)->format('d M Y') ?? $booking->booking_date ?? 'No date' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Slot</p>
                            <p class="font-medium text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $booking->slot ?? '')) ?: 'No slot' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Vehicle</p>
                            <p class="font-medium text-gray-900">
                                {{ $vehicle ?: 'Vehicle not linked' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Plate Number</p>
                            <p class="font-medium text-gray-900">
                                {{ $booking->vehicleData?->plate_number ?? 'Not available' }}
                            </p>
                        </div>

                    </div>

                    <div class="mt-5">
                        <a href="{{ route('manager.bookings.show', $booking) }}"
                           class="inline-flex items-center px-4 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
                            Open Booking
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-500 mt-3">
                        No booking linked to this job.
                    </p>
                @endif

            </div>

            {{-- Work Details Form --}}
            <div class="bg-white rounded-xl border shadow-sm p-5">

                <h2 class="text-lg font-semibold text-gray-900">
                    Work Details
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Update job notes, issues found, parts used, mileage, and time spent.
                </p>

                <form method="POST"
                      action="{{ route('manager.jobs.work-details', $job) }}"
                      class="mt-5 space-y-4">

                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea name="description"
                                  rows="3"
                                  class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('description', $job->description) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Work Summary
                        </label>
                        <textarea name="work_summary"
                                  rows="4"
                                  class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('work_summary', $job->work_summary) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Issues Found
                        </label>
                        <textarea name="issues_found"
                                  rows="3"
                                  class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('issues_found', $job->issues_found) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Parts Used
                        </label>
                        <textarea name="parts_used"
                                  rows="3"
                                  class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('parts_used', $job->parts_used) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Vehicle Mileage
                            </label>
                            <input type="number"
                                   name="vehicle_mileage"
                                   min="0"
                                   class="w-full border rounded-lg px-3 py-2 text-sm"
                                   value="{{ old('vehicle_mileage', $job->vehicle_mileage) }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Total Time Minutes
                            </label>
                            <input type="number"
                                   name="total_time_minutes"
                                   min="0"
                                   class="w-full border rounded-lg px-3 py-2 text-sm"
                                   value="{{ old('total_time_minutes', $job->total_time_minutes) }}">
                        </div>

                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-5 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                            Save Work Details
                        </button>
                    </div>

                </form>

            </div>

        </div>

        {{-- Actions --}}
        <aside class="space-y-6">

            {{-- Status --}}
            <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">

                <h2 class="text-lg font-semibold text-gray-900">
                    Job Status
                </h2>

                <form method="POST"
                      action="{{ route('manager.jobs.status', $job) }}"
                      class="space-y-3"
                      onsubmit="return confirm('Update this job status?');">

                    @csrf
                    @method('PATCH')

                    <select name="status"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            required>
                        <option value="pending" {{ $statusValue === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ $statusValue === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $statusValue === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $statusValue === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>

                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Update Status
                    </button>

                </form>

                @if($statusValue !== 'completed')
                    <form method="POST"
                          action="{{ route('manager.jobs.status', $job) }}"
                          onsubmit="return confirm('Mark this job as completed? This should trigger the feedback flow.');">
                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="status" value="completed">

                        <button type="submit"
                                class="w-full px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">
                            Complete Job
                        </button>
                    </form>
                @endif

                @if($statusValue === 'completed')
                    <div class="rounded-lg bg-green-50 border border-green-100 p-3 text-sm text-green-800">
                        Job is completed. Feedback flow should be triggered if WhatsApp and queue are active.
                    </div>
                @endif

            </div>

            {{-- Assignment --}}
            <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">

                <h2 class="text-lg font-semibold text-gray-900">
                    Assignment
                </h2>

                <form method="POST"
                      action="{{ route('manager.jobs.assign', $job) }}"
                      class="space-y-3">

                    @csrf
                    @method('PATCH')

                    <select name="assigned_to"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Unassigned</option>

                        @foreach($teamMembers ?? [] as $member)
                            <option value="{{ $member->id }}"
                                {{ (int) old('assigned_to', $job->assigned_to) === (int) $member->id ? 'selected' : '' }}>
                                {{ $member->name }} @if($member->role) — {{ ucfirst($member->role) }} @endif
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                        Update Assignment
                    </button>

                </form>

            </div>

            {{-- Timeline --}}
            <div class="bg-white rounded-xl border shadow-sm p-5">

                <h2 class="text-lg font-semibold text-gray-900">
                    Timeline
                </h2>

                <div class="mt-4 space-y-3 text-sm">

                    <div>
                        <p class="text-gray-500">Created</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($job->created_at)->format('d M Y, h:i A') ?? 'Not available' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Started</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($job->start_time)->format('d M Y, h:i A') ?? $job->start_time ?? 'Not started' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Completed</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($job->end_time)->format('d M Y, h:i A') ?? $job->end_time ?? 'Not completed' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Last Updated</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($job->updated_at)->format('d M Y, h:i A') ?? 'Not available' }}
                        </p>
                    </div>

                </div>

            </div>

        </aside>

    </div>

</div>
@endsection