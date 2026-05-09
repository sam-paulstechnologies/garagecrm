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
        'class' => 'bg-red-100 text-red-700 border-red-200',
    ],
    'due_soon' => [
        'label' => 'Due Soon',
        'class' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
    ],
    'scheduled' => [
        'label' => 'Scheduled',
        'class' => 'bg-green-100 text-green-700 border-green-200',
    ],
    default => [
        'label' => 'No service history',
        'class' => 'bg-gray-100 text-gray-700 border-gray-200',
    ],
};
@endphp

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-lg font-semibold text-gray-800">
            Service History
        </h2>
        <p class="text-xs text-gray-500 mt-1">
            Completed jobs are used to calculate next service reminders.
        </p>
    </div>
</div>

{{-- Service Reminder Summary --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-gray-50 border rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Last Service</div>
        <div class="text-base font-semibold text-gray-900">
            {{ $lastService ? $lastService->format('d M Y') : '—' }}
        </div>
    </div>

    <div class="bg-gray-50 border rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Next Service</div>
        <div class="text-base font-semibold text-gray-900">
            {{ $nextService ? $nextService->format('d M Y') : '—' }}
        </div>
    </div>

    <div class="bg-gray-50 border rounded-lg p-4">
        <div class="text-xs text-gray-500 mb-1">Service Reminder Status</div>
        <span class="inline-flex px-2.5 py-1 rounded-full border text-xs font-semibold {{ $statusMeta['class'] }}">
            {{ $statusMeta['label'] }}
        </span>
    </div>
</div>

@if(!isset($serviceHistory) || $serviceHistory->isEmpty())
    <div class="text-sm text-gray-500">
        No completed services found for this client.
    </div>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-200 rounded-lg">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-2 text-left border-b">Job Code</th>
                    <th class="px-4 py-2 text-left border-b">Description</th>
                    <th class="px-4 py-2 text-left border-b">Start Time</th>
                    <th class="px-4 py-2 text-left border-b">End Time</th>
                    <th class="px-4 py-2 text-left border-b">Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach($serviceHistory as $job)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 border-b">
                            {{ $job->job_code ?? '—' }}
                        </td>

                        <td class="px-4 py-2 border-b">
                            {{ $job->description ?? '—' }}
                        </td>

                        <td class="px-4 py-2 border-b">
                            {{ optional($job->start_time)->format('d M Y H:i') ?? '—' }}
                        </td>

                        <td class="px-4 py-2 border-b">
                            {{ optional($job->end_time)->format('d M Y H:i') ?? '—' }}
                        </td>

                        <td class="px-4 py-2 border-b">
                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif