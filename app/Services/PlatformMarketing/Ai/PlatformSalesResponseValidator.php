<?php

namespace App\Services\PlatformMarketing\Ai;

class PlatformSalesResponseValidator
{
    public function clean(string $response): string
    {
        $response = trim($response);

        foreach (['guaranteed ROI', 'guaranteed revenue', 'secret prompt', 'API key'] as $blocked) {
            $response = str_ireplace($blocked, '', $response);
        }

        if ($response === '') {
            return 'Thanks. Can you tell me a little about your garage and current follow-up process?';
        }

        return mb_substr($response, 0, 900);
    }
}
