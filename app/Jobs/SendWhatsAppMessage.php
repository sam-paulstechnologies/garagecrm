<?php

namespace App\Jobs;

use Twilio\Rest\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public string $toE164,
        public string $body,
        public ?int $templateId = null,
        public array $meta = []
    ) {}

    public function handle(): void
    {
        try {

            $twilioSid   = config('services.twilio.sid');
            $twilioToken = config('services.twilio.token');
            $from        = config('services.twilio.whatsapp_from');

            $tw = new Client($twilioSid, $twilioToken);

            Log::info('[WA][Twilio] Sending message', [
                'to' => $this->toE164,
                'templateId' => $this->templateId
            ]);

            $msg = $tw->messages->create(
                'whatsapp:' . $this->toE164,
                [
                    'from' => $from,
                    'body' => $this->body,
                    'statusCallback' => route('webhooks.twilio.status'),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Optional persistence
            |--------------------------------------------------------------------------
            */

            if (class_exists(\App\Models\WhatsApp\WhatsAppMessage::class)) {

                $templateName = null;

                if ($this->templateId && class_exists(\App\Models\WhatsApp\WhatsAppTemplate::class)) {

                    $tpl = \App\Models\WhatsApp\WhatsAppTemplate::find($this->templateId);

                    $templateName = $tpl?->name;
                }

                \App\Models\WhatsApp\WhatsAppMessage::create([
                    'provider'      => 'twilio',
                    'direction'     => 'outbound',
                    'to_number'     => $this->toE164,
                    'from_number'   => $from,
                    'template'      => $templateName,
                    'payload'       => json_encode(
                        $this->meta + [
                            'body' => $this->body,
                            'sid'  => $msg->sid ?? null
                        ]
                    ),
                    'status'        => 'queued',
                    'error_code'    => null,
                    'error_message' => null,
                ]);
            }

        } catch (\Throwable $e) {

            Log::error('[WA][Twilio] Send failed', [
                'to'  => $this->toE164,
                'err' => $e->getMessage()
            ]);
        }
    }
}