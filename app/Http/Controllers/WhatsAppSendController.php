<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\TwilioWhatsAppService;
use Illuminate\Http\Request;

class WhatsAppSendController extends Controller
{
    public function send(Request $request, TwilioWhatsAppService $svc)
    {
        $data = $request->validate([
            'to'          => 'required|string',
            'template'    => 'required|string', // e.g. lead_created, opp_confirmed, generic
            'vars'        => 'array',           // optional: ["Sam", "LEAD-123"] or {"name":"Sam","lead":"LEAD-123"}
            'media_urls'  => 'array',           // optional: ["https://.../file.jpg"]
            'media_urls.*'=> 'url',
        ]);

        $res = $svc->sendTemplate(
            $data['to'],
            $data['template'],
            $data['vars'] ?? [],
            $data['media_urls'] ?? []
        );

        return response()->json($res, $res['ok'] ? 200 : 422);
    }
}
