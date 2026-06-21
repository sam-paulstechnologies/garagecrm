<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Related Records
        </h2>
    </div>

    <div class="space-y-3 p-5">
        @if(!empty($booking->lead_id) && Route::has('admin.leads.show'))
            <a href="{{ route('admin.leads.show', $booking->lead_id) }}" class="sf-booking-related-card">
                <span>
                    <span class="sf-booking-related-type">Lead</span>
                    <span class="sf-booking-related-title">#{{ $booking->lead_id }}</span>
                    <span class="sf-booking-related-meta">
                        Created {{ $booking->lead?->created_at?->format('d M Y') ?? '-' }}
                        @if($booking->lead?->status)
                            &middot; {{ $booking->lead->status_label ?? ucfirst(str_replace('_', ' ', $booking->lead->status)) }}
                        @endif
                    </span>
                </span>
                <span class="sf-booking-related-action">View</span>
            </a>
        @endif

        @if(!empty($booking->opportunity_id) && Route::has('admin.opportunities.show'))
            <a href="{{ route('admin.opportunities.show', $booking->opportunity_id) }}" class="sf-booking-related-card">
                <span>
                    <span class="sf-booking-related-type">Opportunity</span>
                    <span class="sf-booking-related-title">#{{ $booking->opportunity_id }}</span>
                    <span class="sf-booking-related-meta">
                        {{ $booking->opportunity?->created_at?->format('d M Y') ?? '-' }}
                        @if($booking->opportunity?->stage)
                            &middot; {{ \App\Models\Client\Opportunity::stageLabel(\App\Models\Client\Opportunity::normalizeStage($booking->opportunity->stage)) }}
                        @endif
                    </span>
                </span>
                <span class="sf-booking-related-action">View</span>
            </a>
        @endif

        @if($job && Route::has('admin.jobs.show'))
            <a href="{{ route('admin.jobs.show', $job) }}" class="sf-booking-related-card">
                <span>
                    <span class="sf-booking-related-type">Job</span>
                    <span class="sf-booking-related-title">{{ $job->job_code ?? '#' . $job->id }}</span>
                    <span class="sf-booking-related-meta">
                        Created {{ $job->created_at?->format('d M Y') ?? '-' }}
                        @if($job->status)
                            &middot; {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                        @endif
                    </span>
                </span>
                <span class="sf-booking-related-action">View</span>
            </a>
        @endif

        @if($invoice && Route::has('admin.invoices.show'))
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-booking-related-card">
                <span>
                    <span class="sf-booking-related-type">Invoice</span>
                    <span class="sf-booking-related-title">{{ $invoice->number ?? '#' . $invoice->id }}</span>
                    <span class="sf-booking-related-meta">
                        {{ $invoice->invoice_date?->format('d M Y') ?? $invoice->created_at?->format('d M Y') ?? '-' }}
                        @if($invoice->status)
                            &middot; {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                        @endif
                    </span>
                </span>
                <span class="sf-booking-related-action">View</span>
            </a>
        @endif

        @if(empty($booking->lead_id) && empty($booking->opportunity_id) && ! $job && ! $invoice)
            <div class="sf-booking-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-booking-muted">
                No related lead, opportunity, job, or invoice linked.
            </div>
        @endif
    </div>
</div>
