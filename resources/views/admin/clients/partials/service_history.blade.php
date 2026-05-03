<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-gray-800">
        Service History
    </h2>
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