{{-- resources/views/admin/clients/archived.blade.php --}}

@extends('layouts.app')

@section('title', 'Archived Clients')

@section('content')
    <div class="sf-page mx-auto max-w-7xl px-4 py-6 space-y-6">

        {{-- Alerts --}}
        @include('admin.clients.archive-partials._alerts')

        {{-- Hero --}}
        @include('admin.clients.archive-partials._hero')

        {{-- Summary Stats --}}
        @include('admin.clients.archive-partials._stats')

        {{-- Search --}}
        @include('admin.clients.archive-partials._filters')

        {{-- Alphabet Jump --}}
        @include('admin.clients.archive-partials._alphabet_nav')

        {{-- Archived Client Cards --}}
        @include('admin.clients.archive-partials._client_grid')

    </div>
@endsection