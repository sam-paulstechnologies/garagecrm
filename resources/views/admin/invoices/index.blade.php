{{-- resources/views/admin/invoices/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Invoices')

@push('styles')
    @include('admin.invoices.index-partials._styles')
@endpush

@section('content')
@php
    $stats = $stats ?? [
        'total' => $invoices->total(),
        'paid' => 0,
        'pending' => 0,
        'overdue' => 0,
        'roi_revenue' => 0,
    ];

    $currentStatus = $status ?? request('status', '');
    $currentSearch = $q ?? request('q', request('search', ''));

    $statusBadgeClass = function ($statusValue) {
        return match($statusValue) {
            'paid' => 'sf-badge-green',
            'overdue' => 'sf-badge-red',
            default => 'sf-badge-yellow',
        };
    };
@endphp

<div class="sf-page sf-invoices-page mx-auto max-w-7xl px-4 py-6 space-y-6">
    @include('admin.invoices.index-partials._hero')

    {{-- Search and filter first --}}
    @include('admin.invoices.index-partials._filters')

    {{-- KPI tiles --}}
    @include('admin.invoices.index-partials._stats')

    {{-- ROI note --}}
    @include('admin.invoices.index-partials._note')

    @include('admin.invoices.index-partials._alerts')

    @include('admin.invoices.index-partials._table')

    @include('admin.invoices.index-partials._pagination')
</div>
@endsection