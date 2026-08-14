<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Ensures new writes are encrypted while preserving the established model
 * contract: callers receive ciphertext and must deliberately decrypt it.
 */
final class EncryptedStoredString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? $value : (string) $value;
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
