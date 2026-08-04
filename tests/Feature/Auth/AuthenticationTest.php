<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_uses_same_origin_vite_assets_on_every_production_host(): void
    {
        config()->set('app.url', 'https://app.sayaraforce.com');
        Vite::useHotFile(storage_path('framework/testing-vite-hot'));

        foreach (['sayaraforce.com', 'app.sayaraforce.com'] as $host) {
            $response = $this
                ->withServerVariables([
                    'HTTP_HOST' => $host,
                    'HTTPS' => 'on',
                ])
                ->get('/login');

            $response->assertOk();

            $html = $response->getContent();

            $this->assertMatchesRegularExpression(
                '/(?:src|href)="\/build\/assets\/[^"?]+\.(?:css|js)"/',
                $html,
                "The {$host} login page should use same-origin Vite assets."
            );
            $this->assertStringNotContainsString(
                'https://app.sayaraforce.com/build/',
                $html,
                "The {$host} login page must not cross-load Vite modules from the app hostname."
            );
        }
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
