<div class="sf-table-wrap">
    <div class="sf-table-scroll">
        <table class="sf-table">
            <thead>
                <tr>
                    <th class="w-[16%]">Invoice</th>
                    <th class="w-[16%]">Client</th>
                    <th class="w-[20%]">Linked Job</th>
                    <th class="w-[13%]">Amount</th>
                    <th class="w-[11%]">Status</th>
                    <th class="w-[14%]">ROI Status</th>
                    <th class="w-[10%] text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($invoices as $invoice)
                    @php
                        $invoiceNumber = $invoice->invoice_number
                            ?? $invoice->number
                            ?? 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

                        $statusValue = $invoice->status ?? 'pending';

                        $hasRevenue = (float) ($invoice->amount ?? 0) > 0;
                        $hasJob = !empty($invoice->job_id);

                        $roiReady = $statusValue === 'paid' && $hasRevenue && $hasJob;
                    @endphp

                    <tr>
                        <td>
                            <div class="font-extrabold text-white">
                                {{ $invoiceNumber }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : 'No invoice date' }}
                            </div>
                        </td>

                        <td>
                            <div class="font-bold text-slate-200">
                                {{ $invoice->client?->name ?? 'N/A' }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                {{ $invoice->client?->phone ?? $invoice->client?->phone_norm ?? 'No phone' }}
                            </div>
                        </td>

                        <td>
                            @if($invoice->job)
                                <div class="font-bold text-slate-200">
                                    {{ $invoice->job->job_code ?? 'Job #' . $invoice->job->id }}
                                </div>

                                <div class="mt-1 max-w-[260px] text-xs font-medium text-slate-500">
                                    <span class="block truncate" title="{{ $invoice->job->description }}">
                                        {{ $invoice->job->description ?: 'No job description' }}
                                    </span>
                                </div>
                            @else
                                <span class="font-medium text-slate-500">
                                    Not linked
                                </span>
                            @endif
                        </td>

                        <td>
                            <div class="font-extrabold text-orange-300">
                                {{ $invoice->currency ?? 'AED' }}
                                {{ number_format((float) ($invoice->amount ?? 0), 2) }}
                            </div>
                        </td>

                        <td>
                            <span class="{{ $statusBadgeClass($statusValue) }}">
                                {{ ucwords($statusValue) }}
                            </span>
                        </td>

                        <td>
                            @if($roiReady)
                                <span class="sf-badge-orange">ROI Ready</span>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Job + paid revenue available
                                </div>
                            @elseif(!$hasJob)
                                <span class="sf-badge-slate">Link Job</span>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Needed for attribution
                                </div>
                            @elseif(!$hasRevenue)
                                <span class="sf-badge-red">Missing Amount</span>
                            @else
                                <span class="sf-badge-yellow">Not Paid</span>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Revenue not confirmed
                                </div>
                            @endif
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-3 whitespace-nowrap">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-link">
                                    View
                                </a>

                                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-link">
                                    Edit
                                </a>

                                @if($invoice->file_path && \Illuminate\Support\Facades\Route::has('admin.invoices.download'))
                                    <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-link">
                                        Download
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('admin.invoices.index-partials._empty_state')
                @endforelse
            </tbody>
        </table>
    </div>
</div>
