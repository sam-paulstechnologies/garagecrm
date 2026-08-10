<?php

namespace Tests\Feature;

use App\Jobs\DeliverNotificationIntent;
use App\Models\Notifications\NotificationIntent;
use App\Models\Notifications\PushDevice;
use App\Models\System\Company;
use App\Models\User;
use App\Notifications\NotificationIntentService;
use App\Notifications\NotificationTypes;
use App\Notifications\Push\PushProviderManager;
use App\Notifications\PushDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithCommercialPlans;
use Tests\TestCase;

class MobileNotificationFoundationTest extends TestCase
{
    use InteractsWithCommercialPlans, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('n', 32)),
            'mobile_notifications.driver' => 'fake',
            'mobile_notifications.external_delivery_enabled' => false,
        ]);
    }

    public function test_supported_notification_domain_is_explicit_and_stable(): void
    {
        $this->assertSame([
            'new_enquiry', 'high_intent_lead', 'follow_up_due', 'booking_upcoming',
            'missed_follow_up', 'next_service_due', 'billing_attention',
        ], NotificationTypes::ALL);
    }

    public function test_device_registration_is_tenant_scoped_encrypted_and_idempotent(): void
    {
        [$company, $user] = $this->tenant('Push Device Garage', 'admin');
        Sanctum::actingAs($user);

        $payload = [
            'provider' => 'fake',
            'token' => 'synthetic-device-token-never-returned',
            'platform' => 'web',
            'device_name' => 'Synthetic phone',
        ];
        $this->postJson(route('api.push-devices.store'), $payload)->assertCreated()->assertJsonMissing(['token']);
        $this->postJson(route('api.push-devices.store'), $payload)->assertCreated();

        $this->assertDatabaseCount('push_devices', 1);
        $device = PushDevice::query()->firstOrFail();
        $this->assertSame($company->id, $device->company_id);
        $this->assertSame($user->id, $device->user_id);
        $this->assertNotSame($payload['token'], $device->getRawOriginal('encrypted_token'));
        $this->assertSame($payload['token'], Crypt::decryptString($device->encrypted_token));
    }

    public function test_user_cannot_revoke_another_tenants_device(): void
    {
        [, $first] = $this->tenant('First Device Garage', 'admin');
        [, $second] = $this->tenant('Second Device Garage', 'admin');
        $device = app(PushDeviceService::class)->register($first, 'fake', 'first-tenant-token', 'web', null);

        Sanctum::actingAs($second);
        $this->deleteJson(route('api.push-devices.destroy', $device))->assertNotFound();
        $this->assertSame('active', $device->fresh()->status);
    }

    public function test_intent_is_idempotent_and_fake_provider_delivers_without_external_network(): void
    {
        [$company, $user] = $this->tenant('Notification Delivery Garage', 'manager');
        app(PushDeviceService::class)->register($user, 'fake', 'delivery-device-token', 'web', null);
        $service = app(NotificationIntentService::class);

        $first = $service->record(
            $company, $user, 'new_enquiry', 'New enquiry', 'A synthetic enquiry needs attention.',
            'new-enquiry:synthetic-100', '/manager/inbox', ['lead_id' => 100], dispatch: false,
        );
        $second = $service->record(
            $company, $user, 'new_enquiry', 'Duplicate', 'Duplicate',
            'new-enquiry:synthetic-100', dispatch: false,
        );
        $this->assertSame($first->id, $second->id);

        (new DeliverNotificationIntent($first->id))->handle(
            app(\App\Commercial\CompanyAccessService::class),
            app(PushProviderManager::class),
        );

        $this->assertDatabaseHas('notification_intents', [
            'id' => $first->id,
            'company_id' => $company->id,
            'state' => 'delivered',
            'failure_reason' => null,
        ]);
    }

    public function test_disabled_company_suppresses_background_delivery(): void
    {
        [$company, $user] = $this->tenant('Suspended Notification Garage', 'manager');
        app(PushDeviceService::class)->register($user, 'fake', 'suspended-device-token', 'web', null);
        $intent = app(NotificationIntentService::class)->record(
            $company, $user, 'follow_up_due', 'Follow-up due', 'Synthetic follow-up.',
            'follow-up:synthetic-200', dispatch: false,
        );
        $company->update(['status' => 'suspended', 'suspended_at' => now()]);

        (new DeliverNotificationIntent($intent->id))->handle(
            app(\App\Commercial\CompanyAccessService::class),
            app(PushProviderManager::class),
        );

        $this->assertSame('suppressed', $intent->fresh()->state);
        $this->assertSame('tenant_not_operational', $intent->fresh()->failure_reason);
    }

    public function test_notification_center_is_user_and_tenant_scoped_and_mobile_responsive(): void
    {
        [$company, $manager] = $this->tenant('Notification Center Garage', 'manager');
        [$otherCompany, $other] = $this->tenant('Other Notification Garage', 'manager');
        app(NotificationIntentService::class)->record(
            $company, $manager, 'booking_upcoming', 'Your synthetic booking', 'Open booking details.',
            'booking:tenant-one', dispatch: false,
        );
        app(NotificationIntentService::class)->record(
            $otherCompany, $other, 'booking_upcoming', 'Other tenant private booking', 'Must not render.',
            'booking:tenant-two', dispatch: false,
        );

        $this->actingAs($manager)->get(route('manager.notifications.index'))
            ->assertOk()
            ->assertSee('Your synthetic booking')
            ->assertDontSee('Other tenant private booking')
            ->assertSee('@media(max-width:640px)', false)
            ->assertSee('min-height:44px', false);
    }

    /** @return array{Company, User} */
    private function tenant(string $name, string $role): array
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        $this->assignCanonicalPlan($company, 'free');
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => str($role)->headline().' User',
            'email' => str($name)->slug().'-'.$role.'@example.test',
            'password' => 'Strong-Password-2026!',
            'role' => $role,
            'status' => true,
            'must_change_password' => false,
        ]);

        return [$company->fresh(), $user];
    }
}
