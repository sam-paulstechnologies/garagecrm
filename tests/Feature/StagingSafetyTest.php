<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyStagingIdentity;
use App\Models\User;
use App\Support\Staging\StagingSafety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StagingSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn (): string => 'staging');
        config([
            'staging.production.waba_ids' => 'prod-waba',
            'staging.production.phone_number_ids' => 'prod-phone',
            'staging.meta.allowed_waba_ids' => 'test-waba',
            'staging.meta.allowed_phone_number_ids' => 'test-phone',
            'staging.communications.allowed_phone_recipients' => '+971500000001',
            'staging.communications.allowed_email_recipients' => 'tester@example.test',
            'staging.communications.allowed_email_domains' => 'staging.sayaraforce.test',
            'staging.communications.whatsapp_outbound_enabled' => false,
            'staging.communications.sms_outbound_enabled' => false,
        ]);
    }

    public function test_production_and_unapproved_meta_assets_fail_closed(): void
    {
        $guard = app(StagingSafety::class);

        foreach ([['prod-waba', 'test-phone'], ['test-waba', 'prod-phone'], ['unknown', 'test-phone']] as [$waba, $phone]) {
            try {
                $guard->assertProviderAssetsAllowed($waba, $phone);
                $this->fail('An unsafe provider asset was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('Staging', $exception->getMessage());
                $this->assertStringNotContainsString($waba, $exception->getMessage());
                $this->assertStringNotContainsString($phone, $exception->getMessage());
            }
        }

        $guard->assertProviderAssetsAllowed('test-waba', 'test-phone');
        $this->addToAssertionCount(1);
    }

    public function test_ambiguous_environment_still_guards_outbound(): void
    {
        // A mislabeled staging box (APP_ENV set to an unrecognised value) must
        // never silently allow production-like outbound: the guard fails closed.
        $this->app->detectEnvironment(fn (): string => 'sandbox');
        config(['staging.safety_mode' => false]);
        $guard = app(StagingSafety::class);

        $this->assertTrue($guard->outboundGuardActive());

        // A production provider asset is rejected exactly as under staging.
        try {
            $guard->assertProviderAssetsAllowed('prod-waba', 'test-phone');
            $this->fail('Ambiguous environment allowed a production WABA.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Staging', $exception->getMessage());
        }

        // An unknown email recipient is refused (no implicit allow).
        $this->assertFalse($guard->emailRecipientsAreAllowed(['customer@example.com']));

        // Enabling outbound still refuses a non-allowlisted recipient.
        config(['staging.communications.whatsapp_outbound_enabled' => true]);
        $this->expectException(RuntimeException::class);
        $guard->assertWhatsAppOutboundAllowed('+971509999999', 'test-waba', 'test-phone');
    }

    public function test_app_env_drift_to_production_still_guards_when_safety_flag_set(): void
    {
        // APP_ENV silently drifts to a recognised value, but the explicit
        // STAGING_SAFETY_ENFORCED flag keeps every staging safeguard active so
        // the email allowlist and provider-asset guards cannot be dropped.
        $this->app->detectEnvironment(fn (): string => 'production');
        config([
            'staging.safety_mode' => true,
            'app.url' => 'http://localhost',
            'staging.expected_host' => 'staging.sayaraforce.com',
        ]);
        $guard = app(StagingSafety::class);

        $this->assertTrue($guard->outboundGuardActive());
        $this->assertFalse($guard->emailRecipientsAreAllowed(['customer@example.com']));

        $this->expectException(RuntimeException::class);
        $guard->assertProviderAssetsAllowed('prod-waba', 'test-phone');
    }

    public function test_app_env_drift_still_guards_when_staging_host_identity_matches(): void
    {
        // No safety flag, but APP_URL still points at the approved staging host,
        // so a mislabeled APP_ENV cannot open outbound.
        $this->app->detectEnvironment(fn (): string => 'production');
        config([
            'staging.safety_mode' => false,
            'app.url' => 'https://staging.sayaraforce.com',
            'staging.expected_host' => 'staging.sayaraforce.com',
        ]);
        $guard = app(StagingSafety::class);

        $this->assertTrue($guard->outboundGuardActive());
        $this->assertFalse($guard->emailRecipientsAreAllowed(['customer@example.com']));
    }

    public function test_recognised_production_environment_is_not_guarded(): void
    {
        // A genuine production box (recognised APP_ENV, no staging host/DB
        // identity, no safety flag) is left unguarded so production keeps sending.
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['staging.safety_mode' => false]);
        $guard = app(StagingSafety::class);

        $this->assertFalse($guard->outboundGuardActive());
        $guard->assertProviderAssetsAllowed('prod-waba', 'prod-phone'); // no-op
        $this->assertTrue($guard->emailRecipientsAreAllowed(['anyone@anywhere.example']));
        $this->addToAssertionCount(1);
    }

    public function test_safety_flag_keeps_guard_active_on_a_mislabeled_production_box(): void
    {
        // APP_ENV drifted to 'production' but the operator pinned the explicit
        // safety flag: outbound stays guarded (defence in depth).
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['staging.safety_mode' => true]);
        $guard = app(StagingSafety::class);

        $this->assertTrue($guard->outboundGuardActive());
        $this->expectException(RuntimeException::class);
        $guard->assertProviderAssetsAllowed('prod-waba', 'test-phone');
    }

    public function test_empty_required_denylist_is_reported_and_provider_assertion_fails_closed(): void
    {
        $guard = app(StagingSafety::class);

        // setUp populates the WABA/phone denylists but not the production DB-host
        // denylist, so readiness must surface it as not-ready under the guard.
        $readiness = $guard->denylistReadiness();
        $this->assertTrue($readiness['guard_active']);
        $this->assertFalse($readiness['ready']);
        $this->assertContains('production_database_hosts', $readiness['empty']);

        // Emptying the provider denylists must fail the provider-asset path
        // closed rather than silently completing, even with absent asset ids.
        config([
            'staging.production.waba_ids' => '',
            'staging.production.phone_number_ids' => '',
        ]);
        $this->assertEqualsCanonicalizing(
            ['production_database_hosts', 'production_waba_ids', 'production_phone_number_ids'],
            $guard->denylistReadiness()['empty']
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('required production denylist is empty');
        $guard->assertProviderAssetsAllowed(null, null);
    }

    public function test_outbound_whatsapp_and_sms_are_disabled_by_default(): void
    {
        $guard = app(StagingSafety::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('disabled');
        $guard->assertWhatsAppOutboundAllowed('+971500000001', 'test-waba', 'test-phone');
    }

    public function test_outbound_whatsapp_requires_test_assets_and_recipient_allowlist_when_enabled(): void
    {
        config(['staging.communications.whatsapp_outbound_enabled' => true]);
        $guard = app(StagingSafety::class);

        $guard->assertWhatsAppOutboundAllowed('+971500000001', 'test-waba', 'test-phone');
        $this->addToAssertionCount(1);

        $this->expectException(RuntimeException::class);
        $guard->assertWhatsAppOutboundAllowed('+971509999999', 'test-waba', 'test-phone');
    }

    public function test_email_allowlist_requires_every_recipient_to_be_approved(): void
    {
        $guard = app(StagingSafety::class);

        $this->assertTrue($guard->emailRecipientsAreAllowed(['tester@example.test']));
        $this->assertTrue($guard->emailRecipientsAreAllowed(['person@staging.sayaraforce.test']));
        $this->assertFalse($guard->emailRecipientsAreAllowed(['tester@example.test', 'customer@example.com']));
        $this->assertFalse($guard->emailRecipientsAreAllowed([]));
    }

    public function test_staging_identity_middleware_serves_robots_and_marks_html(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSeeText('Disallow: /');

        $middleware = app(ApplyStagingIdentity::class);
        $robots = $middleware->handle(Request::create('/robots.txt'), fn () => new Response('unexpected'));

        $this->assertSame("User-agent: *\nDisallow: /\n", $robots->getContent());
        $this->assertSame('noindex, nofollow', $robots->headers->get('X-Robots-Tag'));

        Auth::setUser(new User(['name' => 'Staging Tester']));
        $html = $middleware->handle(Request::create('/dashboard'), fn () => new Response(
            '<html><head><title>SayaraForce</title></head><body><main>Dashboard</main></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        ));

        $this->assertStringContainsString('STAGING ENVIRONMENT &#8212; TEST DATA ONLY', (string) $html->getContent());
        $this->assertStringContainsString('[STAGING] SayaraForce', (string) $html->getContent());
        $this->assertSame('noindex, nofollow', $html->headers->get('X-Robots-Tag'));
    }

    public function test_destructive_check_refuses_non_staging_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_ENV must be staging');
        app(StagingSafety::class)->assertRuntimeIsolated(destructive: true);
    }

    public function test_local_schema_validation_mode_requires_the_complete_disposable_database_contract(): void
    {
        config([
            'app.url' => 'http://localhost',
            'staging.expected_host' => 'localhost',
            'staging.expected_database' => 'sayaraforce_staging_validation',
            'staging.schema_baseline_approved' => true,
            'staging.schema_validation_mode' => true,
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.database' => 'sayaraforce_staging_validation',
        ]);

        app(StagingSafety::class)->assertRuntimeIsolated();
        $this->addToAssertionCount(1);

        config(['database.connections.mysql.host' => 'mysql-sayaraforce-staging.example.test']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('local validation safety contract is incomplete');
        app(StagingSafety::class)->assertRuntimeIsolated();
    }

    public function test_live_verifier_requires_seed_fixtures_without_rejecting_self_service_uat_tenants(): void
    {
        $source = (string) file_get_contents(app_path('Console/Commands/VerifyLiveStaging.php'));

        $this->assertStringContainsString('required synthetic tenant count', $source);
        $this->assertStringContainsString('required synthetic user count', $source);
        $this->assertStringContainsString('self_service_staging_tenants', $source);
        $this->assertStringNotContainsString('non-synthetic tenant count', $source);
        $this->assertStringNotContainsString('non-synthetic user count', $source);
    }

    public function test_live_verifier_accepts_only_reviewed_test_billing_providers(): void
    {
        $command = (string) file_get_contents(app_path('Console/Commands/VerifyLiveStaging.php'));
        $script = (string) file_get_contents(base_path('ops/azure/staging/verify-staging.ps1'));
        $postDeploy = (string) file_get_contents(base_path('ops/azure/staging/post-deploy.sh'));

        $this->assertStringContainsString("['fake', 'stripe']", $command);
        $this->assertStringContainsString("config('billing.mode') !== 'test'", $command);
        $this->assertStringContainsString('assertSandboxConfiguration()', $command);
        $this->assertStringContainsString('active Stripe Sandbox price mapping count', $command);
        $this->assertStringNotContainsString('must remain fake before Stripe Sandbox cutover approval', $command);

        $this->assertStringContainsString("-notin @('fake', 'stripe')", $script);
        $this->assertStringContainsString("STRIPE_API_BASE'] -ne 'https://api.stripe.com'", $script);
        $this->assertStringContainsString("STRIPE_API_VERSION'] -ne '2026-07-29.dahlia'", $script);
        $this->assertStringContainsString("@('STRIPE_SECRET_KEY', 'STRIPE_WEBHOOK_SECRET')", $script);

        $this->assertStringContainsString('case "${BILLING_PROVIDER:-}" in', $postDeploy);
        $this->assertStringContainsString('billing:reconcile-fake-test-history', $postDeploy);
        $this->assertStringContainsString('Fake billing-history reconciliation skipped', $postDeploy);
        $this->assertStringContainsString('BILLING_PROVIDER is not an approved staging provider', $postDeploy);
    }
}
