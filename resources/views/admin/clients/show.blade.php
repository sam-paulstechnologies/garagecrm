@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Tabs Navigation --}}
    @include('admin.clients.partials.tabs')

    {{-- Tab Content --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.details')
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.leads')
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.opportunities')
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.files')
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.notes')
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.activity')
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('admin.clients.index') }}" class="text-blue-600 underline hover:text-blue-800">← Back to Clients</a>
    </div>
</div>
@endsection
