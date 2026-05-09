{{-- resources/views/admin/bookings/show.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    $status = strtolower((string) ($booking->status ?? ''));
    $priority = strtolower((string) ($booking->priority ?? ''));
    $slot = strtolower((string) ($booking->slot ?? ''));

    $statusBadge = match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'confirmed', 'approved' => 'bg-green-100 text-green-800 border-green-200',
        'completed', 'converted_to_job' => 'bg-blue-100 text-blue-800 border-blue-200',
        'cancelled', 'rejected' => 'bg-red-100 text-red-800 border-red-200',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };

    $priorityBadge = match ($priority) {
        'urgent' => 'bg-red-100 text-red-800 border-red-200',
        'high' => 'bg-orange-100 text-orange-800 border-orange-200',
        'medium' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'low' => 'bg-gray-100 text-gray-700 border-gray-200',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };

    $slotBadge = match ($slot) {
        'morning' => 'bg-blue-100 text-blue-800 border-blue-200',
        'afternoon' => 'bg-purple-100 text-purple-800 border-purple-200',
        'evening' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };

    $bookingDate = !empty($booking->booking_date)
        ? \Illuminate\Support\Carbon::parse($booking->booking_date)
        : null;

    $expectedCloseDate = !empty($booking->expected_close_date)
        ? \Illuminate\Support\Carbon::parse($booking->expected_close_date)
        : null;

    $vehicleLabel = $booking->vehicle_label
        ?? $booking->opportunity?->vehicle_label
        ?? trim(
            ($booking->vehicleData?->make?->name ?? '') . ' ' .
            ($booking->vehicleData?->model?->name ?? '')
        );

    $vehicleLabel = trim($vehicleLabel);

    $serviceType = $booking->service_type ?? $booking->opportunity?->service_type ?? null;

    $services = collect(explode(',', (string) $serviceType))
        ->map(fn ($service) => trim($service))
        ->filter()
        ->values();

    $nextAction = match (true) {
        $status === 'pending' => 'Confirm booking / prepare job',
        in_array($status, ['confirmed', 'approved'], true) => 'Create job',
        in_array($status, ['completed', 'converted_to_job'], true) => 'Review job',
        in_array($status, ['cancelled', 'rejected'], true) => 'No action required',
        default => 'Review booking',
    };

    $communications = \App\Models\Shared\Communication::where('company_id', company_id())
        ->where('booking_id', $booking->id)
        ->orderByDesc('communication_date')
        ->orderByDesc('id')
        ->paginate(10);
@endphp

<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-6 space-y-6">

        {{-- Back --}}
        <div>
            <a href="{{ route('admin.bookings.index') }}"
               class="text-sm text-blue-600 hover:underline">
                ← Back to Bookings
            </a>
        </div>

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <h1 class="text-2xl font-semibold text-gray-900">
                            {{ $booking->name ?? 'Booking #'.$booking->id }}
                        </h1>

                        <span class="inline-flex px-2.5 py-1 rounded-full border text-xs font-semibold {{ $statusBadge }}">
                            {{ ucfirst(str_replace('_', ' ', $booking->status ?? 'Pending')) }}
                        </span>

                        <span class="inline-flex px-2.5 py-1 rounded-full border text-xs font-semibold {{ $priorityBadge }}">
                            {{ ucfirst($booking->priority ?? 'Medium') }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                        <span>👤 {{ $booking->client?->name ?? 'No client' }}</span>
                        <span>🚗 {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}</span>
                        <span>📅 {{ $bookingDate ? $bookingDate->format('d M Y') : 'No date' }}</span>
                        <span>🕒 {{ ucfirst($booking->slot ?? 'No slot') }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.bookings.edit', $booking->id) }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Edit Booking
                    </a>

                    @if($booking->client_id && Route::has('admin.clients.show'))
                        <a href="{{ route('admin.clients.show', $booking->client_id) }}"
                           class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                            View Client
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Status Strip --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Booking Status
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Booking is the confirmed customer appointment before job creation.
                    </p>
                </div>

                <div class="text-sm text-gray-600">
                    <span class="font-medium">Next Action:</span>
                    <span class="text-blue-600">{{ $nextAction }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-5">
                <div class="rounded-lg border p-4 {{ $status === 'pending' ? 'bg-yellow-50 border-yellow-200' : 'bg-gray-50 border-gray-100' }}">
                    <div class="text-xs text-gray-500">Step 1</div>
                    <div class="text-sm font-semibold mt-1">Pending</div>
                </div>

                <div class="rounded-lg border p-4 {{ in_array($status, ['confirmed', 'approved'], true) ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-100' }}">
                    <div class="text-xs text-gray-500">Step 2</div>
                    <div class="text-sm font-semibold mt-1">Confirmed</div>
                </div>

                <div class="rounded-lg border p-4 {{ in_array($status, ['completed', 'converted_to_job'], true) ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-100' }}">
                    <div class="text-xs text-gray-500">Step 3</div>
                    <div class="text-sm font-semibold mt-1">Job Created / Completed</div>
                </div>

                <div class="rounded-lg border p-4 {{ in_array($status, ['cancelled', 'rejected'], true) ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-100' }}">
                    <div class="text-xs text-gray-500">Exception</div>
                    <div class="text-sm font-semibold mt-1">Cancelled / Rejected</div>
                </div>
            </div>
        </div>

        {{-- Main Layout --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            {{-- Left --}}
            <div class="xl:col-span-2 space-y-6">

                {{-- Booking Summary --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Booking Summary
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">Booking ID</div>
                            <div class="font-semibold text-gray-900">#{{ $booking->id }}</div>
                        </div>

                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">Booking Date</div>
                            <div class="font-semibold text-gray-900">
                                {{ $bookingDate ? $bookingDate->format('d M Y') : '—' }}
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">Slot</div>
                            <span class="inline-flex px-2.5 py-1 rounded-full border text-xs font-semibold {{ $slotBadge }}">
                                {{ ucfirst($booking->slot ?? '—') }}
                            </span>
                        </div>

                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">Expected Duration</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->expected_duration ?? '—' }} {{ $booking->expected_duration ? 'day(s)' : '' }}
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">Expected Close Date</div>
                            <div class="font-semibold text-gray-900">
                                {{ $expectedCloseDate ? $expectedCloseDate->format('d M Y') : '—' }}
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                            <div class="text-xs text-gray-500 mb-1">Assigned To</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->assignedUser?->name ?? 'Unassigned' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Service Type --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Service Type(s)
                    </h2>

                    @if($services->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($services as $service)
                                <span class="inline-flex px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                                    {{ $service }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No service type added.</p>
                    @endif
                </div>

                {{-- Pickup Details --}}
                @if($booking->pickup_required)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            Pickup Details
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                                <div class="text-xs text-gray-500 mb-1">Pickup Address</div>
                                <div class="font-medium text-gray-900">
                                    {{ $booking->pickup_address ?? '—' }}
                                </div>
                            </div>

                            <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                                <div class="text-xs text-gray-500 mb-1">Pickup Contact Number</div>
                                <div class="font-medium text-gray-900">
                                    {{ $booking->pickup_contact_number ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Notes --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Notes
                    </h2>

                    @if($booking->notes)
                        <p class="text-sm text-gray-700 whitespace-pre-line">
                            {{ $booking->notes }}
                        </p>
                    @else
                        <p class="text-sm text-gray-500">
                            No notes added.
                        </p>
                    @endif
                </div>

                {{-- Communications --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Communications
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">
                                Customer communication linked to this booking.
                            </p>
                        </div>

                        <a href="{{ route('admin.communications.create', [
                                'booking_id' => $booking->id,
                                'client_id'  => $booking->client_id
                            ]) }}"
                           class="text-sm text-blue-600 hover:underline">
                            Add Communication
                        </a>
                    </div>

                    <div class="space-y-3">
                        @forelse($communications as $comm)
                            <div class="border border-gray-100 rounded-lg p-4 bg-gray-50">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $comm->communication_type }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ optional($comm->communication_date)->format('d M Y H:i') ?? '—' }}
                                    </div>
                                </div>

                                <div class="mt-2 text-sm text-gray-700 whitespace-pre-line">
                                    {{ $comm->content }}
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">
                                No communications yet.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        {{ $communications->links() }}
                    </div>
                </div>
            </div>

            {{-- Right Sidebar --}}
            <div class="space-y-6">

                {{-- Customer --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Customer
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Client</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->client?->name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Phone</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->client?->phone ?? $booking->client?->whatsapp ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Email</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->client?->email ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vehicle --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Vehicle
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Vehicle</div>
                            <div class="font-semibold text-gray-900">
                                {{ $vehicleLabel !== '' ? $vehicleLabel : '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Make</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->vehicleData?->make?->name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Model</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->vehicleData?->model?->name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Plate Number</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->vehicleData?->plate_number ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Opportunity --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Source Opportunity
                    </h2>

                    @if($booking->opportunity)
                        <div class="space-y-3 text-sm">
                            <div>
                                <div class="text-xs text-gray-500">Opportunity</div>
                                <div class="font-semibold text-gray-900">
                                    {{ $booking->opportunity->title ?? 'Opportunity #'.$booking->opportunity->id }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-500">Stage</div>
                                <div class="font-semibold text-gray-900">
                                    {{ ucfirst(str_replace('_', ' ', $booking->opportunity->stage ?? '—')) }}
                                </div>
                            </div>

                            @if(Route::has('admin.opportunities.show'))
                                <a href="{{ route('admin.opportunities.show', $booking->opportunity_id) }}"
                                   class="inline-flex text-sm text-blue-600 hover:underline">
                                    View Opportunity
                                </a>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500">
                            No opportunity linked.
                        </p>
                    @endif
                </div>

                {{-- Record Info --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Record Info
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Created At</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->created_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Last Updated</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->updated_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Company ID</div>
                            <div class="font-semibold text-gray-900">
                                {{ $booking->company_id ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection