{{-- resources/views/admin/jobs/index-partials/_table.blade.php --}}

<div class="sf-table-wrap">
    <div class="sf-table-scroll">
        <table class="sf-table">
            <thead>
                <tr>
                    <th class="w-[18%]">Job</th>
                    <th class="w-[14%]">Client</th>
                    <th class="w-[14%]">Service Bucket</th>
                    <th class="w-[12%]">Current Stage</th>
                    <th class="w-[20%]">Customer Update Now</th>
                    <th class="w-[16%]">Closure / ROI Status</th>
                    <th class="w-[6%] text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($jobs as $job)
                    @php
                        $serviceSignal = $detectServiceSignal($job);

                        $customerUpdate = match($job->status) {
                            'pending' => 'Send start or inspection update once work begins.',
                            'in_progress' => 'Send progress update if customer needs visibility.',
                            default => 'Update customer when job changes.',
                        };
                    @endphp

                    <tr>
                        <td>
                            <div class="font-extrabold sf-job-value">
                                {{ $job->job_code ?? '-' }}
                            </div>

                            <div class="sf-job-muted mt-1 max-w-[260px] text-xs font-medium">
                                <span class="block truncate" title="{{ $job->description }}">
                                    {{ $job->description ?: 'No description added' }}
                                </span>
                            </div>
                        </td>

                        <td>
                            <div class="font-bold sf-job-value">
                                {{ $job->client?->name ?? 'N/A' }}
                            </div>
                        </td>

                        <td>
                            <span class="{{ $serviceBadge($serviceSignal) }}">
                                {{ $serviceSignal }}
                            </span>
                        </td>

                        <td>
                            <span class="{{ $statusBadge($job->status) }}">
                                {{ ucwords(str_replace('_', ' ', $job->status)) }}
                            </span>
                        </td>

                        <td>
                            <div class="sf-job-muted font-medium leading-6">
                                {{ $customerUpdate }}
                            </div>
                        </td>

                        <td>
                            <div class="font-extrabold text-orange-300">
                                Invoice required to close
                            </div>

                            <div class="sf-job-muted mt-1 text-xs font-medium">
                                Capture invoice no. + amount for campaign ROI.
                            </div>
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-3 whitespace-nowrap">
                                <a href="{{ route('admin.jobs.show', $job->id) }}" class="sf-link">
                                    View
                                </a>

                                <a href="{{ route('admin.jobs.edit', $job->id) }}" class="sf-link">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('admin.jobs.index-partials._empty_state')
                @endforelse
            </tbody>
        </table>
    </div>
</div>