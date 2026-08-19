<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Security\RecoveryCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class RecoveryCodeHashingTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Recovery Tester',
            'email' => 'recovery-'.uniqid().'@example.test',
            'password' => 'Strong-Password-2026!',
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);
    }

    /**
     * @return array<int,string>
     */
    private function storedCodes(User $user): array
    {
        return json_decode(Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_recovery_codes), true);
    }

    private function isHashed(string $value): bool
    {
        return (bool) preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $value);
    }

    public function test_generated_recovery_codes_are_hashed_at_rest(): void
    {
        $user = $this->user();
        $plaintext = app(RecoveryCodeService::class)->generateForUser($user);

        $this->assertCount(8, $plaintext);

        $stored = $this->storedCodes($user);
        $this->assertCount(8, $stored);

        foreach ($stored as $entry) {
            $this->assertTrue($this->isHashed($entry), 'Recovery code is not hashed at rest.');
        }

        // No plaintext code is recoverable from storage even with APP_KEY.
        foreach ($plaintext as $code) {
            $this->assertNotContains($code, $stored);
        }
    }

    public function test_hashed_recovery_code_can_be_consumed_exactly_once(): void
    {
        $user = $this->user();
        $plaintext = app(RecoveryCodeService::class)->generateForUser($user);
        $code = $plaintext[0];

        $this->assertTrue(app(RecoveryCodeService::class)->consume($user->fresh(), $code));
        $this->assertFalse(app(RecoveryCodeService::class)->consume($user->fresh(), $code));

        // The remaining seven codes are still hashed and usable.
        $this->assertCount(7, $this->storedCodes($user));
        $this->assertTrue(app(RecoveryCodeService::class)->consume($user->fresh(), $plaintext[1]));
    }

    public function test_legacy_plaintext_code_is_accepted_and_migrates_remaining_to_hashes(): void
    {
        $user = $this->user();

        // Simulate a pre-existing (legacy) reversible store of plaintext codes.
        $legacy = ['AAAAA-11111', 'BBBBB-22222', 'CCCCC-33333'];
        $user->forceFill([
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($legacy)),
        ])->save();

        $this->assertTrue(app(RecoveryCodeService::class)->consume($user->fresh(), 'AAAAA-11111'));

        $stored = $this->storedCodes($user);
        $this->assertCount(2, $stored);

        // The consumed code is gone and every surviving code is now hashed.
        foreach ($stored as $entry) {
            $this->assertTrue($this->isHashed($entry), 'Legacy code was not migrated to a hash.');
        }
        $this->assertNotContains('AAAAA-11111', $stored);

        // A surviving legacy code still authenticates after migration.
        $this->assertTrue(app(RecoveryCodeService::class)->consume($user->fresh(), 'BBBBB-22222'));
        // ...but a consumed one never works again.
        $this->assertFalse(app(RecoveryCodeService::class)->consume($user->fresh(), 'AAAAA-11111'));
    }

    public function test_atomic_consumption_serialises_same_code(): void
    {
        // Functional proof of one-time consumption under the row-locked
        // transaction used by the challenge controller: the same code inside a
        // transaction succeeds once and then fails.
        $user = $this->user();
        $plaintext = app(RecoveryCodeService::class)->generateForUser($user);
        $code = $plaintext[0];

        $results = [];
        foreach (range(1, 2) as $ignored) {
            $results[] = DB::transaction(function () use ($user, $code): bool {
                $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->first();

                return app(RecoveryCodeService::class)->consume($locked, $code);
            });
        }

        $this->assertSame([true, false], $results);
    }
}
