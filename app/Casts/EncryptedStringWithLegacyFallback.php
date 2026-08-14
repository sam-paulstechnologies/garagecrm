<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Transitional encrypted cast for provider tokens that pre-date encrypted-at-
 * rest storage. New writes are always encrypted; legacy plaintext remains
 * readable only so a controlled migration command can rotate it safely.
 */
final class EncryptedStringWithLegacyFallback implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (Throwable) {
            return (string) $value;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            Crypt::decryptString((string) $value);

            return (string) $value;
        } catch (Throwable) {
            return Crypt::encryptString((string) $value);
        }
    }
}
