@extends('super_admin.layout')

@section('title', 'Platform Health')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">System Health</p>
        <h1 class="mt-2 text-3xl font-black text-white">Platform Status</h1>
        <p class="mt-2 text-sm font-semibold sa-muted">Database-backed checks only. Secrets and raw environment values are not displayed.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Environment</p><p class="mt-3 text-xl font-black text-white">{{ $environment }}</p></div>
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Queue</p><p class="mt-3 text-xl font-black text-white">{{ $queueConnection }}</p></div>
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Cache</p><p class="mt-3 text-xl font-black text-white">{{ $cacheStore }}</p></div>
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Failed Jobs</p><p class="mt-3 text-xl font-black text-white">{{ $failedJobsCount }}</p></div>
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Storage Link</p><p class="mt-3 text-xl font-black text-white">{{ $checks['storage_link'] ? 'OK' : 'Missing' }}</p></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Core Checks</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach(['db' => 'Database', 'cache' => 'Cache', 'storage_link' => 'Storage Link'] as $key => $label)
                    <div class="rounded-2xl sa-soft p-4">
                        <p class="text-xs font-black uppercase tracking-wide sa-label">{{ $label }}</p>
                        <div class="mt-2">@include('super_admin.partials._badge', ['tone' => $checks[$key] ? 'green' : 'red', 'label' => $checks[$key] ? 'Healthy' : 'Needs attention'])</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Last Platform Records</h2>
            <div class="mt-4 space-y-3">
                @foreach($lastRecords as $label => $record)
                    <div class="flex items-center justify-between gap-3 rounded-2xl sa-soft p-4">
                        <span class="text-sm font-black text-white">{{ str($label)->headline() }}</span>
                        <span class="text-sm font-bold sa-muted">{{ $record?->created_at ? \Carbon\Carbon::parse($record->created_at)->format('d M Y, h:i A') : 'No record' }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="mt-6 overflow-hidden rounded-3xl sa-card">
        <div class="p-5">
            <h2 class="text-lg font-black text-white">Recent Failed Jobs</h2>
            <p class="mt-1 text-sm font-semibold sa-muted">Exceptions are truncated to avoid noisy or sensitive payloads.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full sa-table">
                <thead><tr><th class="px-5 py-4 text-left">Failed At</th><th class="px-5 py-4 text-left">Queue</th><th class="px-5 py-4 text-left">Exception</th></tr></thead>
                <tbody>
                    @forelse($failedJobs as $job)
                        <tr>
                            <td class="px-5 py-4 text-sm font-bold">{{ \Carbon\Carbon::parse($job->failed_at)->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $job->queue }}</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ str($job->exception)->limit(160) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-sm font-bold sa-muted">No failed jobs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
