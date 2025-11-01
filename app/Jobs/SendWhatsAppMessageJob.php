<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $to;
    public string $body;

    public $tries   = 3;
    public $backoff = [5, 20, 60];

    public function __construct(string $to, string $body)
    {
        $this->to = $to;
        $this->body = $body;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $sid   = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $from  = env('TWILIO_WHATSAPP_FROM');

        $tw = new Client($sid, $token);

        $msg = $tw->messages->create($this->to, [
            'from' => $from,
            'body' => $this->body,
        ]);

        Log::info('[WA] Sent', ['sid' => $msg->sid, 'to' => $this->to]);
    }
}
