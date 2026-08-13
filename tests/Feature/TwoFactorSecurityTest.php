<?php

namespace Tests\Feature;

use App\Models\System\Company;
use App\Models\User;
use App\Security\SecurityAudit;
use App\Security\TwoFactorPolicy;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('security.two_factor_enforcement', 'required_admins');
        Route::middleware(['web', 'auth', 'security.step-up'])
            ->post('/_test/security-sensitive', fn () => response('allowed'));
    }

    public function test_required_admin_password_login_is_gated_to_enrollment(): void
    {
        $admin = $this->user('admin');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->get('/dashboard')->assertRedirect(route('security.two-factor.show'));
        $this->get(route('security.two-factor.show'))->assertOk()->assertSee('Required for your role');
    }

    public function test_required_admin_can_confirm_password_once_and_continue_to_enrollment_setup(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('security.two-factor.show'))
            ->assertOk()
            ->assertSee('Confirm password to begin')
            ->assertDontSee('Generate secure setup QR');

        $this->get(route('password.confirm', [
            'return_to' => route('security.two-factor.show', absolute: false),
        ]))
            ->assertOk()
            ->assertSee('ConfirmPassword', false);

        $this->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertRedirect(route('security.two-factor.show', absolute: false))
            ->assertSessionHas('auth.password_confirmed_at');

        $this->get(route('security.two-factor.show'))
            ->assertOk()
            ->assertSee('Generate secure setup QR')
            ->assertDontSee('Confirm password to begin')
            ->assertDontSee('<iframe', false);

        $this->get(route('security.two-factor.show'))
            ->assertOk()
            ->assertSee('Generate secure setup QR')
            ->assertDontSee('Confirm password to begin');
    }

    public function test_password_confirmation_post_is_narrowly_accessible_during_mandatory_enrollment(): void
    {
        $admin = $this->user('admin');

        $response = $this->actingAs($admin)
            ->withSession(['url.intended' => route('security.two-factor.show', absolute: false)])
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('password.confirm.store'), ['password' => 'password']);

        $response
            ->assertRedirect(route('security.two-factor.show', absolute: false))
            ->assertSessionHas('auth.password_confirmed_at');
        $this->assertNotSame(423, $response->getStatusCode());
    }

    public function test_wrong_password_does_not_confirm_or_escape_mandatory_enrollment(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->withSession(['url.intended' => route('security.two-factor.show', absolute: false)])
            ->post(route('password.confirm.store'), ['password' => 'incorrect-password'])
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('auth.password_confirmed_at');

        $this->get('/dashboard')->assertRedirect(route('security.two-factor.show'));
        $this->get(route('security.two-factor.show'))
            ->assertOk()
            ->assertSee('Confirm password to begin')
            ->assertDontSee('Generate secure setup QR');
    }

    public function test_enrollment_gate_allows_only_required_support_routes_and_logout(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('password.confirm'))->assertOk();
        $this->get(route('security.two-factor.show'))->assertOk();
        $this->get('/dashboard')->assertRedirect(route('security.two-factor.show'));
        $this->post(route('logout'))->assertRedirect(route('login', absolute: false));
        $this->assertGuest();
    }

    public function test_operational_user_two_factor_is_optional(): void
    {
        $manager = $this->user('manager');

        $this->actingAs($manager)->get(route('security.two-factor.show'))
            ->assertOk()->assertSee('Optional for your role');
    }

    public function test_secret_generation_does_not_enable_until_valid_totp_and_recovery_acknowledgement(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('security.two-factor.enable'))->assertRedirect();
        $admin->refresh();
        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_recovery_codes);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertFalse($admin->hasConfirmedTwoFactorAuthentication());

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('security.two-factor.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        Cache::flush();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('security.two-factor.confirm'), ['code' => $this->currentCode($admin->refresh())])
            ->assertRedirect(route('security.two-factor.recovery-codes'));
        $this->assertFalse($admin->refresh()->hasConfirmedTwoFactorAuthentication());

        $this->actingAs($admin)->withSession(['security.show_recovery_codes' => true])
            ->post(route('security.two-factor.recovery-codes.acknowledge'), ['acknowledged' => '1'])
            ->assertRedirect(route('dashboard'));
        $this->assertTrue($admin->refresh()->hasConfirmedTwoFactorAuthentication());
    }

    public function test_qr_code_is_only_available_to_the_authenticated_owner_after_password_confirmation(): void
    {
        $admin = $this->user('admin');
        app(EnableTwoFactorAuthentication::class)($admin);

        $this->actingAs($admin)->get(route('security.two-factor.show'))
            ->assertOk()->assertDontSee('<svg', false);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.two-factor.show'))->assertOk()->assertSee('<svg', false);

        $other = $this->user('admin');
        $this->actingAs($other)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.two-factor.show'))->assertOk()->assertDontSee(Fortify::currentEncrypter()->decrypt($admin->two_factor_secret));
    }

    public function test_confirmed_user_password_alone_never_receives_full_session(): void
    {
        $admin = $this->confirmedUser('admin');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->assertTrue(session()->has('login.id'));

        Cache::flush();
        $oldSession = session()->getId();
        $this->post(route('two-factor.login.store'), ['code' => $this->currentCode($admin)])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($oldSession, session()->getId());
    }

    public function test_inertia_password_login_uses_top_level_two_factor_navigation(): void
    {
        $admin = $this->confirmedUser('admin');

        $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('two-factor.login', absolute: false));

        $this->assertGuest();
        $this->assertTrue(session()->has('login.id'));
    }

    public function test_totp_login_preserves_safe_intended_destination_and_back_target_is_not_reusable(): void
    {
        $admin = $this->confirmedUser('admin');
        $intended = '/admin/clients?status=active';

        $this->withSession(['url.intended' => $intended])
            ->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        Cache::flush();
        $oldSession = session()->getId();
        $this->post(route('two-factor.login.store'), ['code' => $this->currentCode($admin)])
            ->assertRedirect($intended);

        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($oldSession, session()->getId());
        $this->get(route('two-factor.login'))->assertRedirect(route('dashboard'));
    }

    public function test_inertia_totp_completion_uses_canonical_top_level_location(): void
    {
        $admin = $this->confirmedUser('admin');
        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        Cache::flush();
        $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('two-factor.login.store'), ['code' => $this->currentCode($admin)])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_recovery_code_login_preserves_intended_destination(): void
    {
        $admin = $this->confirmedUser('admin');
        $code = $admin->recoveryCodes()[0];
        $intended = '/admin/billing';

        $this->withSession(['url.intended' => $intended])
            ->post('/login', ['email' => $admin->email, 'password' => 'password']);
        $this->post(route('two-factor.login.store'), ['recovery_code' => $code])
            ->assertRedirect($intended);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_authenticated_login_and_completed_challenge_routes_redirect_to_dashboard(): void
    {
        $admin = $this->confirmedUser('admin');

        $this->actingAs($admin)->get('/login')->assertRedirect(route('dashboard'));
        $this->get(route('two-factor.login'))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_remember_me_does_not_bypass_two_factor_challenge(): void
    {
        $admin = $this->confirmedUser('admin');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_recovery_code_works_once_and_cannot_be_reused(): void
    {
        $admin = $this->confirmedUser('admin');
        $code = $admin->recoveryCodes()[0];

        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
        $this->post(route('two-factor.login.store'), ['recovery_code' => $code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
        $this->post(route('two-factor.login.store'), ['recovery_code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_regeneration_invalidates_previous_codes(): void
    {
        $admin = $this->confirmedUser('admin');
        $old = $admin->recoveryCodes();
        app(GenerateNewRecoveryCodes::class)($admin);

        $this->assertEmpty(array_intersect($old, $admin->refresh()->recoveryCodes()));
    }

    public function test_password_change_preserves_two_factor_configuration(): void
    {
        $admin = $this->confirmedUser('admin');
        $secret = $admin->two_factor_secret;
        $codes = $admin->two_factor_recovery_codes;

        $admin->update(['password' => 'A-New-Synthetic-Password-2026!']);

        $this->assertSame($secret, $admin->fresh()->two_factor_secret);
        $this->assertSame($codes, $admin->fresh()->two_factor_recovery_codes);
        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
    }

    public function test_forgot_password_reset_preserves_two_factor_configuration(): void
    {
        $admin = $this->confirmedUser('admin');
        $secret = $admin->two_factor_secret;
        $codes = $admin->two_factor_recovery_codes;
        $token = Password::broker()->createToken($admin);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'Reset-Synthetic-Password-2026!',
            'password_confirmation' => 'Reset-Synthetic-Password-2026!',
        ])->assertSessionHasNoErrors();

        $this->assertSame($secret, $admin->fresh()->two_factor_secret);
        $this->assertSame($codes, $admin->fresh()->two_factor_recovery_codes);
        $this->assertTrue($admin->fresh()->hasConfirmedTwoFactorAuthentication());
    }

    public function test_disabling_mandatory_admin_two_factor_immediately_forces_reenrollment(): void
    {
        $admin = $this->confirmedUser('admin');

        $this->actingAs($admin)->withSession([
            'security_step_up_at' => time(),
            'auth.password_confirmed_at' => time(),
        ])->delete(route('security.two-factor.disable'))->assertRedirect(route('security.two-factor.show'));

        $this->assertNull($admin->fresh()->two_factor_secret);
        $this->get('/dashboard')->assertRedirect(route('security.two-factor.show'));
    }

    public function test_required_sensitive_application_routes_have_step_up_middleware(): void
    {
        foreach ([
            'admin.billing.checkout',
            'admin.billing.cancel',
            'admin.billing.portal',
            'admin.users.store',
            'admin.users.update',
            'admin.users.destroy',
            'admin.messaging.whatsapp.number.store',
            'admin.messaging.whatsapp.number.update',
            'admin.messaging.whatsapp.number.destroy',
            'admin.messaging.whatsapp.start',
            'admin.messaging.whatsapp.complete',
            'admin.messaging.whatsapp.retry',
            'admin.messaging.whatsapp.disconnect',
            'admin.lead-sources.meta.connect',
            'admin.lead-sources.meta.select-page',
            'admin.lead-sources.meta.disconnect',
            'admin.settings.update',
            'api.whatsapp.settings.update',
            'super-admin.commercial.launch-offer.update',
            'super-admin.platform-users.store',
            'super-admin.platform-users.update',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing sensitive route {$name}");
            $this->assertContains('security.step-up', $route->middleware(), "Missing step-up on {$name}");
        }
    }

    public function test_plan_or_company_assignment_does_not_change_two_factor_policy(): void
    {
        $freeAdmin = $this->user('admin');
        $platform = $this->user('super_admin', null);
        $manager = $this->user('manager');

        $this->assertTrue(app(TwoFactorPolicy::class)->isMandatory($freeAdmin));
        $this->assertTrue(app(TwoFactorPolicy::class)->isMandatory($platform));
        $this->assertFalse(app(TwoFactorPolicy::class)->isMandatory($manager));
    }

    public function test_required_unenrolled_admin_api_token_is_not_fully_privileged(): void
    {
        $admin = $this->user('admin');
        $token = $admin->createToken('2fa-gate-test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertStatus(423)->assertJson(['two_factor_enrollment_required' => true]);
    }

    public function test_sensitive_action_requires_recent_password_and_totp_step_up(): void
    {
        $admin = $this->confirmedUser('admin');

        $this->actingAs($admin)->post('/_test/security-sensitive')->assertRedirect(route('security.step-up.show'));
        Cache::flush();
        $this->actingAs($admin)->post(route('security.step-up.store'), [
            'password' => 'password',
            'code' => $this->currentCode($admin),
        ])->assertRedirect();
        $this->post('/_test/security-sensitive')->assertOk();

        $this->withSession(['security_step_up_at' => now()->subMinutes(16)->getTimestamp()])
            ->post('/_test/security-sensitive')->assertRedirect(route('security.step-up.show'));
    }

    public function test_optional_manager_still_needs_two_factor_if_granted_a_sensitive_action(): void
    {
        $manager = $this->user('manager');

        $this->actingAs($manager)->post('/_test/security-sensitive')
            ->assertRedirect(route('security.two-factor.show'));
    }

    public function test_admin_reset_is_tenant_scoped_audited_and_forces_reenrollment(): void
    {
        $admin = $this->confirmedUser('admin');
        $ordinary = $this->confirmedUser('mechanic', $admin->company);
        $other = $this->confirmedUser('mechanic');

        $this->actingAs($admin)->withSession(['security_step_up_at' => time()])
            ->post(route('security.two-factor.admin-reset', $other), ['reason' => 'Verified support recovery request.'])
            ->assertForbidden();
        $this->actingAs($admin)->withSession(['security_step_up_at' => time()])
            ->post(route('security.two-factor.admin-reset', $ordinary), ['reason' => 'Verified support recovery request.'])
            ->assertRedirect();

        $this->assertNull($ordinary->fresh()->two_factor_secret);
        $this->assertNotNull($ordinary->fresh()->two_factor_reenrollment_required_at);
        $this->actingAs($ordinary->fresh())->get('/dashboard')
            ->assertRedirect(route('security.two-factor.show'));
        $this->assertDatabaseHas('security_audit_logs', [
            'event' => 'two_factor.administratively_reset',
            'actor_user_id' => $admin->id,
            'target_user_id' => $ordinary->id,
        ]);
    }

    public function test_tenant_admin_cannot_reset_platform_user(): void
    {
        $admin = $this->confirmedUser('admin');
        $platform = $this->confirmedUser('super_admin', null);

        $this->actingAs($admin)->withSession(['security_step_up_at' => time()])
            ->post(route('security.two-factor.admin-reset', $platform), ['reason' => 'Attempted invalid platform reset.'])
            ->assertForbidden();
    }

    public function test_repeated_invalid_challenges_are_throttled_without_revealing_factor_details(): void
    {
        config()->set('security.challenge_attempts_per_minute', 3);
        $admin = $this->confirmedUser('admin');
        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        foreach (range(1, 3) as $_) {
            $this->post(route('two-factor.login.store'), ['code' => '000000'])->assertSessionHasErrors('code');
        }

        $this->post(route('two-factor.login.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertDatabaseHas('security_audit_logs', ['event' => 'two_factor.challenge_throttled']);
    }

    public function test_security_audit_rejects_sensitive_authentication_material(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(SecurityAudit::class)->record('unsafe.test', $this->user('admin'), context: ['secret' => 'never-store']);
    }

    public function test_free_registration_creates_admin_then_enforces_enrollment_without_breaking_transaction(): void
    {
        $this->seed(CommercialFoundationSeeder::class);
        config()->set('registration.public_enabled', true);

        $this->post('/register', [
            'garage_name' => 'Two Factor Registration Garage',
            'name' => 'Two Factor Test Admin',
            'email' => 'two-factor-registration@staging.sayaraforce.test',
            'phone' => '+971500001234',
            'address' => 'Synthetic Security Test Address',
            'password' => 'Synthetic-Only-Password-2026!',
            'password_confirmation' => 'Synthetic-Only-Password-2026!',
            'terms' => '1',
        ])->assertRedirect(route('admin.messaging.whatsapp.index'));

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('users', 1);
        $this->get(route('admin.messaging.whatsapp.index'))->assertRedirect(route('security.two-factor.show'));
    }

    private function user(string $role, ?Company $company = null): User
    {
        if ($role !== 'super_admin' && $company === null) {
            $company = Company::query()->create(['name' => fake()->unique()->company(), 'status' => 'active']);
        }

        return User::factory()->create([
            'role' => $role,
            'company_id' => $company?->id,
            'status' => true,
            'must_change_password' => false,
        ]);
    }

    private function confirmedUser(string $role, ?Company $company = null): User
    {
        $user = $this->user($role, $company);
        app(EnableTwoFactorAuthentication::class)($user);
        app(ConfirmTwoFactorAuthentication::class)($user, $this->currentCode($user->refresh()));
        $user->forceFill(['two_factor_recovery_codes_acknowledged_at' => now()])->save();
        Cache::flush();

        return $user->refresh();
    }

    private function currentCode(User $user): string
    {
        return (new Google2FA)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
    }
}
