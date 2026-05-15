@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Booking #{{ $booking->id }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Review customer booking and take manager action.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('manager.bookings.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Back to Bookings
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
        $make = $booking->vehicleData?->make?->name;
        $model = $booking->vehicleData?->model?->name;
        $vehicle = trim(implode(' ', array_filter([$make, $model])));
        $statusValue = $booking->status ?? 'pending';

        $statusClass = match($statusValue) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
            'scheduled' => 'bg-blue-50 text-blue-700 border-blue-100',
            'converted_to_job' => 'bg-green-50 text-green-700 border-green-100',
            'lost' => 'bg-red-50 text-red-700 border-red-100',
            default => 'bg-gray-50 text-gray-700 border-gray-100',
        };
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Details --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Booking Summary --}}
            <div class="bg-white rounded-xl border shadow-sm p-5">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Booking Summary
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Current booking and customer request details.
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
                            {{ $booking->client?->name ?? $booking->name ?? 'Customer' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900">
                            {{ $booking->client?->phone ?? $booking->client?->whatsapp ?? 'No phone' }}
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
                        <p class="text-gray-500">Service Type</p>
                        <p class="font-medium text-gray-900">
                            {{ $booking->service_type ?: 'Service booking' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Priority</p>
                        <p class="font-medium text-gray-900">
                            {{ ucfirst($booking->priority ?? 'medium') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Assigned To</p>
                        <p class="font-medium text-gray-900">
                            {{ $booking->assignedUser?->name ?? 'Not assigned' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Expected Close Date</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($booking->expected_close_date)->format('d M Y') ?? $booking->expected_close_date ?? 'Not set' }}
                        </p>
                    </div>

                </div>

                @if($booking->notes)
                    <div class="mt-5 pt-5 border-t">
                        <p class="text-sm text-gray-500 mb-1">Notes</p>
                        <div class="rounded-lg bg-gray-50 border p-3 text-sm text-gray-700 whitespace-pre-line">
                            {{ $booking->notes }}
                        </div>
                    </div>
                @endif

            </div>

            {{-- Vehicle --}}
            <div class="bg-white rounded-xl border shadow-sm p-5">

                <h2 class="text-lg font-semibold text-gray-900">
                    Vehicle
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 text-sm">

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

                    <div>
                        <p class="text-gray-500">VIN</p>
                        <p class="font-medium text-gray-900">
                            {{ $booking->vehicleData?->vin ?? 'Not available' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Year</p>
                        <p class="font-medium text-gray-900">
                            {{ $booking->vehicleData?->year ?? 'Not available' }}
                        </p>
                    </div>

                </div>

            </div>

            {{-- Opportunity --}}
            <div class="bg-white rounded-xl border shadow-sm p-5">

                <h2 class="text-lg font-semibold text-gray-900">
                    Opportunity
                </h2>

                @if($booking->opportunity)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 text-sm">

                        <div>
                            <p class="text-gray-500">Opportunity</p>
                            <p class="font-medium text-gray-900">
                                {{ $booking->opportunity->title ?? 'Opportunity #' . $booking->opportunity->id }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Stage</p>
                            <p class="font-medium text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $booking->opportunity->stage ?? '')) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Converted</p>
                            <p class="font-medium text-gray-900">
                                {{ $booking->opportunity->is_converted ? 'Yes' : 'No' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-gray-500">Source</p>
                            <p class="font-medium text-gray-900">
                                {{ ucfirst($booking->opportunity->source ?? 'Unknown') }}
                            </p>
                        </div>

                    </div>
                @else
                    <p class="text-sm text-gray-500 mt-3">
                        No opportunity linked.
                    </p>
                @endif

            </div>

        </div>

        {{-- Actions --}}
        <aside class="space-y-6">

            <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">

                <h2 class="text-lg font-semibold text-gray-900">
                    Manager Actions
                </h2>

                @if($statusValue === 'pending')
                    <form method="POST"
                          action="{{ route('manager.bookings.confirm', $booking) }}"
                          onsubmit="return confirm('Confirm this booking and notify the customer?');">
                        @csrf

                        <button type="submit"
                                class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                            Confirm Booking
                        </button>
                    </form>
                @endif

                @if(in_array($statusValue, ['pending', 'scheduled'], true))
                    <form method="POST"
                          action="{{ route('manager.bookings.convert-to-job', $booking) }}"
                          onsubmit="return confirm('Convert this booking into a job?');">
                        @csrf

                        <button type="submit"
                                class="w-full px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">
                            Convert To Job
                        </button>
                    </form>
                @endif

                @if($job)
                    <div class="rounded-lg bg-green-50 border border-green-100 p-3 text-sm text-green-800">
                        This booking is already linked to Job #{{ $job->id }}.
                    </div>

                    @if(\Illuminate\Support\Facades\Route::has('manager.jobs.show'))
                        <a href="{{ route('manager.jobs.show', $job) }}"
                           class="block w-full text-center px-4 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
                            Open Job
                        </a>
                    @endif
                @endif

                @if(!in_array($statusValue, ['lost', 'converted_to_job'], true))
                    <div class="border-t pt-4">

                        <h3 class="text-sm font-semibold text-gray-900 mb-2">
                            Reject / Mark Lost
                        </h3>

                        <form method="POST"
                              action="{{ route('manager.bookings.reject', $booking) }}"
                              class="space-y-3"
                              onsubmit="return confirm('Reject this booking?');">
                            @csrf

                            <select name="lost_reason"
                                    class="w-full border rounded-lg px-3 py-2 text-sm"
                                    required>
                                <option value="">Select reason</option>
                                <option value="cancelled_by_customer">Cancelled by customer</option>
                                <option value="rejected_by_garage">Rejected by garage</option>
                                <option value="no_show">No show</option>
                                <option value="slot_unavailable">Slot unavailable</option>
                                <option value="duplicate">Duplicate</option>
                                <option value="wrong_booking">Wrong booking</option>
                                <option value="price_issue">Price issue</option>
                                <option value="customer_postponed">Customer postponed</option>
                                <option value="other">Other</option>
                            </select>

                            <textarea name="notes"
                                      rows="3"
                                      class="w-full border rounded-lg px-3 py-2 text-sm"
                                      placeholder="Optional note"></textarea>

                            <button type="submit"
                                    class="w-full px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                                Reject Booking
                            </button>
                        </form>

                    </div>
                @endif

            </div>

            <div class="bg-white rounded-xl border shadow-sm p-5">

                <h2 class="text-lg font-semibold text-gray-900">
                    Status Timeline
                </h2>

                <div class="mt-4 space-y-3 text-sm">

                    <div>
                        <p class="text-gray-500">Created</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($booking->created_at)->format('d M Y, h:i A') ?? 'Not available' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Confirmed At</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($booking->confirmed_at)->format('d M Y, h:i A') ?? 'Not confirmed' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Completed At</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($booking->completed_at)->format('d M Y, h:i A') ?? 'Not completed' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Last Changed</p>
                        <p class="font-medium text-gray-900">
                            {{ optional($booking->state_changed_at)->format('d M Y, h:i A') ?? 'Not available' }}
                        </p>
                    </div>

                </div>

            </div>

        </aside>

    </div>

</div>
@endsection