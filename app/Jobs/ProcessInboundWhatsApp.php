<?php

namespace App\Jobs;

use App\Models\MessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string  $from,
        public string  $to,
        public string  $body,
        public ?string $sid = null,
        public int     $numMedia = 0,
        public ?string $profileName = null,
        public string  $provider = 'twilio',
        public array   $payload = [],
        public ?int    $companyId = null,
        public ?int    $leadId = null
    ) {}

    public function handle(): void
    {
        try {
            MessageLog::create([
                'company_id'          => $this->companyId ?? 1, // or auth()->user()->company_id if context known
                'lead_id'             => $this->leadId,
                'direction'           => 'in',
                'channel'             => 'whatsapp',
                'to_number'           => $this->to,
                'from_number'         => $this->from,
                'template'            => null,
                'body'                => $this->body,
                'provider_message_id' => $this->sid,
                'provider_status'     => 'received',
                'meta'                => $this->payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Failed to insert inbound message', [
                'sid'   => $this->sid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
