<?php

namespace App\Services\PlatformMarketing;

class PlatformPhoneNormalizer
{
    public function normalize(?string $number): string
    {
        $number = trim((string) $number);
        $number = preg_replace('/^whatsapp:/i', '', $number);
        $number = preg_replace('/\D+/', '', $number);

        if ($number === '') {
            return '';
        }

        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }

        if (str_starts_with($number, '05')) {
            $number = '971'.substr($number, 1);
        }

        if (str_starts_with($number, '9710')) {
            $number = '971'.substr($number, 3);
        }

        return $number;
    }

    public function display(?string $number): string
    {
        $normalized = $this->normalize($number);

        return $normalized === '' ? 'Unknown' : '+'.$normalized;
    }
}
