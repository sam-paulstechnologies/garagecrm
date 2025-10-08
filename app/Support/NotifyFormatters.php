<?php

namespace App\Support;

use App\Events\LeadCreated;
use App\Events\OpportunityStatusUpdated;
use App\Events\BookingStatusUpdated;
use App\Events\JobCompleted;

class NotifyFormatters
{
    /* ---------- LeadCreated ---------- */
    public static function leadCreatedSubject(LeadCreated $e): string
    {
        return "Lead Created – #{$e->lead->id}";
    }

    public static function leadCreatedBody(LeadCreated $e): string
    {
        $name = $e->lead->name ?? 'there';
        return "Hi {$name}, thanks for contacting us. Your lead no: {$e->lead->id}.";
    }

    public static function leadCreatedCta(LeadCreated $e): array
    {
        return [
            'label' => 'View Lead',
            'url'   => route('admin.leads.show', $e->lead), // safe at runtime
        ];
    }

    public static function leadCreatedPlaceholders(LeadCreated $e): array
    {
        return [
            $e->lead->name ?? 'there',
            $e->lead->id,
        ];
    }

    public static function leadCreatedTo(LeadCreated $e): array
    {
        return [
            'phone' => $e->lead->whatsapp ?? $e->lead->phone,
            'email' => $e->lead->email,
        ];
    }

    /* ---------- OpportunityStatusUpdated ---------- */
    public static function oppTemplate(OpportunityStatusUpdated $e): ?string
    {
        return match ($e->status) {
            'confirmed'   => 'opportunity_confirmed',
            'cancelled'   => 'opportunity_cancelled',
            'rescheduled' => 'opportunity_rescheduled',
            default       => null,
        };
    }

    public static function oppSubject(OpportunityStatusUpdated $e): string
    {
        return "Your booking is {$e->status}";
    }

    public static function oppBody(OpportunityStatusUpdated $e): string
    {
        $client = optional($e->opportunity->client);
        $name   = $client->name ?? 'there';
        $ref    = $e->opportunity->reference ?? $e->opportunity->id;
        return "Hi {$name}, your booking/opportunity {$ref} is {$e->status}.";
    }

    public static function oppCta(OpportunityStatusUpdated $e): array
    {
        return [
            'label' => 'View Booking',
            'url'   => route('admin.opportunities.show', $e->opportunity),
        ];
    }

    public static function oppPlaceholders(OpportunityStatusUpdated $e): array
    {
        return [
            optional($e->opportunity->client)->name ?? 'there',
            $e->opportunity->reference ?? $e->opportunity->id,
        ];
    }

    public static function oppTo(OpportunityStatusUpdated $e): array
    {
        $c = optional($e->opportunity->client);
        return [
            'phone' => $c->whatsapp ?? $c->phone,
            'email' => $c->email,
        ];
    }

    /* ---------- BookingStatusUpdated ---------- */
    public static function bookingTemplate(BookingStatusUpdated $e): ?string
    {
        return match ($e->status) {
            'cancelled'   => 'opportunity_cancelled',
            'rescheduled' => 'opportunity_rescheduled',
            default       => null,
        };
    }

    public static function bookingSubject(BookingStatusUpdated $e): string
    {
        return "Your booking is {$e->status}";
    }

    public static function bookingBody(BookingStatusUpdated $e): string
    {
        $client = optional($e->booking->client);
        $name   = $client->name ?? 'there';
        $ref    = $e->booking->reference ?? $e->booking->id;
        return "Hi {$name}, your booking {$ref} is {$e->status}.";
    }

    public static function bookingCta(BookingStatusUpdated $e): array
    {
        return [
            'label' => 'View Booking',
            'url'   => route('admin.bookings.show', $e->booking),
        ];
    }

    public static function bookingPlaceholders(BookingStatusUpdated $e): array
    {
        return [
            optional($e->booking->client)->name ?? 'there',
            $e->booking->reference ?? $e->booking->id,
        ];
    }

    public static function bookingTo(BookingStatusUpdated $e): array
    {
        $c = optional($e->booking->client);
        return [
            'phone' => $c->whatsapp ?? $c->phone,
            'email' => $c->email,
        ];
    }

    /* ---------- JobCompleted ---------- */
    public static function jobCompletedSubject(JobCompleted $e): string
    {
        return "Your job is complete";
    }

    public static function jobCompletedBody(JobCompleted $e): string
    {
        $client = optional($e->job->client);
        $name   = $client->name ?? 'there';
        $ref    = $e->job->reference ?? $e->job->id;
        return "Hi {$name}, your job {$ref} is completed.";
    }

    public static function jobCompletedCta(JobCompleted $e): array
    {
        $url = $e->invoiceUrl
            ?? ($e->job->invoice_id ? route('admin.invoices.show', $e->job->invoice_id) : route('admin.jobs.show', $e->job));

        return [
            'label' => 'View Invoice',
            'url'   => $url,
        ];
    }

    public static function jobCompletedPlaceholders(JobCompleted $e): array
    {
        $invoiceUrl = $e->invoiceUrl
            ?? ($e->job->invoice_id ? route('admin.invoices.show', $e->job->invoice_id) : route('admin.jobs.show', $e->job));

        return [
            optional($e->job->client)->name ?? 'there',
            $e->job->reference ?? $e->job->id,
            $invoiceUrl,
        ];
    }

    public static function jobCompletedTo(JobCompleted $e): array
    {
        $c = optional($e->job->client);
        return [
            'phone' => $c->whatsapp ?? $c->phone,
            'email' => $c->email,
        ];
    }
}
