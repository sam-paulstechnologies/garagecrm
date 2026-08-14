<?php

namespace Tests\Unit;

use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\WhatsApp\MetaApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWabaCallbackOverrideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://staging.sayaraforce.com',
            'messaging.providers.meta_whatsapp.app_id' => 'synthetic-app-id',
            'messaging.providers.meta_whatsapp.waba_callback_override_enabled' => true,
            'messaging.providers.meta_whatsapp.webhook_callback_url' => 'https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp',
            'messaging.providers.meta_whatsapp.webhook_verify_token' => 'synthetic-verify-token',
        ]);
    }

    public function test_subscription_uses_the_staging_specific_waba_callback_override(): void
    {
        Http::fake(fn () => Http::response(['success' => true]));

        app(MetaApiClient::class)->subscribeWaba('synthetic-waba', 'synthetic-access-token');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && str_ends_with($request->url(), '/synthetic-waba/subscribed_apps')
                && $request['override_callback_uri'] === 'https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp'
                && $request['verify_token'] === 'synthetic-verify-token';
        });
    }

    public function test_subscription_readback_requires_the_same_callback_for_this_app(): void
    {
        $client = app(MetaApiClient::class);

        $this->assertTrue($client->appIsSubscribed(['data' => [[
            'override_callback_uri' => 'https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp',
            'whatsapp_business_api_data' => ['id' => 'synthetic-app-id'],
        ]]]));
        $this->assertFalse($client->appIsSubscribed(['data' => [[
            'override_callback_uri' => 'https://sayaraforce.com/api/v1/webhooks/meta/whatsapp',
            'whatsapp_business_api_data' => ['id' => 'synthetic-app-id'],
        ]]]));
        $this->assertFalse($client->appIsSubscribed(['data' => [[
            'whatsapp_business_api_data' => ['id' => 'synthetic-app-id'],
        ]]]));
    }

    public function test_unsafe_or_missing_override_configuration_fails_before_any_meta_request(): void
    {
        Http::fake();
        config([
            'messaging.providers.meta_whatsapp.webhook_callback_url' => 'https://sayaraforce.com/api/v1/webhooks/meta/whatsapp',
            'messaging.providers.meta_whatsapp.webhook_verify_token' => null,
        ]);

        try {
            app(MetaApiClient::class)->subscribeWaba('synthetic-waba', 'synthetic-access-token');
            $this->fail('Unsafe callback configuration was accepted.');
        } catch (MessagingProvisioningException $exception) {
            $this->assertSame('unsafe_webhook_callback_override', $exception->reason);
            $this->assertStringNotContainsString('sayaraforce.com', $exception->getMessage());
            $this->assertStringNotContainsString('synthetic', $exception->getMessage());
        }

        Http::assertNothingSent();
    }
}
