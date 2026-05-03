@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Tabs --}}
    @include('admin.clients.partials.tabs')

    {{-- KPIs --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.kpis')
    </div>

    {{-- Vehicles --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.vehicles')
    </div>

    {{-- Client Details --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.details')
    </div>

    {{-- ✅ Service History (ADDED) --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.service_history')
    </div>

    {{-- Leads --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.leads')
    </div>

    {{-- Opportunities --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.opportunities')
    </div>

    {{-- Bookings --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.bookings')
    </div>

    {{-- Communications --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.communications')
    </div>

    {{-- Documents --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.documents')
    </div>

    {{-- Invoices --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.invoices')
    </div>

    {{-- Notes --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.notes')
    </div>

    {{-- Activity --}}
    <div class="bg-white p-6 rounded-lg shadow">
        @include('admin.clients.partials.activity')
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.clients.index') }}"
           class="text-blue-600 underline hover:text-blue-800">
            ← Back to Clients
        </a>
    </div>

</div>
@endsection