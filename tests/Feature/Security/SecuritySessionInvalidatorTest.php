<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Security\SecuritySessionInvalidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecuritySessionInvalidatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database', 'session.table' => 'sessions']);
    }

    private function seedSession(string $id, ?int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode('x'),
            'last_activity' => now()->getTimestamp(),
        ]);
    }

    public function test_invalidate_other_sessions_keeps_current_and_leaves_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->seedSession('keep', $user->id);
        $this->seedSession('drop', $user->id);
        $this->seedSession('other', $other->id);

        $removed = app(SecuritySessionInvalidator::class)->invalidateOtherSessions($user, 'keep');

        $this->assertSame(1, $removed);
        $this->assertDatabaseHas('sessions', ['id' => 'keep']);
        $this->assertDatabaseMissing('sessions', ['id' => 'drop']);
        $this->assertDatabaseHas('sessions', ['id' => 'other']);
    }

    public function test_password_reset_kills_all_user_sessions_and_tokens(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->seedSession('u1', $user->id);
        $this->seedSession('u2', $user->id);
        $this->seedSession('o1', $other->id);
        $user->createToken('api');
        $user->createToken('api2');

        app(SecuritySessionInvalidator::class)->afterPasswordReset($user);

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'o1']);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_revoke_api_tokens_only_targets_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->createToken('api');
        $other->createToken('api');

        app(SecuritySessionInvalidator::class)->revokeApiTokens($user);

        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(1, $other->tokens()->count());
    }
}
