@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
@php
    $status = strtolower((string) ($booking->status ?? 'pending'));
    $priority = strtolower((string) ($booking->priority ?? 'medium'));

    $bookingDateRaw = $booking->booking_date
        ?? $booking->scheduled_at
        ?? $booking->date
        ?? $booking->preferred_date
        ?? null;

    $bookingDate = $bookingDateRaw
        ? \Illuminate\Support\Carbon::parse($bookingDateRaw)
        : null;

    $expectedCloseDate = !empty($booking->expected_close_date)
        ? \Illuminate\Support\Carbon::parse($booking->expected_close_date)
        : null;

    $vehicleLabel = '';

    if (!empty($booking->vehicle)) {
        $vehicleLabel = trim(
            ($booking->vehicle->year ? $booking->vehicle->year . ' ' : '') .
            ($booking->vehicle->make?->name ?? $booking->vehicle->vehicleMake?->name ?? '') . ' ' .
            ($booking->vehicle->model?->name ?? $booking->vehicle->vehicleModel?->name ?? '') . ' ' .
            ($booking->vehicle->plate_number ? '(' . $booking->vehicle->plate_number . ')' : '')
        );
    }

    if ($vehicleLabel === '') {
        $vehicleLabel = trim(
            ($booking->vehicleMake?->name ?? $booking->other_make ?? '') . ' ' .
            ($booking->vehicleModel?->name ?? $booking->other_model ?? '')
        );
    }

    $servicesRaw = $booking->service_type
        ?? $booking->services
        ?? $booking->notes_services
        ?? '';

    $services = is_array($servicesRaw)
        ? collect($servicesRaw)
        : collect(explode(',', (string) $servicesRaw));

    $services = $services
        ->map(fn ($service) => trim((string) $service))
        ->filter()
        ->values();

    $statusBadge = match ($status) {
        'pending' => 'sf-badge-yellow',
        'scheduled', 'confirmed', 'approved' => 'sf-badge-green',
        'converted_to_job', 'completed' => 'sf-badge-blue',
        'lost', 'cancelled', 'canceled', 'rejected' => 'sf-badge-red',
        default => 'sf-badge-slate',
    };

    $priorityBadge = match ($priority) {
        'urgent' => 'sf-badge-red',
        'high' => 'sf-badge-orange',
        'medium' => 'sf-badge-yellow',
        'low' => 'sf-badge-slate',
        default => 'sf-badge-slate',
    };

    $slotBadge = match (strtolower((string) ($booking->slot ?? ''))) {
        'morning' => 'sf-badge-blue',
        'afternoon' => 'sf-badge-orange',
        'evening' => 'sf-badge-slate',
        'full_day' => 'sf-badge-green',
        default => 'sf-badge-slate',
    };

    $nextAction = match ($status) {
        'pending' => 'Confirm booking',
        'scheduled', 'confirmed', 'approved' => 'Receive vehicle',
        'converted_to_job', 'completed' => 'Review job',
        'lost', 'cancelled', 'canceled', 'rejected' => 'No action',
        default => 'Review',
    };

    $stepClass = function ($active, $done = false) {
        if ($active) {
            return 'border-orange-400/40 bg-orange-500/10 text-orange-200 ring-1 ring-orange-400/20';
        }

        if ($done) {
            return 'border-green-400/20 bg-green-500/10 text-green-200';
        }

        return 'border-white/10 bg-slate-950/60 text-slate-500';
    };
@endphp

