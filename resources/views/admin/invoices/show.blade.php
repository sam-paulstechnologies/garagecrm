@extends('layouts.app')

@section('title', 'Invoice Details')

@push('styles')
    @include('admin.invoices.show-partials._styles')
@endpush

@section('content')
@include('admin.invoices.show-partials._context')

<div class="sf-page sf-invoices-page mx-auto max-w-7xl px-4 py-6 space-y-6">
    <a href="{{ route('admin.invoices.index') }}" class="sf-back-link">
        Back to Invoices
    </a>

    @include('admin.invoices.show-partials._header')
    @include('admin.invoices.show-partials._alerts')
    @include('admin.invoices.show-partials._status_tracker')
    @include('admin.invoices.show-partials._summary_cards')
    @include('admin.invoices.show-partials._roi_note')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('admin.invoices.show-partials._details')
            @include('admin.invoices.show-partials._linked_job')
            @include('admin.invoices.show-partials._legacy_file')
            @include('admin.invoices.show-partials._system_information')
            @include('admin.invoices.show-partials._activity_timeline')
        </div>

        <aside class="space-y-6">
            @include('admin.invoices.show-partials._client_panel')
            @include('admin.invoices.show-partials._roi_panel')
            @include('admin.invoices.show-partials._related_records')
        </aside>
    </div>
</div>
@endsection
