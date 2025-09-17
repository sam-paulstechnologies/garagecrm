<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TwilioWhatsApp
{
    protected string $sid;
    protected string $token;
    protected string $from;

    public function __construct()
    {
        $this->sid   = env('TWILIO_SID');
        $this->token = env('TWILIO_TOKEN');
        $this->from  = env('TWILIO_WHATSAPP_FROM'); // e.g. whatsapp:+14155238886
    }

    public function send(string $to, string $body): array
    {
        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";

        $resp = Http::asForm()
            ->withBasicAuth($this->sid, $this->token)
            ->withOptions(['verify' => false])
            ->post($endpoint, [
                'From' => $this->from,
                'To'   => "whatsapp:{$to}", // e.g. +9715xxxxxxx
                'Body' => $body,
            ]);

        if ($resp->successful()) {
            return ['ok' => true, 'sid' => $resp->json('sid')];
        }

        return ['ok' => false, 'error' => $resp->body()];
    }
}
