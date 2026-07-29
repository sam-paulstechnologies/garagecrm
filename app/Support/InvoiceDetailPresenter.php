<?php

namespace App\Support;

use App\Models\Job\Invoice;
use App\Services\PhoneNumberService;
use Illuminate\Support\Facades\Route;

final class InvoiceDetailPresenter
{
    public function __construct(
        private readonly PhoneNumberService $phoneNumbers,
        private readonly InvoiceStatusPresenter $statuses,
    ) {
    }

    /**
     * Build the explicit data contract shared by the admin invoice-detail partials.
     *
     * @return array<string, mixed>
     */
    public function present(Invoice $invoice): array
    {
        $invoiceNumber = $invoice->invoice_number
            ?? $invoice->number
            ?? 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

        $status = $this->statuses->present($invoice->status);
        $statusValue = $status['value'];
        $amount = (float) ($invoice->amount ?? 0);
        $currency = $invoice->currency ?? 'AED';
        $hasRevenue = $amount > 0;
        $hasJob = ! empty($invoice->job_id);
        $roiReady = $statusValue === 'paid' && $hasRevenue && $hasJob;

        $sourceLabel = $invoice->source
            ? ucwords(str_replace('_', ' ', $invoice->source))
            : 'Generated';

        $contactPhone = $invoice->client?->phone
            ?? $invoice->client?->phone_norm
            ?? $invoice->client?->whatsapp
            ?? null;
        $contactPhoneDisplay = $contactPhone
            ? $this->phoneNumbers->formatForDisplay($contactPhone)
            : null;
        $contactTelUrl = $contactPhone
            ? $this->phoneNumbers->buildTelUrl($contactPhone)
            : null;
        $whatsappLookup = $contactPhone
            ? $this->phoneNumbers->buildWhatsappLookupKey($contactPhone)
            : null;
        $invoiceWhatsappInboxUrl = Route::has('admin.inbox.index')
            ? route('admin.inbox.index', $whatsappLookup ? ['search' => $whatsappLookup] : [])
            : '#';
        $contactEmail = trim((string) ($invoice->client?->email ?? ''));

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
        $hasDownload = filled($invoice->file_path) && Route::has('admin.invoices.download');

        $statusLabels = [];
        $statusHelp = [];

        foreach (InvoiceStatusPresenter::SUPPORTED_STATUSES as $supportedStatus) {
            $supported = $this->statuses->present($supportedStatus);
            $statusLabels[$supportedStatus] = $supported['label'];
            $statusHelp[$supportedStatus] = $supported['help'];
        }

        $activityItems = collect([
            [
                'title' => 'Invoice created',
                'meta' => $invoice->created_at?->format('d M Y, h:i A') ?? '-',
                'detail' => 'Invoice record was created.',
            ],
        ]);

        if ($invoice->updated_at && (! $invoice->created_at || $invoice->updated_at->ne($invoice->created_at))) {
            $activityItems->push([
                'title' => 'Invoice updated',
                'meta' => $invoice->updated_at->format('d M Y, h:i A'),
                'detail' => 'Current status: ' . $status['label'],
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

        return [
            'invoice' => $invoice,
            'invoiceNumber' => $invoiceNumber,
            'statusValue' => $statusValue,
            'statusLabel' => $status['label'],
            'statusBadge' => $status['admin_badge_class'],
            'amount' => $amount,
            'currency' => $currency,
            'hasRevenue' => $hasRevenue,
            'hasJob' => $hasJob,
            'roiReady' => $roiReady,
            'sourceLabel' => $sourceLabel,
            'contactPhone' => $contactPhone,
            'contactPhoneDisplay' => $contactPhoneDisplay,
            'contactTelUrl' => $contactTelUrl,
            'invoiceWhatsappInboxUrl' => $invoiceWhatsappInboxUrl,
            'whatsappFloatingUrl' => $invoiceWhatsappInboxUrl,
            'contactEmail' => $contactEmail,
            'contactMailtoUrl' => $contactEmail !== '' ? 'mailto:' . $contactEmail : null,
            'job' => $job,
            'booking' => $booking,
            'vehicle' => $vehicle,
            'vehicleLabel' => $vehicleLabel,
            'paidAmount' => $paidAmount,
            'outstandingAmount' => max(0, $amount - $paidAmount),
            'hasDownload' => $hasDownload,
            'statusLabels' => $statusLabels,
            'statusHelp' => $statusHelp,
            'statusFormFields' => [
                'client_id' => $invoice->client_id,
                'job_id' => $invoice->job_id,
                'number' => $invoice->number ?? $invoiceNumber,
                'amount' => $invoice->amount,
                'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'currency' => $invoice->currency ?? 'AED',
            ],
            'activityItems' => $activityItems,
        ];
    }
}
