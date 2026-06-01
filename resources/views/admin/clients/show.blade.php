{{-- resources/views/admin/clients/show.blade.php --}}

@extends('layouts.app')

@section('title', 'SayaraForce | Client Profile')

@section('content')
    @include('admin.clients.show-partials._styles')

    <div class="sf-page sf-client-show mx-auto max-w-7xl px-4 py-6 space-y-6">

        @include('admin.clients.show-partials._back_link')

        @include('admin.clients.show-partials._profile_header')

        @include('admin.clients.show-partials._workspace_tabs_section')

        @include('admin.clients.show-partials._kpi_section')

        @include('admin.clients.show-partials._main_layout')

    </div>
@endsection