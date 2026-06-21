@php
    $invoiceNumber = $invoice->invoice_number
        ?? $invoice->number
        ?? 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

    $statusValue = $invoice->status ?? 'pending';

    $statusBadge = match($statusValue) {
        'paid' => 'sf-badge-green',
        'overdue' => 'sf-badge-red',
        default => 'sf-badge-yellow',
    };

    $amount = (float) ($invoice->amount ?? 0);
    $currency = $invoice->currency ?? 'AED';

    $hasRevenue = $amount > 0;
    $hasJob = !empty($invoice->job_id);
    $roiReady = $statusValue === 'paid' && $hasRevenue && $hasJob;
    $sourceLabel = $invoice->source
        ? ucwords(str_replace('_', ' ', $invoice->source))
        : 'Generated';
@endphp
