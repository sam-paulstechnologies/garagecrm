<?php

namespace App\Services\IntegrationTest;

class TwilioTester
{
    public function test(string $sid, string $token, string $from): array
    {
        if (!trim($sid) || !trim($token)) {
            return ['ok' => false, 'message' => 'SID or Auth Token missing.'];
        }
        if (!trim($from)) {
            return ['ok' => false, 'message' => 'WhatsApp From number missing.'];
        }
        $ok = (str_starts_with($from, 'whatsapp:+') || str_starts_with($from, '+')) && strlen($sid) >= 10 && strlen($token) >= 10;
        return $ok
            ? ['ok' => true,  'message' => 'Credentials format looks valid. (Live test pending)']
            : ['ok' => false, 'message' => 'One or more fields look malformed.'];
    }
}
