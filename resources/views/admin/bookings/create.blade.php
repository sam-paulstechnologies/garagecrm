@extends('layouts.app')

@section('title', 'Create Booking')

@section('content')
    @include('admin.bookings.create-partials._styles')

    <div class="sf-page sf-booking-form-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.bookings.create-partials._hero')
        @include('admin.bookings.create-partials._alerts')
        @include('admin.bookings.create-partials._form')
    </div>
@endsection
