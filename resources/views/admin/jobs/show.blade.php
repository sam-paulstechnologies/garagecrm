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

    $phoneService = app(\App\Services\PhoneNumberService::class);
    $contactPhone = $job->client?->phone
        ?? $job->client?->phone_norm
        ?? $job->client?->whatsapp
        ?? $job->booking?->client?->phone
        ?? $job->booking?->client?->phone_norm
        ?? $job->booking?->lead?->phone
        ?? $job->booking?->lead?->phone_norm
        ?? null;
    $contactPhoneDisplay = $contactPhone ? $phoneService->formatForDisplay($contactPhone) : null;
    $contactTelUrl = $contactPhone ? $phoneService->buildTelUrl($contactPhone) : null;
    $whatsappLookup = $contactPhone ? $phoneService->buildWhatsappLookupKey($contactPhone) : null;
    $jobWhatsappInboxUrl = \Illuminate\Support\Facades\Route::has('admin.inbox.index')
        ? route('admin.inbox.index', $whatsappLookup ? ['search' => $whatsappLookup] : [])
        : '#';
    $whatsappFloatingUrl = $jobWhatsappInboxUrl;
    $contactEmail = trim((string) ($job->client?->email ?? ''));
    $contactMailtoUrl = $contactEmail !== '' ? 'mailto:' . $contactEmail : null;

    $booking = $job->booking;
    $vehicle = $booking?->vehicleData ?? $booking?->vehicle ?? null;
    $vehicleLabel = $booking?->vehicle_label
        ?? $vehicle?->vehicle_label
        ?? trim(implode(' ', array_filter([
            $vehicle?->year,
            $vehicle?->make?->name ?? $vehicle?->vehicleMake?->name ?? null,
            $vehicle?->model?->name ?? $vehicle?->vehicleModel?->name ?? null,
            $vehicle?->plate_number ? '(' . $vehicle->plate_number . ')' : null,
        ])));
    $vehicleLabel = $vehicleLabel !== '' ? $vehicleLabel : null;

    $stageLabels = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ];
    $stageHelp = [
        'pending' => 'Job is waiting to start or be assigned.',
        'in_progress' => 'Work is actively happening or being tracked.',
        'completed' => 'Job is closed only after invoice number and amount are captured.',
    ];
    $stageFormFields = [
        'client_id' => $job->client_id,
        'booking_id' => $job->booking_id,
        'description' => $job->description,
        'start_time' => $job->start_time?->format('Y-m-d\TH:i'),
        'assigned_to' => $job->assigned_to,
        'work_summary' => $job->work_summary,
        'issues_found' => $job->issues_found,
        'parts_used' => $job->parts_used,
    ];

    $activityItems = collect([
        [
            'title' => 'Job created',
            'meta' => $job->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => 'Job record was created.',
        ],
    ]);

    if ($job->updated_at && (!$job->created_at || $job->updated_at->ne($job->created_at))) {
        $activityItems->push([
            'title' => 'Job updated',
            'meta' => $job->updated_at->format('d M Y, h:i A'),
            'detail' => 'Current stage: ' . ($stageLabels[$status] ?? ucwords(str_replace('_', ' ', $status))),
        ]);
    }

    if ($booking) {
        $activityItems->push([
            'title' => 'Booking linked',
            'meta' => $booking->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => $booking->name ?? 'Booking #' . $booking->id,
        ]);
    }

    if ($invoice) {
        $activityItems->push([
            'title' => 'Invoice linked',
            'meta' => $invoice->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => ($invoiceNumber ?: 'Invoice #' . $invoice->id) . ($invoiceAmount ? ' / AED ' . number_format((float) $invoiceAmount, 2) : ''),
        ]);
    }

    foreach (($job->jobCards ?? collect())->take(3) as $jobCard) {
        $activityItems->push([
            'title' => 'Job card uploaded',
            'meta' => $jobCard->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => $jobCard->description ?? $jobCard->file_path ?? 'Job card file uploaded.',
        ]);
    }
@endphp

<div class="sf-page sf-jobs-page mx-auto max-w-7xl px-4 py-6 space-y-6">
    <a href="{{ route('admin.jobs.index') }}" class="sf-back-link">
        Back to Jobs
    </a>

    @include('admin.jobs.show-partials._header')
    @include('admin.jobs.show-partials._stage_tracker')
    @include('admin.jobs.show-partials._summary_cards')
    @include('admin.jobs.show-partials._customer_update')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('admin.jobs.show-partials._details')
            @include('admin.jobs.show-partials._service_signal')
            @include('admin.jobs.show-partials._system_information')
            @include('admin.jobs.show-partials._activity_timeline')
        </div>

        <aside class="space-y-6">
            @include('admin.jobs.show-partials._client_panel')
            @include('admin.jobs.show-partials._closure_panel')
            @include('admin.jobs.show-partials._related_records')
        </aside>
    </div>
</div>
@endsection