<div class="sf-page space-y-6">

    {{-- Back --}}
    <div>
        <a href="{{ route('admin.bookings.index') }}" class="sf-link">
            ← Back to Bookings
        </a>
    </div>

    {{-- Header --}}
    <div class="sf-hero-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="sf-kicker">
                        Booking Profile
                    </div>

                    <span class="{{ $statusBadge }}">
                        {{ ucfirst(str_replace('_', ' ', $booking->status ?? 'Pending')) }}
                    </span>

                    <span class="{{ $priorityBadge }}">
                        {{ ucfirst($booking->priority ?? 'Medium') }}
                    </span>
                </div>

                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                    {{ $booking->name ?? 'Booking #' . $booking->id }}
                </h1>

                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium text-slate-400">
                    <span>👤 {{ $booking->client?->name ?? 'No client' }}</span>
                    <span>🚗 {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}</span>
                    <span>📅 {{ $bookingDate ? $bookingDate->format('d M Y') : 'No date' }}</span>
                    <span>🕒 {{ ucfirst(str_replace('_', ' ', $booking->slot ?? 'No slot')) }}</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @if(Route::has('admin.bookings.edit'))
                    <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="sf-btn-primary">
                        Edit Booking
                    </a>
                @endif

                @if($booking->client_id && Route::has('admin.clients.show'))
                    <a href="{{ route('admin.clients.show', $booking->client_id) }}" class="sf-btn-secondary">
                        View Client
                    </a>
                @endif

                @if(!empty($booking->opportunity_id) && Route::has('admin.opportunities.show'))
                    <a href="{{ route('admin.opportunities.show', $booking->opportunity_id) }}" class="sf-btn-secondary">
                        View Opportunity
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Status Strip --}}
    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="sf-section-title">
                    Booking Status
                </h2>

                <p class="sf-section-subtitle">
                    Booking is the confirmed customer appointment before job creation.
                </p>
            </div>

            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 px-4 py-3 text-sm font-bold text-orange-200">
                Next Action:
                <span class="text-white">{{ $nextAction }}</span>
            </div>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-2xl border px-4 py-4 {{ $stepClass($status === 'pending') }}">
                    <div class="text-xs font-bold uppercase tracking-wide opacity-70">Step 1</div>
                    <div class="mt-1 text-sm font-extrabold">Pending</div>
                </div>

                <div class="rounded-2xl border px-4 py-4 {{ $stepClass(in_array($status, ['scheduled', 'confirmed', 'approved'], true), in_array($status, ['converted_to_job', 'completed'], true)) }}">
                    <div class="text-xs font-bold uppercase tracking-wide opacity-70">Step 2</div>
                    <div class="mt-1 text-sm font-extrabold">Scheduled / Confirmed</div>
                </div>

                <div class="rounded-2xl border px-4 py-4 {{ $stepClass(in_array($status, ['converted_to_job', 'completed'], true)) }}">
                    <div class="text-xs font-bold uppercase tracking-wide opacity-70">Step 3</div>
                    <div class="mt-1 text-sm font-extrabold">Job Created / Completed</div>
                </div>

                <div class="rounded-2xl border px-4 py-4 {{ $stepClass(in_array($status, ['lost', 'cancelled', 'canceled', 'rejected'], true)) }}">
                    <div class="text-xs font-bold uppercase tracking-wide opacity-70">Exception</div>
                    <div class="mt-1 text-sm font-extrabold">Lost / Cancelled</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Layout --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Left --}}
        <div class="space-y-6 xl:col-span-2">

            {{-- Booking Summary --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Booking Summary
                    </h2>
                </div>

                <div class="sf-card-body">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Booking ID</div>
                            <div class="mt-1 font-extrabold text-white">#{{ $booking->id }}</div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Booking Date</div>
                            <div class="mt-1 font-extrabold text-white">
                                {{ $bookingDate ? $bookingDate->format('d M Y') : '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Slot</div>
                            <div class="mt-2">
                                <span class="{{ $slotBadge }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->slot ?? '—')) }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Expected Duration</div>
                            <div class="mt-1 font-extrabold text-white">
                                {{ $booking->expected_duration ?? '—' }} {{ $booking->expected_duration ? 'day(s)' : '' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Expected Close Date</div>
                            <div class="mt-1 font-extrabold text-white">
                                {{ $expectedCloseDate ? $expectedCloseDate->format('d M Y') : '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Assigned To</div>
                            <div class="mt-1 font-extrabold text-white">
                                {{ $booking->assignedUser?->name ?? $booking->assignee?->name ?? 'Unassigned' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Service Type --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Service Type(s)
                    </h2>
                </div>

                <div class="sf-card-body">
                    @if($services->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($services as $service)
                                <span class="sf-badge-blue">
                                    {{ $service }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="sf-empty">
                            No service type added.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Pickup Details --}}
            @if($booking->pickup_required)
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Pickup Details
                        </h2>
                    </div>

                    <div class="sf-card-body">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Pickup Address</div>
                                <div class="mt-1 font-bold text-slate-200">
                                    {{ $booking->pickup_address ?? '—' }}
                                </div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Pickup Contact Number</div>
                                <div class="mt-1 font-bold text-slate-200">
                                    {{ $booking->pickup_contact_number ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Notes --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Notes
                    </h2>
                </div>

                <div class="sf-card-body">
                    @if(!empty($booking->notes))
                        <div class="whitespace-pre-line text-sm font-medium leading-7 text-slate-300">
                            {{ $booking->notes }}
                        </div>
                    @else
                        <div class="sf-empty">
                            No notes added.
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right --}}
        <div class="space-y-6">

            {{-- Client --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Client
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Name</div>
                        <div class="mt-1 font-extrabold text-white">
                            {{ $booking->client?->name ?? 'N/A' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Phone</div>
                        <div class="mt-1 font-bold text-slate-200">
                            {{ $booking->client?->phone ?? $booking->client?->whatsapp ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Email</div>
                        <div class="mt-1 break-words font-bold text-slate-200">
                            {{ $booking->client?->email ?? '—' }}
                        </div>
                    </div>

                    @if($booking->client_id && Route::has('admin.clients.show'))
                        <a href="{{ route('admin.clients.show', $booking->client_id) }}" class="sf-btn-secondary w-full">
                            Open Client Profile
                        </a>
                    @endif
                </div>
            </div>

            {{-- Vehicle --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Vehicle
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Vehicle</div>
                        <div class="mt-1 font-extrabold text-white">
                            {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle added' }}
                        </div>
                    </div>

                    @if(!empty($booking->vehicle?->plate_number))
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Plate Number</div>
                            <div class="mt-1 font-bold text-slate-200">
                                {{ $booking->vehicle->plate_number }}
                            </div>
                        </div>
                    @endif

                    @if(!empty($booking->vehicle?->vin))
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">VIN</div>
                            <div class="mt-1 break-all font-bold text-slate-200">
                                {{ $booking->vehicle->vin }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Related --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Related Records
                    </h2>
                </div>

                <div class="sf-card-body space-y-3">
                    @if(!empty($booking->opportunity_id) && Route::has('admin.opportunities.show'))
                        <a href="{{ route('admin.opportunities.show', $booking->opportunity_id) }}" class="sf-btn-secondary w-full">
                            View Opportunity
                        </a>
                    @endif

                    @if(!empty($booking->job_id) && Route::has('admin.jobs.show'))
                        <a href="{{ route('admin.jobs.show', $booking->job_id) }}" class="sf-btn-secondary w-full">
                            View Job
                        </a>
                    @endif

                    @if(empty($booking->opportunity_id) && empty($booking->job_id))
                        <div class="sf-empty">
                            No related opportunity or job linked.
                        </div>
                    @endif
                </div>
            </div>

            {{-- System --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        System Details
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Created At</div>
                        <div class="mt-1 font-bold text-slate-200">
                            {{ $booking->created_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Last Updated</div>
                        <div class="mt-1 font-bold text-slate-200">
                            {{ $booking->updated_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Company ID</div>
                        <div class="mt-1 font-bold text-slate-200">
                            {{ $booking->company_id ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection