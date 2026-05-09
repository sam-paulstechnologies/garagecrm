<?php

use App\Events\LeadCreated;
use App\Events\OpportunityStatusUpdated;
use App\Events\BookingStatusUpdated;
use App\Events\JobCompleted;
use App\Support\NotifyFormatters as F;

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy Notification Config
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | WhatsApp has been intentionally removed from this config.
    |
    | WhatsApp journeys must now go through:
    |
    |   App\Services\WhatsApp\SendWhatsAppMessage::fireEvent()
    |
    | using DB-driven mappings instead of hardcoded template names.
    |
    | This prevents duplicate WhatsApp sends from the old notification path:
    |
    |   DispatchNotificationJob / config/notify.php
    |
    | Email is kept here because email notifications can still use the
    | legacy notification formatter flow safely.
    |
    */

    /* ---------- Lead Created ---------- */
    LeadCreated::class => [
        'channels'      => ['email'],
        'template'      => 'lead_created',
        'subject'       => [F::class, 'leadCreatedSubject'],
        'body'          => [F::class, 'leadCreatedBody'],
        'cta'           => [F::class, 'leadCreatedCta'],
        'placeholders'  => [F::class, 'leadCreatedPlaceholders'],
        'to'            => [F::class, 'leadCreatedTo'],
    ],

    /* ---------- Opportunity status updates ---------- */
    OpportunityStatusUpdated::class => [
        'channels'      => ['email'],
        'template'      => [F::class, 'oppTemplate'],
        'subject'       => [F::class, 'oppSubject'],
        'body'          => [F::class, 'oppBody'],
        'cta'           => [F::class, 'oppCta'],
        'placeholders'  => [F::class, 'oppPlaceholders'],
        'to'            => [F::class, 'oppTo'],
    ],

    /* ---------- Booking status updates ---------- */
    BookingStatusUpdated::class => [
        'channels'      => ['email'],
        'template'      => [F::class, 'bookingTemplate'],
        'subject'       => [F::class, 'bookingSubject'],
        'body'          => [F::class, 'bookingBody'],
        'cta'           => [F::class, 'bookingCta'],
        'placeholders'  => [F::class, 'bookingPlaceholders'],
        'to'            => [F::class, 'bookingTo'],
    ],

    /* ---------- Job completed ---------- */
    JobCompleted::class => [
        'channels'      => ['email'],
        'template'      => 'job_completed',
        'subject'       => [F::class, 'jobCompletedSubject'],
        'body'          => [F::class, 'jobCompletedBody'],
        'cta'           => [F::class, 'jobCompletedCta'],
        'placeholders'  => [F::class, 'jobCompletedPlaceholders'],
        'to'            => [F::class, 'jobCompletedTo'],
    ],

];