<?php

namespace App\Services\Services;

class ServiceTypeResolver
{
    /**
     * Resolve service type using weighted keyword scoring
     */
    public function resolve(?string $text): ?string
    {
        $t = $this->normalize($text);

        if ($t === '') {
            return null;
        }

        $map = [
            'Oil Change' => [
                'oil change','engine oil','oil service','change oil','oil filter'
            ],
            'Brake Service' => [
                'brake','break','pads','pad','disc','rotor','brake fluid'
            ],
            'Tyre Replacement' => [
                'tyre','tire','puncture','flat','wheel','alignment','balancing'
            ],
            'Battery Replacement' => [
                'battery','jump start','no start','wont start','won’t start'
            ],
            'AC Repair' => [
                'ac','a/c','aircon','air conditioner','cooling'
            ],
            'Engine/Check Light' => [
                'check engine','engine light','misfire','overheating','smoke'
            ],
            'Transmission' => [
                'gearbox','transmission','gear slipping','clutch'
            ],
            'Major Repair' => [
                'accident','body work','dent','paint','collision'
            ],
            'General Service' => [
                'service','maintenance','repair','fix','inspection','diagnosis','diagnostic'
            ],
        ];

        $scores = [];

        foreach ($map as $label => $keywords) {
            $score = 0;

            foreach ($keywords as $kw) {
                if (str_contains($t, $kw)) {
                    $score += strlen($kw); // 🔥 weighted scoring
                }
            }

            if ($score > 0) {
                $scores[$label] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        // 🔥 pick highest confidence match
        arsort($scores);

        return array_key_first($scores);
    }

    /**
     * Normalize messy text (WhatsApp / OCR safe)
     */
    protected function normalize(?string $text): string
    {
        $t = strtolower((string) $text);

        // Remove special characters
        $t = preg_replace('/[^a-z0-9\s]/', ' ', $t);

        // Collapse spaces
        $t = preg_replace('/\s+/', ' ', $t);

        return trim($t);
    }
}