<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Jobs\SendWhatsAppMessageJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLeadWelcomeWhatsApp implements ShouldQueue
{
    public $queue = 'default';  // keep aligned with your worker

    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        // Resolve WhatsApp "to" number:
        // - prefer lead->phone if starts with '+' (E164)
        // - else use app fallback for testing
        $to = $lead->phone && str_starts_with($lead->phone, '+')
            ? 'whatsapp:' . $lead->phone
            : env('APP_WHATSAPP_DEFAULT_TO');

        if (!$to) {
            // No destination—silently skip or log
            \Log::warning('[WA] No recipient for lead welcome', ['lead_id' => $lead->id]);
            return;
        }

        // Compose message (you can later swap to template lookup)
        $msg = "Hi {$lead->name}, thanks for contacting our garage! "
             . "We’ve received your details and our manager will reach out shortly to book your visit. "
             . "— GarageCRM";

        // Dispatch queued WA job
        SendWhatsAppMessageJob::dispatch($to, $msg)
            ->onQueue('default');
    }
}
