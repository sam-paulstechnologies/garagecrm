@extends('layouts.app')

@section('title', 'Create Booking')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Booking Management
            </div>

            <h1 class="sf-page-title mt-3">
                Create Booking
            </h1>

            <p class="sf-page-subtitle">
                Create a confirmed customer appointment and link it to client, opportunity, vehicle, and team member.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
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
                Add booking details. This should represent a confirmed appointment, not only a tentative enquiry.
            </p>
        </div>

        <div class="sf-card-body">
            @include('admin.bookings.partials.form', [
                'action'        => route('admin.bookings.store'),
                'isEdit'        => false,
                'booking'       => null,
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