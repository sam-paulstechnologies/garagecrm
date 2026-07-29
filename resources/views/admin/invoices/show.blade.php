@extends('layouts.app')

@section('title', 'Invoice Details')

@push('styles')
    @include('admin.invoices.show-partials._styles')
@endpush

@section('content')
<div class="sf-page sf-invoices-page mx-auto max-w-7xl px-4 py-6 space-y-6">
    <a href="{{ route('admin.invoices.index') }}" class="sf-back-link">
        Back to Invoices
    </a>

    @include('admin.invoices.show-partials._header', $invoiceContext)
    @include('admin.invoices.show-partials._alerts', $invoiceContext)
    @include('admin.invoices.show-partials._status_tracker', $invoiceContext)
    @include('admin.invoices.show-partials._summary_cards', $invoiceContext)
    @include('admin.invoices.show-partials._roi_note', $invoiceContext)

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('admin.invoices.show-partials._details', $invoiceContext)
            @include('admin.invoices.show-partials._linked_job', $invoiceContext)
            @include('admin.invoices.show-partials._legacy_file', $invoiceContext)
            @include('admin.invoices.show-partials._system_information', $invoiceContext)
            @include('admin.invoices.show-partials._activity_timeline', $invoiceContext)
        </div>

        <aside class="space-y-6">
            @include('admin.invoices.show-partials._client_panel', $invoiceContext)
            @include('admin.invoices.show-partials._roi_panel', $invoiceContext)
            @include('admin.invoices.show-partials._related_records', $invoiceContext)
        </aside>
    </div>
</div>
@endsection
