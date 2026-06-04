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

            $companyId = (int) ($this->meta['company_id'] ?? 0);

            $twilioSid   = config('services.twilio.sid');
            $twilioToken = config('services.twilio.token');
            $from        = config('services.twilio.whatsapp_from');

            $tw = new Client($twilioSid, $twilioToken);

            Log::info('[WA][Twilio] Sending message', [
                'company_id' => $companyId ?: null,
                'to' => $this->maskPhone($this->toE164),
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

                if ($this->templateId && $companyId && class_exists(\App\Models\WhatsApp\WhatsAppTemplate::class)) {

                    $tpl = \App\Models\WhatsApp\WhatsAppTemplate::where('company_id', $companyId)
                        ->find($this->templateId);

                    $templateName = $tpl?->name;
                }

                \App\Models\WhatsApp\WhatsAppMessage::create([
                    'company_id'     => $companyId ?: null,
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
                'company_id' => $this->meta['company_id'] ?? null,
                'to'  => $this->maskPhone($this->toE164),
                'err' => $e->getMessage()
            ]);
        }
    }

    protected function maskPhone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }
}
