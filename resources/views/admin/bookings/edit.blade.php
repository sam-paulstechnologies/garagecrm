@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
    @include('admin.bookings.edit-partials._styles')

    <div class="sf-page sf-booking-form-page sf-bookings-edit mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.bookings.edit-partials._hero')
        @include('admin.bookings.edit-partials._alerts')
        @include('admin.bookings.edit-partials._form')
    </div>
@endsection
