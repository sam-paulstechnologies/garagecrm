@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Booking Management
            </div>

            <h1 class="sf-page-title mt-3">
                Edit Booking #{{ $booking->id }}
            </h1>

            <p class="sf-page-subtitle">
                Update booking details, client, opportunity, vehicle, date, slot, priority, and assigned team member.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.bookings.show'))
                <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-btn-secondary">
                    View Booking
                </a>
            @endif

            <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
                ← Back to Bookings
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @include('admin.bookings.partials.errors')

    {{-- Form Card --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Booking Information
            </h2>

            <p class="sf-section-subtitle">
                Keep the booking accurate so job creation and customer follow-ups work cleanly.
            </p>
        </div>

        <div class="sf-card-body">
            @include('admin.bookings.partials.form', [
                'action'        => route('admin.bookings.update', $booking),
                'isEdit'        => true,
                'booking'       => $booking,
                'clients'       => $clients,
                'opportunities' => $opportunities,
                'vehicles'      => $vehicles,
                'users'         => $users,
                'vehicleMakes'  => $vehicleMakes,
                'vehicleModels' => $vehicleModels,
            ])
        </div>
    </div>

</div>
@endsection