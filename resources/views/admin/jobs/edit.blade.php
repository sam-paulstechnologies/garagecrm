@extends('layouts.app')

@section('title', 'Edit Job')

@push('styles')
    @include('admin.jobs.edit-partials._styles')
@endpush

@section('content')
@php
    $status = $job->status ?? 'pending';

    $statusBadge = match($status) {
        'completed' => 'sf-badge-green',
        'in_progress' => 'sf-badge-blue',
        default => 'sf-badge-yellow',
    };

    $jobText = strtolower(trim(
        ($job->description ?? '') . ' ' .
        ($job->work_summary ?? '') . ' ' .
        ($job->issues_found ?? '') . ' ' .
        ($job->parts_used ?? '')
    ));

    $serviceBucket = 'General Service';

    if (str_contains($jobText, 'oil')) {
        $serviceBucket = 'Oil Service';
    } elseif (str_contains($jobText, 'battery')) {
        $serviceBucket = 'Battery Service';
    } elseif (str_contains($jobText, 'tyre') || str_contains($jobText, 'tire')) {
        $serviceBucket = 'Tyre Service';
    } elseif (str_contains($jobText, 'ac') || str_contains($jobText, 'a/c') || str_contains($jobText, 'air condition')) {
        $serviceBucket = 'AC Service';
    } elseif (str_contains($jobText, 'brake')) {
        $serviceBucket = 'Brake Service';
    } elseif (str_contains($jobText, 'wash') || str_contains($jobText, 'detailing')) {
        $serviceBucket = 'Car Wash / Detailing';
    }

    $serviceBadge = match($serviceBucket) {
        'Oil Service' => 'sf-badge-orange',
        'Battery Service' => 'sf-badge-blue',
        'Tyre Service' => 'sf-badge-slate',
        'AC Service' => 'sf-badge-blue',
        'Brake Service' => 'sf-badge-red',
        'Car Wash / Detailing' => 'sf-badge-green',
        default => 'sf-badge-slate',
    };

    $invoice = $job->invoice ?? $job->primaryInvoice ?? $job->invoices?->first();

    $invoiceNumber = $invoice?->invoice_number ?? $invoice?->number ?? '';
    $invoiceAmount = $invoice?->amount ?? '';
@endphp

<div class="sf-page sf-jobs-page space-y-6">
    @include('admin.jobs.edit-partials._hero')
    @include('admin.jobs.edit-partials._notice')
    @include('admin.jobs.edit-partials._errors')
    @include('admin.jobs.edit-partials._form')
</div>

@include('admin.jobs.edit-partials._invoice_modal')
@endsection

@push('scripts')
    @include('admin.jobs.edit-partials._scripts')
@endpush
