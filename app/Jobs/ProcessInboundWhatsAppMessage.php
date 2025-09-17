<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use App\Models\Client\Client;
use App\Models\Sales\Opportunity; // adjust namespace to your app
use App\Services\WhatsApp\WhatsAppService;

class ProcessInboundWhatsAppMessage implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $fromE164, public ?string $text) {}

    public function handle(WhatsAppService $wa): void
    {
        $msg = strtoupper(trim($this->text ?? ''));

        // 1) Find client by whatsapp/phone
        $client = Client::where('whatsapp', $this->fromE164)
            ->orWhere('phone', $this->fromE164)
            ->first();

        // 2) Pick most recent open opportunity for this client
        $opp = $client?->opportunities()
            ->latest()->whereNotIn('stage', ['Closed Won','Closed Lost'])->first();

        if (!$client || !$opp) {
            $wa->sendTemplate($this->fromE164, 'generic_support', ['We could not locate your booking. Reply HELP.']);
            return;
        }

        if ($msg === 'CONFIRM') {
            $opp->stage = 'Appointment';   // or 'Confirmed'
            $opp->save();

            $wa->sendTemplate($this->fromE164, 'opportunity_confirmed',
                [$client->name, $opp->id, url("/client/opportunities/{$opp->id}")],
                [], ['opportunity_id'=>$opp->id]
            );
            return;
        }

        if ($msg === 'CANCEL') {
            $opp->stage = 'Contact on Hold'; // or custom 'Cancelled'
            $opp->save();

            $wa->sendTemplate($this->fromE164, 'opportunity_cancelled',
                [$client->name, $opp->id], [], ['opportunity_id'=>$opp->id]
            );
            return;
        }

        if (Str::startsWith($msg, 'RESCHEDULE')) {
            // e.g., RESCHEDULE 2025-09-20 16:00
            $parts = explode(' ', $msg, 2);
            $when  = $parts[1] ?? null;
            if ($when) {
                // You likely have a booking_date/time column; adjust accordingly
                $opp->expected_meeting_at = $when;
                $opp->stage = 'Appointment';
                $opp->save();

                $wa->sendTemplate($this->fromE164, 'opportunity_rescheduled',
                    [$client->name, $when], [], ['opportunity_id'=>$opp->id]
                );
                return;
            }
        }

        // Fallback
        $wa->sendTemplate($this->fromE164, 'generic_support',
            ['Reply CONFIRM, CANCEL or RESCHEDULE yyyy-mm-dd HH:MM']);
    }
}
