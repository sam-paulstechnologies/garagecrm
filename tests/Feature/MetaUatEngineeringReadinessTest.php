<?php

namespace Tests\Feature;

use App\Messaging\MetaUatReadiness;
use App\Support\Staging\StagingSafety;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\TestCase;

class MetaUatEngineeringReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->detectEnvironment(fn (): string => 'staging');
        config([
            'app.url' => 'https://staging.sayaraforce.com',
            'staging.expected_host' => 'staging.sayaraforce.com',
            'messaging.providers.meta_whatsapp.webhook_callback_url' => 'https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp',
            'messaging.providers.meta_whatsapp.required_coexistence_webhook_fields' => ['history', 'smb_app_state_sync', 'smb_message_echoes'],
            'staging.meta.allow_legacy_company_resolution' => false,
            'staging.communications.whatsapp_outbound_enabled' => false,
            'staging.communications.sms_outbound_enabled' => false,
        ]);
    }

    public function test_readiness_report_never_exposes_meta_values(): void
    {
        config([
            'messaging.providers.meta_whatsapp.app_id' => 'synthetic-app-id-sensitive',
            'messaging.providers.meta_whatsapp.app_secret' => 'synthetic-app-secret-sensitive',
            'messaging.providers.meta_whatsapp.business_app_config_id' => 'synthetic-coexistence-sensitive',
            'messaging.providers.meta_whatsapp.cloud_api_config_id' => 'synthetic-cloud-sensitive',
            'messaging.providers.meta_whatsapp.webhook_verify_token' => 'synthetic-verify-sensitive',
            'messaging.providers.meta_whatsapp.waba_callback_override_enabled' => true,
            'staging.meta.allowed_waba_ids' => 'synthetic-waba-sensitive',
            'staging.meta.allowed_phone_number_ids' => 'synthetic-phone-sensitive',
            'staging.production.waba_ids' => 'synthetic-prod-waba-sensitive',
            'staging.production.phone_number_ids' => 'synthetic-prod-phone-sensitive',
            'staging.communications.allowed_phone_recipients' => '',
        ]);

        $report = app(MetaUatReadiness::class)->report();

        $this->assertTrue($report['engineering_ready']);
        $this->assertTrue($report['live_uat_configuration_ready']);
        $this->assertFalse($report['outbound_test_authorized']);
        $this->assertSame(
            'https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp',
            $report['endpoints']['webhook_callback']
        );
        $this->assertSame(
            'https://staging.sayaraforce.com/admin/messaging/whatsapp',
            $report['endpoints']['onboarding_screen']
        );
        $this->assertFalse($report['checks']['test_recipient_allowlist_configured']);

        Artisan::call('staging:meta-readiness', ['--json' => true]);
        $output = Artisan::output();
        foreach (['synthetic-app-id-sensitive', 'synthetic-app-secret-sensitive', 'synthetic-waba-sensitive'] as $secret) {
            $this->assertStringNotContainsString($secret, $output);
        }
    }

    public function test_missing_human_owned_configuration_is_reported_without_blocking_engineering(): void
    {
        config([
            'messaging.providers.meta_whatsapp.app_id' => null,
            'messaging.providers.meta_whatsapp.app_secret' => null,
            'messaging.providers.meta_whatsapp.business_app_config_id' => null,
            'messaging.providers.meta_whatsapp.cloud_api_config_id' => null,
            'messaging.providers.meta_whatsapp.webhook_verify_token' => null,
            'messaging.providers.meta_whatsapp.waba_callback_override_enabled' => false,
            'staging.meta.allowed_waba_ids' => '',
            'staging.meta.allowed_phone_number_ids' => '',
            'staging.production.waba_ids' => '',
            'staging.production.phone_number_ids' => '',
            'staging.communications.allowed_phone_recipients' => '',
        ]);

        $report = app(MetaUatReadiness::class)->report();

        $this->assertTrue($report['engineering_ready']);
        $this->assertFalse($report['live_uat_configuration_ready']);
        $this->assertFalse($report['checks']['meta_app_secret_configured']);
        $this->assertFalse($report['checks']['staging_waba_allowlist_configured']);
    }

    public function test_staging_assets_fail_closed_until_allowlists_and_denylists_are_complete(): void
    {
        config([
            'staging.production.waba_ids' => 'production-waba',
            'staging.production.phone_number_ids' => 'production-phone',
            'staging.meta.allowed_waba_ids' => 'test-waba',
            'staging.meta.allowed_phone_number_ids' => 'test-phone',
        ]);

        app(StagingSafety::class)->assertProviderAssetsAllowed('test-waba', 'test-phone');
        $this->addToAssertionCount(1);

        foreach ([
            ['production-waba', 'test-phone'],
            ['test-waba', 'production-phone'],
            ['unknown-waba', 'test-phone'],
        ] as [$waba, $phone]) {
            try {
                app(StagingSafety::class)->assertProviderAssetsAllowed($waba, $phone);
                $this->fail('An unsafe Meta asset was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('Staging', $exception->getMessage());
                $this->assertStringNotContainsString($waba, $exception->getMessage());
                $this->assertStringNotContainsString($phone, $exception->getMessage());
            }
        }
    }
}
