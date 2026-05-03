{{-- resources/views/admin/bookings/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-8 space-y-6">

    <div class="bg-white p-6 rounded shadow">

        <h2 class="text-xl font-semibold mb-4">Booking Details</h2>

        <div class="space-y-3">

            <p><strong>Client:</strong> {{ $booking->client->name ?? 'N/A' }}</p>

            <p><strong>Booking Title:</strong> {{ $booking->name ?? '—' }}</p>

            {{-- Vehicle --}}
            <p>
                <strong>Make:</strong>
                {{ $booking->vehicleData?->make?->name ?? '—' }}
            </p>

            <p>
                <strong>Model:</strong>
                {{ $booking->vehicleData?->model?->name ?? '—' }}
            </p>

            <p>
                <strong>Vehicle:</strong>
                {{ $booking->vehicle_label ?? $booking->opportunity?->vehicle_label ?? '—' }}
            </p>

            {{-- Service --}}
            <p>
                <strong>Service Type:</strong>
                {{ $booking->service_type ?? $booking->opportunity?->service_type ?? '—' }}
            </p>

            <p>
                <strong>Priority:</strong>
                {{ ucfirst($booking->priority ?? '—') }}
            </p>

            <p>
                <strong>Expected Duration (days):</strong>
                {{ $booking->expected_duration ?? '—' }}
            </p>

            <p>
                <strong>Expected Close Date:</strong>
                {{ optional($booking->expected_close_date)->format('Y-m-d') ?? '—' }}
            </p>

            {{-- Date --}}
            <p>
                <strong>Date:</strong>
                {{ optional($booking->booking_date)->format('Y-m-d') ?? '—' }}
            </p>

            <p>
                <strong>Slot:</strong>
                {{ ucfirst($booking->slot ?? '—') }}
            </p>

            {{-- Assigned --}}
            <p>
                <strong>Assigned To:</strong>
                {{ $booking->assignedUser?->name ?? 'Unassigned' }}
            </p>

            <p>
                <strong>Status:</strong>
                {{ ucfirst(str_replace('_', ' ', $booking->status ?? '—')) }}
            </p>


            {{-- Pickup Section --}}
            @if($booking->pickup_required)

                <div class="mt-4 border-t pt-4">

                    <h4 class="text-lg font-semibold mb-2">Pickup Details</h4>

                    <p>
                        <strong>Pickup Address:</strong>
                        {{ $booking->pickup_address ?? '—' }}
                    </p>

                    <p>
                        <strong>Pickup Contact Number:</strong>
                        {{ $booking->pickup_contact_number ?? '—' }}
                    </p>

                </div>

            @endif


            {{-- Notes --}}
            @if($booking->notes)

                <div class="mt-4 border-t pt-4">

                    <h4 class="text-lg font-semibold mb-2">Notes</h4>

                    <p>{{ $booking->notes }}</p>

                </div>

            @endif

        </div>


        <div class="mt-6">

            <a href="{{ route('admin.bookings.edit', $booking->id) }}"
               class="text-blue-600 hover:underline mr-4">
               Edit Booking
            </a>

            <a href="{{ route('admin.bookings.index') }}"
               class="text-gray-600 hover:underline">
               Back to List
            </a>

        </div>

    </div>


    {{-- Communications --}}
    <div class="bg-white p-6 rounded-lg shadow">

        <div class="flex items-center justify-between mb-3">

            <h2 class="text-lg font-semibold">Communications</h2>

            <a href="{{ route('admin.communications.create', [
                    'booking_id' => $booking->id,
                    'client_id'  => $booking->client_id
                ]) }}"
               class="text-sm text-blue-600 underline">
               Add Communication
            </a>

        </div>

        @php

            $communications = \App\Models\Shared\Communication::where('company_id', company_id())
                ->where('booking_id', $booking->id)
                ->orderByDesc('communication_date')
                ->orderByDesc('id')
                ->paginate(10);

        @endphp

        {{-- Communication List --}}
        <div class="mt-4 space-y-2">

            @forelse($communications as $comm)

                <div class="border rounded p-3">

                    <div class="text-sm text-gray-600">
                        {{ $comm->communication_type }}
                        •
                        {{ optional($comm->communication_date)->format('Y-m-d H:i') }}
                    </div>

                    <div class="mt-1">
                        {{ $comm->content }}
                    </div>

                </div>

            @empty

                <p class="text-gray-500 text-sm">No communications yet.</p>

            @endforelse

        </div>

        <div class="mt-4">
            {{ $communications->links() }}
        </div>

    </div>

</div>
@endsection