<?php

namespace App\Security;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

/**
 * Fable final remediation (M6 residual): hash two-factor recovery codes at rest.
 *
 * Recovery codes are one-time backup authentication factors. Fortify's default
 * storage keeps them recoverably encrypted, so DB read + APP_KEY compromise
 * yields plaintext codes and bypasses the second factor. This service persists
 * only per-code cryptographic hashes (bcrypt/argon via the configured hasher),
 * displays plaintext exactly once at generation, verifies with a constant-time
 * hash check, and migrates any surviving legacy plaintext codes to hashed form
 * the first time they are read for consumption — so already-enrolled users are
 * never locked out.
 *
 * Atomic one-time consumption (M6) is preserved: {@see self::consume()} is
 * always invoked inside the caller's row-locked transaction.
 */
class RecoveryCodeService
{
    private const CODE_COUNT = 8;

    /**
     * Generate a fresh set of recovery codes, persist only their hashes, and
     * return the plaintext codes for a single, one-time display to the user.
     *
     * @return array<int,string> plaintext codes (never stored in plaintext)
     */
    public function generateForUser(User $user): array
    {
        $plaintext = collect(range(1, self::CODE_COUNT))
            ->map(fn (): string => RecoveryCode::generate())
            ->all();

        $this->store($user, array_map(fn (string $code): string => Hash::make($code), $plaintext));

        return $plaintext;
    }

    /**
     * Atomically verify and consume a submitted recovery code.
     *
     * MUST be called inside a row-locked transaction (see
     * TwoFactorChallengeController) so concurrent submissions of the same code
     * serialise and exactly one succeeds. Supports both hashed and legacy
     * plaintext entries, and opportunistically migrates the remaining legacy
     * codes to hashed form on success.
     */
    public function consume(User $user, string $submitted): bool
    {
        $submitted = trim($submitted);
        if ($submitted === '') {
            return false;
        }

        $stored = $this->read($user);
        $matchedIndex = null;

        foreach ($stored as $index => $entry) {
            if ($this->matches($submitted, (string) $entry)) {
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedIndex === null) {
            return false;
        }

        unset($stored[$matchedIndex]);

        // Consume the matched code and migrate any surviving legacy plaintext
        // codes to hashed form so the reversible format is retired on use.
        $remaining = array_map(function (string $entry): string {
            return $this->looksHashed($entry) ? $entry : Hash::make($entry);
        }, array_values(array_map('strval', $stored)));

        $this->store($user, $remaining);

        return true;
    }

    /**
     * @return array<int,string>
     */
    private function read(User $user): array
    {
        if (blank($user->two_factor_recovery_codes)) {
            return [];
        }

        $decoded = json_decode(
            Fortify::currentEncrypter()->decrypt($user->two_factor_recovery_codes),
            true,
        );

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    /**
     * @param  array<int,string>  $codes
     */
    private function store(User $user, array $codes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(array_values($codes))),
        ])->save();
    }

    private function matches(string $submitted, string $entry): bool
    {
        if ($this->looksHashed($entry)) {
            return Hash::check($submitted, $entry);
        }

        // Legacy plaintext code: constant-time comparison.
        return hash_equals($entry, $submitted);
    }

    private function looksHashed(string $entry): bool
    {
        return (bool) preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $entry);
    }
}
