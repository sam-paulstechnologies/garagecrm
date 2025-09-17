<?php

use App\Events\LeadCreated;
use App\Events\OpportunityStatusUpdated;
use App\Events\BookingStatusUpdated;
use App\Events\JobCompleted;

return [

    // Lead Created
    LeadCreated::class => [
        'channels' => ['whatsapp', 'email'],
        'template' => 'lead_created',
        'subject'  => fn($e) => "Lead Created – #{$e->lead->id}",
        'body'     => fn($e) => "Hi {$e->lead->name}, thanks for contacting us. Your lead no: {$e->lead->id}.",
        'cta'      => fn($e) => ['label' => 'View Lead', 'url' => route('admin.leads.show', $e->lead)],
        'placeholders' => fn($e) => [
            $e->lead->name ?? 'there',
            $e->lead->id,
        ],
        'to' => fn($e) => [
            'phone' => $e->lead->whatsapp ?? $e->lead->phone,
            'email' => $e->lead->email,
        ],
    ],

    // Opportunity status updates
    OpportunityStatusUpdated::class => [
        'channels' => ['whatsapp', 'email'],
        'template' => fn($e) => match ($e->status) {
            'confirmed'   => 'opportunity_confirmed',
            'cancelled'   => 'opportunity_cancelled',
            'rescheduled' => 'opportunity_rescheduled',
            default       => null,
        },
        'subject' => fn($e) => "Your booking is {$e->status}",
        'body'    => fn($e) =>
            'Hi ' . (optional($e->opportunity->client)->name ?? 'there') .
            ', your booking/opportunity ' .
            ($e->opportunity->reference ?? $e->opportunity->id) .
            ' is ' . $e->status . '.',
        'cta'     => fn($e) => ['label' => 'View Booking', 'url' => route('admin.opportunities.show', $e->opportunity)],
        'placeholders' => fn($e) => [
            optional($e->opportunity->client)->name ?? 'there',
            $e->opportunity->reference ?? $e->opportunity->id,
        ],
        'to' => fn($e) => [
            'phone' => optional($e->opportunity->client)->whatsapp ?? optional($e->opportunity->client)->phone,
            'email' => optional($e->opportunity->client)->email,
        ],
    ],

    // Booking status updates
    BookingStatusUpdated::class => [
        'channels' => ['whatsapp', 'email'],
        'template' => fn($e) => match ($e->status) {
            'cancelled'   => 'opportunity_cancelled',
            'rescheduled' => 'opportunity_rescheduled',
            default       => null, // ignore non-actionable statuses
        },
        'subject' => fn($e) => "Your booking is {$e->status}",
        'body'    => fn($e) =>
            'Hi ' . (optional($e->booking->client)->name ?? 'there') .
            ', your booking ' .
            ($e->booking->reference ?? $e->booking->id) .
            ' is ' . $e->status . '.',
        'cta'     => fn($e) => ['label' => 'View Booking', 'url' => route('admin.bookings.show', $e->booking)],
        'placeholders' => fn($e) => [
            optional($e->booking->client)->name ?? 'there',
            $e->booking->reference ?? $e->booking->id,
        ],
        'to' => fn($e) => [
            'phone' => optional($e->booking->client)->whatsapp ?? optional($e->booking->client)->phone,
            'email' => optional($e->booking->client)->email,
        ],
    ],

    // Job completed
    JobCompleted::class => [
        'channels' => ['whatsapp', 'email'],
        'template'  => 'job_completed',
        'subject'   => fn($e) => "Your job is complete",
        'body'      => fn($e) =>
            'Hi ' . (optional($e->job->client)->name ?? 'there') .
            ', your job ' .
            ($e->job->reference ?? $e->job->id) .
            ' is completed.',
        'cta'       => fn($e) => [
            'label' => 'View Invoice',
            'url'   => $e->invoiceUrl ?? route('admin.invoices.show', $e->job->invoice_id ?? null),
        ],
        'placeholders' => fn($e) => [
            optional($e->job->client)->name ?? 'there',
            $e->job->reference ?? $e->job->id,
            $e->invoiceUrl ?? route('admin.invoices.show', $e->job->invoice_id ?? null),
        ],
        'to' => fn($e) => [
            'phone' => optional($e->job->client)->whatsapp ?? optional($e->job->client)->phone,
            'email' => optional($e->job->client)->email,
        ],
    ],

];
