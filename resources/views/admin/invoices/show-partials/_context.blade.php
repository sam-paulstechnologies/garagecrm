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

    $phoneService = app(\App\Services\PhoneNumberService::class);
    $contactPhone = $invoice->client?->phone ?? $invoice->client?->phone_norm ?? $invoice->client?->whatsapp ?? null;
    $contactPhoneDisplay = $contactPhone ? $phoneService->formatForDisplay($contactPhone) : null;
    $contactTelUrl = $contactPhone ? $phoneService->buildTelUrl($contactPhone) : null;
    $whatsappLookup = $contactPhone ? $phoneService->buildWhatsappLookupKey($contactPhone) : null;
    $invoiceWhatsappInboxUrl = \Illuminate\Support\Facades\Route::has('admin.inbox.index')
        ? route('admin.inbox.index', $whatsappLookup ? ['search' => $whatsappLookup] : [])
        : '#';
    $whatsappFloatingUrl = $invoiceWhatsappInboxUrl;
    $contactEmail = trim((string) ($invoice->client?->email ?? ''));
    $contactMailtoUrl = $contactEmail !== '' ? 'mailto:' . $contactEmail : null;

    $job = $invoice->job;
    $booking = $invoice->booking ?? $job?->booking;
    $vehicle = $booking?->vehicleData ?? $booking?->vehicle ?? null;
    $vehicleLabel = $booking?->vehicle_label
        ?? $vehicle?->vehicle_label
        ?? trim(implode(' ', array_filter([
            $vehicle?->year,
            $vehicle?->make?->name ?? $vehicle?->vehicleMake?->name ?? null,
            $vehicle?->model?->name ?? $vehicle?->vehicleModel?->name ?? null,
            $vehicle?->plate_number ? '(' . $vehicle->plate_number . ')' : null,
        ])));
    $vehicleLabel = $vehicleLabel !== '' ? $vehicleLabel : null;

    $paidAmount = $statusValue === 'paid' ? $amount : 0;
    $outstandingAmount = max(0, $amount - $paidAmount);
    $hasDownload = filled($invoice->file_path) && \Illuminate\Support\Facades\Route::has('admin.invoices.download');

    $statusLabels = [
        'pending' => 'Pending',
        'overdue' => 'Overdue',
        'paid' => 'Paid',
    ];
    $statusHelp = [
        'pending' => 'Invoice is awaiting payment.',
        'overdue' => 'Invoice payment is past due or needs attention.',
        'paid' => 'Invoice revenue is confirmed for reporting.',
    ];
    $statusFormFields = [
        'client_id' => $invoice->client_id,
        'job_id' => $invoice->job_id,
        'number' => $invoice->number ?? $invoiceNumber,
        'amount' => $invoice->amount,
        'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
        'due_date' => $invoice->due_date?->format('Y-m-d'),
        'currency' => $invoice->currency ?? 'AED',
    ];

    $activityItems = collect([
        [
            'title' => 'Invoice created',
            'meta' => $invoice->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => 'Invoice record was created.',
        ],
    ]);

    if ($invoice->updated_at && (!$invoice->created_at || $invoice->updated_at->ne($invoice->created_at))) {
        $activityItems->push([
            'title' => 'Invoice updated',
            'meta' => $invoice->updated_at->format('d M Y, h:i A'),
            'detail' => 'Current status: ' . ($statusLabels[$statusValue] ?? ucwords($statusValue)),
        ]);
    }

    if ($hasDownload) {
        $activityItems->push([
            'title' => 'Invoice file available',
            'meta' => $invoice->updated_at?->format('d M Y, h:i A') ?? '-',
            'detail' => basename((string) $invoice->file_path),
        ]);
    }

    if ($job) {
        $activityItems->push([
            'title' => 'Job linked',
            'meta' => $job->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => $job->job_code ?? 'Job #' . $job->id,
        ]);
    }
@endphp
