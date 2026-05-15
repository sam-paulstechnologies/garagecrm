{{-- resources/views/admin/clients/partials/service_history.blade.php --}}

@php
$lastService = !empty($kpis['last_service'] ?? null)
    ? \Illuminate\Support\Carbon::parse($kpis['last_service'])
    : null;

$nextService = !empty($kpis['next_service'] ?? null)
    ? \Illuminate\Support\Carbon::parse($kpis['next_service'])
    : null;

$nextServiceStatus = $kpis['next_service_status'] ?? 'not_available';

$statusMeta = match ($nextServiceStatus) {
    'overdue' => [
        'label' => 'Overdue',
        'badge' => 'sf-badge-red',
        'card' => 'border-red-400/20 bg-red-500/10',
        'text' => 'text-red-300',
        'icon' => '🚨',
    ],
    'due_soon' => [
        'label' => 'Due Soon',
        'badge' => 'sf-badge-yellow',
        'card' => 'border-yellow-400/20 bg-yellow-500/10',
        'text' => 'text-yellow-300',
        'icon' => '⏳',
    ],
    'scheduled' => [
        'label' => 'Scheduled',
        'badge' => 'sf-badge-green',
        'card' => 'border-green-400/20 bg-green-500/10',
        'text' => 'text-green-300',
        'icon' => '✅',
    ],
    default => [
        'label' => 'No service history',
        'badge' => 'sf-badge-slate',
        'card' => 'border-white/10 bg-slate-950/60',
        'text' => 'text-slate-300',
        'icon' => '🛠️',
    ],
};

$statusBadge = function ($status) {
    $status = strtolower((string) $status);

    return match ($status) {
        'completed', 'done', 'closed' => 'sf-badge-green',
        'in_progress', 'active' => 'sf-badge-blue',
        'pending' => 'sf-badge-orange',
        'cancelled', 'canceled', 'failed' => 'sf-badge-red',
        default => 'sf-badge-slate',
    };
};
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Service History
            </h2>

            <p class="sf-section-subtitle">
                Completed jobs are used to calculate next service reminders.
            </p>
        </div>

        <span class="{{ $statusMeta['badge'] }}">
            {{ $statusMeta['label'] }}
        </span>
    </div>

    {{-- Service Reminder Summary --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

        {{-- Last Service --}}
        <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">
                        Last Service
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $lastService ? $lastService->format('d M Y') : '—' }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-blue-100/70">
                        Most recent completed job
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-xl text-blue-300 ring-1 ring-blue-400/20">
                    🛠️
                </div>
            </div>
        </div>

        {{-- Next Service --}}
        <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        Next Service
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $nextService ? $nextService->format('d M Y') : '—' }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-orange-100/70">
                        Estimated reminder date
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-xl text-orange-300 ring-1 ring-orange-400/20">
                    📅
                </div>
            </div>
        </div>

        {{-- Reminder Status --}}
        <div class="rounded-2xl border {{ $statusMeta['card'] }} p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide {{ $statusMeta['text'] }}">
                        Service Reminder Status
                    </div>

                    <div class="mt-2">
                        <span class="{{ $statusMeta['badge'] }}">
                            {{ $statusMeta['label'] }}
                        </span>
                    </div>

                    <div class="mt-2 text-xs font-medium text-slate-400">
                        Based on completed job history
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/5 text-xl text-slate-200 ring-1 ring-white/10">
                    {{ $statusMeta['icon'] }}
                </div>
            </div>
        </div>

    </div>

    {{-- Service History Table --}}
    @if(!isset($serviceHistory) || $serviceHistory->isEmpty())
        <div class="sf-empty">
            No completed services found for this client.
        </div>
    @else
        <div class="sf-table-wrap">
            <div class="sf-table-scroll">
                <table class="sf-table">
                    <thead>
                        <tr>
                            <th class="w-[16%]">Job Code</th>
                            <th class="w-[34%]">Description</th>
                            <th class="w-[18%]">Start Time</th>
                            <th class="w-[18%]">End Time</th>
                            <th class="w-[14%]">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($serviceHistory as $job)
                            <tr>
                                {{-- Job Code --}}
                                <td>
                                    <div class="font-extrabold text-white">
                                        {{ $job->job_code ?? 'Job #' . $job->id }}
                                    </div>

                                    @if(\Illuminate\Support\Facades\Route::has('admin.jobs.show'))
                                        <a href="{{ route('admin.jobs.show', $job) }}" class="sf-link mt-1 inline-block">
                                            View
                                        </a>
                                    @endif
                                </td>

                                {{-- Description --}}
                                <td>
                                    <div class="text-sm font-medium leading-6 text-slate-300">
                                        {{ \Illuminate\Support\Str::limit($job->description ?? '—', 120) }}
                                    </div>
                                </td>

                                {{-- Start Time --}}
                                <td>
                                    <div class="font-bold text-slate-200">
                                        {{ optional($job->start_time)->format('d M Y') ?? '—' }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ optional($job->start_time)->format('h:i A') ?? '' }}
                                    </div>
                                </td>

                                {{-- End Time --}}
                                <td>
                                    <div class="font-bold text-slate-200">
                                        {{ optional($job->end_time)->format('d M Y') ?? '—' }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ optional($job->end_time)->format('h:i A') ?? '' }}
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="{{ $statusBadge($job->status ?? 'completed') }}">
                                        {{ ucfirst(str_replace('_', ' ', $job->status ?? 'completed')) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>