@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    @include('admin.bookings.index-partials._styles')

    <div class="sf-page sf-bookings-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.bookings.index-partials._hero')

        @include('admin.bookings.index-partials._alerts')

        @include('admin.bookings.index-partials._stats')

        @include('admin.bookings.index-partials._bucket_cards')

        @include('admin.bookings.index-partials._filters')

        @include('admin.bookings.index-partials._table')

        @include('admin.bookings.index-partials._pagination')
    </div>
@endsection
