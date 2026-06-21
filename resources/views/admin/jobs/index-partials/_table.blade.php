{{-- resources/views/admin/jobs/index-partials/_table.blade.php --}}

@php
    $phoneService = app(\App\Services\PhoneNumberService::class);
@endphp

<div class="sf-table-wrap sf-jobs-table-wrap">
    <div class="sf-table-scroll">
        <table class="sf-table sf-jobs-table">
            <thead>
                <tr>
                    <th class="w-[22%]">Job</th>
                    <th class="w-[16%]">Client</th>
                    <th class="w-[14%]">Service Bucket</th>
                    <th class="w-[12%]">Current Stage</th>
                    <th class="w-[16%]">Customer Update Now</th>
                    <th class="w-[14%]">Closure / ROI Status</th>
                    <th class="w-[16%] text-right">Actions</th>
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

                        $phone = $job->client?->phone
                            ?? $job->client?->phone_norm
                            ?? $job->client?->whatsapp
                            ?? $job->booking?->client?->phone
                            ?? $job->booking?->client?->phone_norm
                            ?? $job->booking?->lead?->phone
                            ?? $job->booking?->lead?->phone_norm
                            ?? null;
                        $phoneDisplay = $phone ? $phoneService->formatForDisplay($phone) : null;
                        $phoneTelUrl = $phone ? $phoneService->buildTelUrl($phone) : null;
                        $invoice = $job->invoice ?? $job->primaryInvoice ?? $job->invoices?->first();
                        $invoiceNumber = $invoice?->invoice_number ?? $invoice?->number ?? null;
                        $invoiceAmount = $invoice?->amount ?? null;
                        $vehicleLabel = $job->booking?->vehicle_label ?? $job->booking?->vehicleData?->vehicle_label ?? null;
                    @endphp

                    <tr>
                        <td data-label="Job">
                            <a href="{{ route('admin.jobs.show', $job) }}" class="sf-job-name-link">
                                {{ $job->job_code ?? '-' }}
                            </a>

                            @if($phoneDisplay && $phoneTelUrl)
                                <a href="{{ $phoneTelUrl }}" class="mt-1 inline-flex text-xs font-extrabold text-orange-300 underline decoration-orange-300/40 underline-offset-2">
                                    {{ $phoneDisplay }}
                                </a>
                            @else
                                <div class="mt-1 text-xs font-extrabold text-slate-400">
                                    No phone
                                </div>
                            @endif

                            <div class="sf-job-muted mt-1 max-w-[280px] text-xs font-medium">
                                <span class="block truncate" title="{{ $job->description }}">
                                    {{ $job->description ?: 'No description added' }}
                                </span>
                            </div>
                        </td>

                        <td data-label="Client">
                            <div class="font-bold sf-job-value">
                                {{ $job->client?->name ?? 'N/A' }}
                            </div>

                            <div class="sf-job-muted mt-1 text-xs font-medium">
                                {{ $vehicleLabel ?: 'No vehicle linked' }}
                            </div>
                        </td>

                        <td data-label="Service Bucket">
                            <span class="{{ $serviceBadge($serviceSignal) }}">
                                {{ $serviceSignal }}
                            </span>
                        </td>

                        <td data-label="Current Stage">
                            <span class="{{ $statusBadge($job->status) }}">
                                {{ ucwords(str_replace('_', ' ', $job->status)) }}
                            </span>
                        </td>

                        <td data-label="Customer Update Now">
                            <div class="sf-job-muted text-xs font-semibold leading-5">
                                {{ $customerUpdate }}
                            </div>
                        </td>

                        <td data-label="Closure / ROI Status">
                            @if($job->status === 'completed' || $invoiceNumber || $invoiceAmount)
                                <div class="font-extrabold text-green-300">
                                    Invoice captured
                                </div>

                                <div class="sf-job-muted mt-1 text-xs font-medium">
                                    {{ $invoiceNumber ?: 'No number' }}{{ $invoiceAmount ? ' / AED ' . number_format((float) $invoiceAmount, 2) : '' }}
                                </div>
                            @else
                                <div class="font-extrabold text-orange-300">
                                    Invoice required
                                </div>

                                <div class="sf-job-muted mt-1 text-xs font-medium">
                                    Needed before close.
                                </div>
                            @endif
                        </td>

                        <td data-label="Actions" class="text-right">
                            <div class="sf-jobs-action-group">
                                <a href="{{ route('admin.jobs.show', $job) }}" class="sf-jobs-action-pill sf-jobs-action-view">
                                    View
                                </a>

                                <a href="{{ route('admin.jobs.edit', $job) }}" class="sf-jobs-action-pill sf-jobs-action-edit">
                                    Edit
                                </a>

                                @if(Route::has('admin.jobs.archive') && empty($job->is_archived))
                                    <form method="POST" action="{{ route('admin.jobs.archive', $job) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="sf-jobs-action-pill sf-jobs-action-archive">
                                            Archive
                                        </button>
                                    </form>
                                @endif
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
