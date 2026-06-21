{{-- resources/views/admin/bookings/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    @include('admin.bookings.index-partials._styles')

    <div class="sf-page sf-bookings-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        <div class="sf-index-sticky-panel space-y-6">
            @include('admin.bookings.index-partials._hero')

            {{-- Search and filter first --}}
            @include('admin.bookings.index-partials._filters')

            {{-- Booking buckets second --}}
            @include('admin.bookings.index-partials._bucket_cards')

            {{-- KPI tiles third --}}
            @include('admin.bookings.index-partials._stats')
        </div>

        @include('admin.bookings.index-partials._alerts')

        @include('admin.bookings.index-partials._table')

        @include('admin.bookings.index-partials._pagination')
    </div>
@endsection
