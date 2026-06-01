@extends('layouts.app')

@section('title', $job->job_code ?? 'Job Details')

@push('styles')
    @include('admin.jobs.show-partials._styles')
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

    $customerUpdateNow = match($status) {
        'pending' => 'Customer can be updated once inspection or work begins.',
        'in_progress' => 'Customer can be sent a progress update if visibility is needed.',
        'completed' => 'Feedback request can be triggered after job closure.',
        default => 'Customer can be updated when the job status changes.',
    };

    $invoice = $job->invoice ?? $job->primaryInvoice ?? $job->invoices?->first();

    $invoiceNumber = $invoice?->invoice_number
        ?? $invoice?->number
        ?? $job->invoice_no
        ?? null;

    $invoiceAmount = $invoice?->amount ?? null;

    $roiStatus = $status === 'completed'
        ? 'Ready for ROI reporting'
        : 'Invoice required before closing';
@endphp

<div class="sf-page sf-jobs-page space-y-6">
    @include('admin.jobs.show-partials._header')
    @include('admin.jobs.show-partials._summary_cards')
    @include('admin.jobs.show-partials._customer_update')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('admin.jobs.show-partials._details')
            @include('admin.jobs.show-partials._service_signal')
        </div>

        <aside class="space-y-6">
            @include('admin.jobs.show-partials._client_panel')
            @include('admin.jobs.show-partials._closure_panel')
        </aside>
    </div>
</div>
@endsection
