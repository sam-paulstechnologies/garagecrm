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
}
