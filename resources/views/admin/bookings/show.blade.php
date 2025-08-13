@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">Booking Details</h2>

    <div class="space-y-3">
        <p><strong>Client:</strong> {{ $booking->client->name ?? 'N/A' }}</p>

        <p><strong>Booking Title:</strong> {{ $booking->name ?? '—' }}</p>

        <p><strong>Make:</strong>
            {{ $booking->vehicleMake->name ?? $booking->other_make ?? '—' }}
        </p>

        <p><strong>Model:</strong>
            {{ $booking->vehicleModel->name ?? $booking->other_model ?? '—' }}
        </p>

        <p><strong>Service Type:</strong>
            {{ $booking->service_type ? str_replace(',', ', ', $booking->service_type) : '—' }}
        </p>

        <p><strong>Priority:</strong> {{ ucfirst($booking->priority ?? '—') }}</p>

        <p><strong>Expected Duration (days):</strong> {{ $booking->expected_duration ?? '—' }}</p>

        <p><strong>Expected Close Date:</strong>
            {{ optional($booking->expected_close_date)->format('Y-m-d') ?? '—' }}
        </p>

        <p><strong>Date:</strong> {{ optional($booking->date)->format('Y-m-d') ?? '—' }}</p>

        <p><strong>Slot:</strong> {{ ucfirst($booking->slot ?? '—') }}</p>

        <p><strong>Assigned To:</strong> {{ $booking->assignedUser->name ?? 'Unassigned' }}</p>

        <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $booking->status ?? '—')) }}</p>

        @if($booking->pickup_required)
            <div class="mt-4 border-t pt-4">
                <h4 class="text-lg font-semibold mb-2">Pickup Details</h4>
                <p><strong>Pickup Address:</strong> {{ $booking->pickup_address ?? '—' }}</p>
                <p><strong>Pickup Contact Number:</strong> {{ $booking->pickup_contact_number ?? '—' }}</p>
            </div>
        @endif

        @if($booking->notes)
            <div class="mt-4 border-t pt-4">
                <h4 class="text-lg font-semibold mb-2">Notes</h4>
                <p>{{ $booking->notes }}</p>
            </div>
        @endif
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="text-blue-600 hover:underline mr-4">
            Edit Booking
        </a>
        <a href="{{ route('admin.bookings.index') }}" class="text-gray-600 hover:underline">
            Back to List
        </a>
    </div>
</div>
@endsection
