{{-- resources/views/admin/invoices/index-partials/_table.blade.php --}}

@php
    $phoneService = app(\App\Services\PhoneNumberService::class);
@endphp

<div class="sf-table-wrap sf-invoices-table-wrap">
    <div class="sf-table-scroll">
        <table class="sf-table sf-invoices-table">
            <thead>
                <tr>
                    <th class="w-[22%]">Invoice</th>
                    <th class="w-[18%]">Client / Job</th>
                    <th class="w-[13%]">Amount</th>
                    <th class="w-[11%]">Status</th>
                    <th class="w-[14%]">Due / Date</th>
                    <th class="w-[10%]">Source / File</th>
                    <th class="w-[12%] text-right">Actions</th>
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
                        $phone = $invoice->client?->phone
                            ?? $invoice->client?->phone_norm
                            ?? $invoice->client?->whatsapp
                            ?? $invoice->job?->client?->phone
                            ?? $invoice->job?->booking?->client?->phone
                            ?? $invoice->job?->booking?->lead?->phone
                            ?? null;
                        $phoneDisplay = $phone ? $phoneService->formatForDisplay($phone) : null;
                        $phoneTelUrl = $phone ? $phoneService->buildTelUrl($phone) : null;
                        $sourceLabel = $invoice->source ? ucwords(str_replace('_', ' ', $invoice->source)) : 'Generated';
                    @endphp

                    <tr>
                        <td data-label="Invoice">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-invoice-name-link">
                                {{ $invoiceNumber }}
                            </a>

                            <div class="sf-invoice-muted mt-1 text-xs font-semibold">
                                {{ $sourceLabel }}
                            </div>

                            @if($phoneDisplay && $phoneTelUrl)
                                <a href="{{ $phoneTelUrl }}" class="mt-1 inline-flex text-xs font-extrabold text-orange-300 underline decoration-orange-300/40 underline-offset-2">
                                    {{ $phoneDisplay }}
                                </a>
                            @else
                                <div class="mt-1 text-xs font-extrabold text-slate-400">
                                    No phone
                                </div>
                            @endif
                        </td>

                        <td data-label="Client / Job">
                            <div class="font-bold sf-invoice-value">
                                {{ $invoice->client?->name ?? 'N/A' }}
                            </div>

                            <div class="sf-invoice-muted mt-1 text-xs font-medium">
                                @if($invoice->job)
                                    {{ $invoice->job->job_code ?? 'Job #' . $invoice->job->id }}
                                @else
                                    Not linked
                                @endif
                            </div>
                        </td>

                        <td data-label="Amount">
                            <div class="font-extrabold text-orange-300">
                                {{ $invoice->currency ?? 'AED' }}
                                {{ number_format((float) ($invoice->amount ?? 0), 2) }}
                            </div>
                        </td>

                        <td data-label="Status">
                            <span class="{{ $statusBadgeClass($statusValue) }}">
                                {{ ucwords($statusValue) }}
                            </span>
                        </td>

                        <td data-label="Due / Date">
                            <div class="font-bold sf-invoice-value">
                                {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'No due date' }}
                            </div>

                            <div class="sf-invoice-muted mt-1 text-xs font-medium">
                                Issued {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') : '-' }}
                            </div>
                        </td>

                        <td data-label="Source / File">
                            <div class="font-bold sf-invoice-value">
                                {{ $sourceLabel }}
                            </div>

                            <div class="sf-invoice-muted mt-1 text-xs font-medium">
                                {{ $invoice->file_path ? 'Download available' : 'No file' }}
                            </div>
                        </td>

                        <td data-label="Actions" class="text-right">
                            <div class="sf-invoices-action-group">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-invoices-action-pill sf-invoices-action-view">
                                    View
                                </a>

                                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-invoices-action-pill sf-invoices-action-edit">
                                    Edit
                                </a>

                                @if($invoice->file_path && \Illuminate\Support\Facades\Route::has('admin.invoices.download'))
                                    <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-invoices-action-pill sf-invoices-action-download">
                                        Download
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <tr class="sf-invoice-roi-row">
                        <td colspan="7">
                            @if($roiReady)
                                <span class="sf-badge-orange">ROI Ready</span>

                                <div class="sf-invoice-muted mt-1 text-xs font-medium">
                                    Job + paid revenue available
                                </div>
                            @elseif(!$hasJob)
                                <span class="sf-badge-slate">Link Job</span>

                                <div class="sf-invoice-muted mt-1 text-xs font-medium">
                                    Needed for attribution
                                </div>
                            @elseif(!$hasRevenue)
                                <span class="sf-badge-red">Missing Amount</span>
                            @else
                                <span class="sf-badge-yellow">Not Paid</span>

                                <div class="sf-invoice-muted mt-1 text-xs font-medium">
                                    Revenue not confirmed
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    @include('admin.invoices.index-partials._empty_state')
                @endforelse
            </tbody>
        </table>
    </div>
</div>
