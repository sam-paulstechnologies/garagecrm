<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundWhatsAppMessage;
use App\Models\Shared\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    // Meta verification: GET ?hub.mode=subscribe&hub.verify_token=...&hub.challenge=...
    public function verify(Request $req)
    {
        $token = config('services.whatsapp.verify_token');
        if ($req->get('hub_mode') === 'subscribe' || $req->get('hub.mode') === 'subscribe') {
            if ($req->get('hub_verify_token') === $token || $req->get('hub.verify_token') === $token) {
                return response($req->get('hub_challenge') ?? $req->get('hub.challenge'), 200);
            }
        }

        return response('Forbidden', 403);
    }

    // Receives message + status callbacks
    public function receive(Request $req)
    {
        $payload = $req->all();
        Log::info('WA inbound received', [
            'entry_count' => is_array($payload['entry'] ?? null) ? count($payload['entry']) : 0,
            'object' => mb_substr((string) ($payload['object'] ?? ''), 0, 40),
        ]);

        // Status updates (sent/delivered/read/failed) may come here — store raw:
        // But when there is a user message:
        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                if (($value['messages'] ?? null)) {
                    foreach ($value['messages'] as $m) {
                        $from = $m['from'] ?? null;
                        $text = $m['text']['body'] ?? ($m['button']['text'] ?? null);

                        WhatsappMessage::create([
                            'provider' => 'meta',
                            'direction' => 'inbound',
                            'from_number' => $from,
                            'payload' => json_encode($m),
                            'status' => 'received',
                        ]);

                        // Offload parsing → job
                        ProcessInboundWhatsAppMessage::dispatch($from, $text);
                    }
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
