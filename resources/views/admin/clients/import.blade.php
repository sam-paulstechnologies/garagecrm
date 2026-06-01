{{-- resources/views/admin/clients/import.blade.php --}}

@extends('layouts.app')

@section('title', 'Import Clients')

@section('content')
    <div class="sf-page mx-auto max-w-7xl px-4 py-6 space-y-6">

        {{-- Alerts --}}
        @include('admin.clients.import-partials._alerts')

        {{-- Hero --}}
        @include('admin.clients.import-partials._hero')

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Upload Form --}}
            @include('admin.clients.import-partials._upload_form')

            {{-- Side Notes --}}
            @include('admin.clients.import-partials._side_notes')

        </div>

        {{-- Import Format --}}
        @include('admin.clients.import-partials._format_table')

    </div>
@endsection