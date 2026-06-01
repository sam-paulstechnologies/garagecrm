@extends('layouts.app')

@section('title', 'Invoice Details')

@push('styles')
    @include('admin.invoices.show-partials._styles')
@endpush

@section('content')
@include('admin.invoices.show-partials._context')

<div class="sf-page sf-invoices-page space-y-6">
    @include('admin.invoices.show-partials._header')
    @include('admin.invoices.show-partials._alerts')
    @include('admin.invoices.show-partials._summary_cards')
    @include('admin.invoices.show-partials._roi_note')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('admin.invoices.show-partials._details')
            @include('admin.invoices.show-partials._linked_job')
            @include('admin.invoices.show-partials._legacy_file')
        </div>

        <aside class="space-y-6">
            @include('admin.invoices.show-partials._client_panel')
            @include('admin.invoices.show-partials._roi_panel')
        </aside>
    </div>
</div>
@endsection
