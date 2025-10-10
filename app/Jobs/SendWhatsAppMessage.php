<?php

namespace App\Jobs;

use Twilio\Rest\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $toE164,
        public string $body,
        public ?int $templateId = null, // optional link to your WhatsAppTemplate
        public array $meta = []          // anything else you want to store
    ) {}

    public function handle(): void
    {
        $tw = new Client(config('services.twilio.sid'), config('services.twilio.token'));

        $msg = $tw->messages->create('whatsapp:'.$this->toE164, [
            'from'          => config('services.twilio.whatsapp_from'),
            'body'          => $this->body,
            'statusCallback'=> route('webhooks.twilio.status'),
        ]);

        // optional: persist immediately
        if (class_exists(\App\Models\WhatsApp\WhatsAppMessage::class)) {
            \App\Models\WhatsApp\WhatsAppMessage::create([
                'provider'      => 'twilio',
                'direction'     => 'outbound',
                'to_number'     => $this->toE164,
                'from_number'   => config('services.twilio.whatsapp_from'),
                'template'      => optional(\App\Models\WhatsApp\WhatsAppTemplate::find($this->templateId))->name,
                'payload'       => json_encode($this->meta + ['body' => $this->body]),
                'status'        => 'queued',
                'error_code'    => null,
                'error_message' => null,
            ]);
        }
    }
}
