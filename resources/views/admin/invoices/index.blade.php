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

<div class="sf-page sf-invoices-page space-y-6">
    @include('admin.invoices.index-partials._hero')
    @include('admin.invoices.index-partials._stats')
    @include('admin.invoices.index-partials._note')
    @include('admin.invoices.index-partials._alerts')
    @include('admin.invoices.index-partials._filters')
    @include('admin.invoices.index-partials._table')
    @include('admin.invoices.index-partials._pagination')
</div>
@endsection
