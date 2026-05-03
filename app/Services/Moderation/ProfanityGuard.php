<?php

namespace App\Services\Moderation;

class ProfanityGuard
{
    protected array $badWords = [
        'abuse','fuck','shit','bitch'
    ];

    public function contains(string $text): bool
    {
        foreach ($this->badWords as $word) {
            if (stripos($text, $word) !== false) {
                return true;
            }
        }
        return false;
    }
}
