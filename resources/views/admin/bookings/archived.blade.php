@extends('layouts.app')

@section('title', 'Archived Bookings')

@section('content')
    @include('admin.bookings.archive-partials._styles')

    <div class="sf-page sf-bookings-archive-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.bookings.archive-partials._hero')
        @include('admin.bookings.archive-partials._alerts')
        @include('admin.bookings.archive-partials._stats')
        @include('admin.bookings.archive-partials._table')
        @include('admin.bookings.archive-partials._pagination')
    </div>
@endsection
