<?php

namespace App\Messaging\WhatsApp;

use InvalidArgumentException;

class PhoneNumberNormalizer
{
    public function normalize(string $number, ?string $countryCode = null): string
    {
        $raw = trim($number);
        if ($raw === '') {
            throw new InvalidArgumentException('Enter a WhatsApp Business number.');
        }

        if (str_starts_with($raw, '+')) {
            $digits = preg_replace('/\D+/', '', $raw);
        } elseif (str_starts_with(preg_replace('/\s+/', '', $raw), '00')) {
            $digits = substr((string) preg_replace('/\D+/', '', $raw), 2);
        } else {
            $callingCode = preg_replace('/\D+/', '', (string) $countryCode);
            if ($callingCode === '') {
                throw new InvalidArgumentException('Choose a country code or enter the full number beginning with +.');
            }

            $national = ltrim((string) preg_replace('/\D+/', '', $raw), '0');
            $digits = $callingCode.$national;
        }

        if (! is_string($digits) || ! preg_match('/^[1-9][0-9]{7,14}$/', $digits)) {
            throw new InvalidArgumentException('Enter a valid international WhatsApp number.');
        }

        return '+'.$digits;
    }
}
