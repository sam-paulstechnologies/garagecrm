<?php

namespace Tests\Feature\Auth;

use App\Commercial\ProductEvents;
use App\Models\Commercial\Subscription;
use App\Models\Garage\Garage;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_public_registration_is_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('registration.public_enabled'));

        $this->get('/register')->assertNotFound();
        $this->post('/register', $this->payload())->assertNotFound();

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_registration_page_is_available_when_explicitly_enabled(): void
    {
        config()->set('registration.public_enabled', true);

        $this->get('/register')
            ->assertOk()
            ->assertSee('Garage self-service onboarding')
            ->assertSee('Create garage workspace');
        $this->assertDatabaseHas('product_events', ['event_type' => ProductEvents::REGISTRATION_STARTED]);
    }

    public function test_successful_registration_creates_one_tenant_garage_and_admin_then_opens_whatsapp_onboarding(): void
    {
        config()->set('registration.public_enabled', true);

        $response = $this->post('/register', $this->payload());

        $response->assertRedirect(route('admin.messaging.whatsapp.index'));
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('garages', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('messaging_connections', 0);
        $this->assertDatabaseCount('subscriptions', 1);

        $company = Company::query()->sole();
        $garage = Garage::query()->sole();
        $admin = User::query()->sole();
        $subscription = Subscription::query()->with('planVersion.plan')->sole();

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue((bool) $admin->status);
        $this->assertFalse((bool) $admin->must_change_password);
        $this->assertSame($company->id, $admin->company_id);
        $this->assertSame($garage->id, $admin->garage_id);
        $this->assertSame($company->id, $garage->company_id);
        $this->assertCount(1, $company->users);
        $this->assertSame($company->id, $subscription->company_id);
        $this->assertSame('free', $subscription->planVersion->plan->code);
        $this->assertSame('active', $subscription->status);
        $this->assertDatabaseHas('product_events', [
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'event_type' => ProductEvents::REGISTRATION_COMPLETED,
        ]);

        $this->get(route('admin.messaging.whatsapp.index'))
            ->assertOk()
            ->assertSee('Connect your garage WhatsApp');
    }

    public function test_duplicate_or_replayed_submission_does_not_create_another_tenant(): void
    {
        config()->set('registration.public_enabled', true);
        $payload = $this->payload();

        $this->post('/register', $payload)->assertRedirect(route('admin.messaging.whatsapp.index'));
        Auth::logout();

        $this->post('/register', $payload)->assertSessionHasErrors('email');

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('garages', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('messaging_connections', 0);
    }

    public function test_registered_admin_cannot_resolve_another_tenants_garage(): void
    {
        config()->set('registration.public_enabled', true);
        $this->post('/register', $this->payload());
        $admin = User::query()->sole();

        $otherCompany = Company::query()->create([
            'name' => 'Other Synthetic Garage',
            'email' => 'other-garage@staging.sayaraforce.test',
            'phone' => '+971500000999',
            'address' => 'Other synthetic address',
            'status' => 'active',
        ]);
        $otherGarage = Garage::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Synthetic Garage',
            'phone' => '+971500000999',
        ]);

        $this->actingAs($admin);

        $this->assertNull((new Garage)->resolveRouteBinding($otherGarage->id));
        $this->assertNotSame($otherCompany->id, $admin->company_id);
        $this->assertDatabaseCount('messaging_connections', 0);
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return [
            'garage_name' => 'Public Registration Test Garage',
            'name' => 'Synthetic Garage Administrator',
            'email' => 'public-registration@staging.sayaraforce.test',
            'phone' => '+971 50 000 0888',
            'address' => 'Synthetic Industrial Area, Dubai',
            'password' => 'Synthetic-Only-Password-2026!',
            'password_confirmation' => 'Synthetic-Only-Password-2026!',
            'terms' => '1',
        ];
    }
}
