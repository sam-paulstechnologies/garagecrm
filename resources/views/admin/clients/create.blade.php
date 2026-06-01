{{-- resources/views/admin/clients/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Create Client')

@section('content')
    <div class="sf-page mx-auto max-w-7xl px-4 py-6 space-y-6">

        {{-- Alerts --}}
        @include('admin.clients.create-partials._alerts')

        {{-- Hero --}}
        @include('admin.clients.create-partials._hero')

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Form --}}
            @include('admin.clients.create-partials._form')

            {{-- Side Notes --}}
            @include('admin.clients.create-partials._side_notes')

        </div>

    </div>
@endsection