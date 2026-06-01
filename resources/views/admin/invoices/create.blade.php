@extends('layouts.app')

@section('title', 'Create Invoice')

@push('styles')
    @include('admin.invoices.create-partials._styles')
@endpush

@section('content')
<div class="sf-page sf-invoices-page space-y-6">
    @include('admin.invoices.create-partials._hero')
    @include('admin.invoices.create-partials._errors')
    @include('admin.invoices.create-partials._note')
    @include('admin.invoices.create-partials._form')
</div>
@endsection

@push('scripts')
    @include('admin.invoices.create-partials._scripts')
@endpush
