{{-- resources/views/admin/clients/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Clients')

@section('content')
    <div class="sf-page mx-auto max-w-7xl px-4 py-6 space-y-6">

        {{-- Import / status messages --}}
        @include('admin.clients.index-partials._alerts')

        {{-- Page header --}}
        @include('admin.clients.index-partials._hero')

        {{-- Search + client cards + pagination --}}
        @include('admin.clients.index-partials._content')

    </div>
@endsection