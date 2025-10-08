<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class TwilioWhatsApp
{
    protected string $sid;
    protected string $token;
    protected string $from;

    public function __construct()
    {
        $this->sid   = (string) Config::get('services.whatsapp.twilio.sid');
        $this->token = (string) Config::get('services.whatsapp.twilio.token');
        $this->from  = (string) Config::get('services.whatsapp.twilio.from'); // e.g. whatsapp:+14155238886

        if (!$this->sid || !$this->token || !$this->from) {
            throw new \RuntimeException(
                'Twilio credentials missing. Check .env and config/services.php for TWILIO_SID, TWILIO_TOKEN, TWILIO_WHATSAPP_FROM.'
            );
        }
    }

    /**
     * Send a plain text WhatsApp message via Twilio REST API.
     * Accepts $to as "+9715xxxxxxx" or "whatsapp:+9715xxxxxxx".
     */
    public function send(string $to, string $body): array
    {
        $to = $this->normalizeTo($to);

        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";

        $resp = Http::asForm()
            ->withBasicAuth($this->sid, $this->token)
            // If you had SSL issues locally, you might have set ->withOptions(['verify' => false])
            // Prefer keeping verification ON; only disable if absolutely necessary.
            ->post($endpoint, [
                'From' => $this->from,
                'To'   => $to,
                'Body' => $body,
            ]);

        if ($resp->successful()) {
            return ['ok' => true, 'sid' => $resp->json('sid')];
        }

        return [
            'ok'    => false,
            'error' => $resp->json('message') ?? $resp->body(),
            'status'=> $resp->status(),
        ];
    }

    protected function normalizeTo(string $to): string
    {
        $to = trim($to);

        // Already prefixed
        if (str_starts_with($to, 'whatsapp:')) {
            return $to;
        }

        // Convert 00 → +
        if (str_starts_with($to, '00')) {
            $to = '+' . substr($to, 2);
        }

        // Ensure it begins with +<countrycode>
        if (!str_starts_with($to, '+')) {
            // At this point we assume $to already includes country code; if not, add yours.
            // e.g., for UAE default you could prepend '+971' – but safer to require full E.164.
            // throw new \InvalidArgumentException('Recipient must be an E.164 number starting with +.');
        }

        return 'whatsapp:' . $to;
    }
}
