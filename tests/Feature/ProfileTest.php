<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMediaTeamMetaOnly;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            EnsureMediaTeamMetaOnly::class,
            EnsureUserIsActive::class,
            ForcePasswordChange::class,
            RoleMiddleware::class,
        ]);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('admin.profile.edit'))
            ->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('admin.profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.profile.edit', absolute: false));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('admin.profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.profile.edit', absolute: false));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->delete(route('admin.profile.destroy'), [
                'password' => 'password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('admin.profile.edit'))
            ->delete(route('admin.profile.destroy'), [
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('admin.profile.edit', absolute: false));

        $this->assertNotNull($user->fresh());
    }
}
