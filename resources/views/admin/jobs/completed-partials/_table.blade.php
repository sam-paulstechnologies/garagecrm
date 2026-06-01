<div class="sf-table-wrap">
    <div class="sf-table-scroll">
        <table class="sf-table">
            <thead>
                <tr>
                    <th class="w-[18%]">Job</th>
                    <th class="w-[16%]">Client</th>
                    <th class="w-[14%]">Service</th>
                    <th class="w-[14%]">Invoice No.</th>
                    <th class="w-[14%]">Invoice Amount</th>
                    <th class="w-[14%]">ROI Status</th>
                    <th class="w-[10%] text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($jobs as $job)
                    @php
                        $invoice = $job->invoice ?? $job->invoices?->first();

                        $invoiceNumber = $invoice?->invoice_number
                            ?? $invoice?->number
                            ?? $job->invoice_no
                            ?? '-';

                        $invoiceAmount = $invoice?->amount ?? null;

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
                    @endphp

                    <tr>
                        <td>
                            <div class="font-extrabold text-white">
                                {{ $job->job_code ?? '-' }}
                            </div>

                            <div class="mt-1 max-w-[260px] text-xs font-medium text-slate-500">
                                <span class="block truncate" title="{{ $job->description }}">
                                    {{ $job->description ?: 'No description added' }}
                                </span>
                            </div>
                        </td>

                        <td>
                            <div class="font-bold text-slate-200">
                                {{ $job->client?->name ?? 'N/A' }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                {{ $job->client?->phone ?: $job->client?->phone_norm ?: 'No phone' }}
                            </div>
                        </td>

                        <td>
                            <span class="{{ $serviceBadge }}">
                                {{ $serviceBucket }}
                            </span>
                        </td>

                        <td>
                            <div class="font-extrabold text-white">
                                {{ $invoiceNumber }}
                            </div>
                        </td>

                        <td>
                            <div class="font-extrabold text-orange-300">
                                {{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : '-' }}
                            </div>
                        </td>

                        <td>
                            @if($invoiceAmount)
                                <span class="sf-badge-green">
                                    Ready for ROI
                                </span>
                            @else
                                <span class="sf-badge-red">
                                    Missing amount
                                </span>
                            @endif
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
                    @include('admin.jobs.completed-partials._empty_state')
                @endforelse
            </tbody>
        </table>
    </div>
</div>
