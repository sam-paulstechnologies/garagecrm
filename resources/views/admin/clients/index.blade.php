{{-- resources/views/admin/clients/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Clients')

@section('content')
    <style>
        .sf-clients-page {
            max-width: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
        }
    </style>

    <div class="sf-page sf-clients-page w-full px-4 py-4 space-y-4 sm:px-6 lg:px-8">

        {{-- Import / status messages --}}
        @include('admin.clients.index-partials._alerts')

        {{-- Page header --}}
        @include('admin.clients.index-partials._hero')

        {{-- Search + client cards + pagination --}}
        @include('admin.clients.index-partials._content')

    </div>
@endsection
