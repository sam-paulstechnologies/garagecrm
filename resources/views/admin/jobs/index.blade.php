@extends('layouts.app')

@section('title', 'Open Jobs')

@push('styles')
    @include('admin.jobs.index-partials._styles')
@endpush

@section('content')
@php
    $visibleJobs = collect($jobs->items());

    $detectServiceSignal = function ($job) {
        $jobText = strtolower(trim(
            ($job->description ?? '') . ' ' .
            ($job->work_summary ?? '') . ' ' .
            ($job->issues_found ?? '') . ' ' .
            ($job->parts_used ?? '')
        ));

        if (str_contains($jobText, 'oil')) {
            return 'Oil Service';
        }

        if (str_contains($jobText, 'battery')) {
            return 'Battery Service';
        }

        if (str_contains($jobText, 'tyre') || str_contains($jobText, 'tire')) {
            return 'Tyre Service';
        }

        if (str_contains($jobText, 'ac') || str_contains($jobText, 'a/c') || str_contains($jobText, 'air condition')) {
            return 'AC Service';
        }

        if (str_contains($jobText, 'brake')) {
            return 'Brake Service';
        }

        if (str_contains($jobText, 'wash') || str_contains($jobText, 'detailing')) {
            return 'Car Wash / Detailing';
        }

        return 'General Service';
    };

    $bucketCounts = [
        'General Service' => 0,
        'Oil Service' => 0,
        'Battery Service' => 0,
        'Tyre Service' => 0,
        'AC Service' => 0,
        'Brake Service' => 0,
        'Car Wash / Detailing' => 0,
    ];

    foreach ($visibleJobs as $visibleJob) {
        $signal = $detectServiceSignal($visibleJob);
        $bucketCounts[$signal] = ($bucketCounts[$signal] ?? 0) + 1;
    }

    $stats = $stats ?? [
        'open_jobs' => $jobs->total(),
        'pending' => $visibleJobs->where('status', 'pending')->count(),
        'in_progress' => $visibleJobs->where('status', 'in_progress')->count(),
    ];

    $statusBadge = function ($status) {
        return match($status) {
            'in_progress' => 'sf-badge-blue',
            'completed' => 'sf-badge-green',
            default => 'sf-badge-yellow',
        };
    };

    $serviceBadge = function ($serviceSignal) {
        return match($serviceSignal) {
            'Oil Service' => 'sf-badge-orange',
            'Battery Service' => 'sf-badge-blue',
            'Tyre Service' => 'sf-badge-slate',
            'AC Service' => 'sf-badge-blue',
            'Brake Service' => 'sf-badge-red',
            'Car Wash / Detailing' => 'sf-badge-green',
            default => 'sf-badge-slate',
        };
    };
@endphp

<div class="sf-page sf-jobs-page space-y-6">
    @include('admin.jobs.index-partials._hero')
    @include('admin.jobs.index-partials._stats')
    @include('admin.jobs.index-partials._service_buckets')
    @include('admin.jobs.index-partials._note')
    @include('admin.jobs.index-partials._filters')
    @include('admin.jobs.index-partials._table')
    @include('admin.jobs.index-partials._pagination')
</div>
@endsection
