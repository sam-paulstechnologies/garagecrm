<?php

namespace App\Services;

class PhoneNumberService
{
    public function cleanRawPhone(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (stripos($value, 'E+') !== false || stripos($value, 'E-') !== false) {
            $value = number_format((float) $value, 0, '', '');
        }

        $cleaned = preg_replace('/[^\d+]/', '', $value) ?: '';

        if (str_starts_with($cleaned, '+')) {
            $cleaned = '+' . preg_replace('/\D+/', '', substr($cleaned, 1));
        } else {
            $cleaned = preg_replace('/\D+/', '', $cleaned);
        }

        return $cleaned !== '' ? $cleaned : null;
    }

    public function normalizeToE164(mixed $value, string $country = 'AE'): ?string
    {
        $phone = $this->cleanRawPhone($value);

        if (! $phone) {
            return null;
        }

        $country = strtoupper(trim($country ?: 'AE'));

        if (str_starts_with($phone, '+')) {
            $digits = preg_replace('/\D+/', '', substr($phone, 1)) ?: '';

            return $this->isValidE164Digits($digits) ? '+' . $digits : null;
        }

        if (str_starts_with($phone, '00')) {
            $digits = substr($phone, 2);

            return $this->isValidE164Digits($digits) ? '+' . $digits : null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if ($country === 'AE') {
            if (preg_match('/^05\d{8}$/', $digits)) {
                return '+971' . substr($digits, 1);
            }

            if (preg_match('/^5\d{8}$/', $digits)) {
                return '+971' . $digits;
            }

            if (preg_match('/^9710(5\d{8})$/', $digits, $matches)) {
                return '+971' . $matches[1];
            }
        }

        return $this->isValidE164Digits($digits) ? '+' . $digits : null;
    }

    public function formatForDisplay(mixed $value, string $country = 'AE'): string
    {
        return $this->normalizeToE164($value, $country)
            ?? (string) ($this->cleanRawPhone($value) ?? '');
    }

    public function buildTelUrl(mixed $value, string $country = 'AE'): ?string
    {
        $e164 = $this->normalizeToE164($value, $country);

        return $e164 ? 'tel:' . $e164 : null;
    }

    public function buildWhatsappLookupKey(mixed $value, string $country = 'AE'): ?string
    {
        $e164 = $this->normalizeToE164($value, $country);

        if ($e164) {
            return ltrim($e164, '+');
        }

        $cleaned = $this->cleanRawPhone($value);

        return $cleaned ? ltrim($cleaned, '+') : null;
    }

    public function isValidMobileLikeNumber(mixed $value, string $country = 'AE'): bool
    {
        $e164 = $this->normalizeToE164($value, $country);

        if (! $e164) {
            return false;
        }

        if (strtoupper(trim($country ?: 'AE')) === 'AE') {
            return (bool) preg_match('/^\+9715\d{8}$/', $e164);
        }

        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $e164);
    }

    private function isValidE164Digits(string $digits): bool
    {
        return (bool) preg_match('/^[1-9]\d{7,14}$/', $digits);
    }
}
