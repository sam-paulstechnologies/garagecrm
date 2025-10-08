<?php

namespace App\Services\IntegrationTest;

class MetaTester
{
    public function test(string $accessToken): array
    {
        if (!trim($accessToken)) {
            return ['ok' => false, 'message' => 'Meta access token missing.'];
        }
        // Real call later; for now, basic sanity check:
        $looksOk = strlen($accessToken) > 20;
        return $looksOk
            ? ['ok' => true,  'message' => 'Token format looks valid. (Live test pending)']
            : ['ok' => false, 'message' => 'Token looks too short/invalid.'];
    }
}
