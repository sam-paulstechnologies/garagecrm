{{-- resources/views/admin/jobs/index.blade.php --}}

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

    $bucketCounts = array_merge([
        'General Service' => 0,
        'Oil Service' => 0,
        'Battery Service' => 0,
        'Tyre Service' => 0,
        'AC Service' => 0,
        'Brake Service' => 0,
        'Car Wash / Detailing' => 0,
    ], $bucketCounts ?? []);

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

<div class="sf-page sf-jobs-page mx-auto max-w-7xl px-4 py-6 space-y-6">
    @include('admin.jobs.index-partials._hero')

    {{-- Search and filter first --}}
    @include('admin.jobs.index-partials._filters')

    {{-- Service buckets second --}}
    @include('admin.jobs.index-partials._service_buckets')

    {{-- KPI tiles third --}}
    @include('admin.jobs.index-partials._stats')

    {{-- Guidance note --}}
    @include('admin.jobs.index-partials._note')

    @include('admin.jobs.index-partials._table')

    @include('admin.jobs.index-partials._pagination')
</div>
@endsection