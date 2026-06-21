@extends('layouts.app')

@section('title', 'Edit Invoice')

@push('styles')
    @include('admin.invoices.edit-partials._styles')
@endpush

@section('content')
@include('admin.invoices.edit-partials._context')

<div class="sf-page sf-invoices-page sf-invoices-edit mx-auto max-w-7xl px-4 py-6 space-y-6">
    @include('admin.invoices.edit-partials._hero')
    @include('admin.invoices.edit-partials._errors')
    @include('admin.invoices.edit-partials._form')
</div>
@endsection

@push('scripts')
    @include('admin.invoices.edit-partials._scripts')
@endpush
