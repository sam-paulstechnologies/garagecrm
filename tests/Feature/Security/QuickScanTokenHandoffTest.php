<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\QuickScanController;
use App\Models\User;
use App\QuickScan\QuickScanSyntheticFixture;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 34 — accepting a Quick Scan must NOT place the 64-char token in the
 * register URL (browser history / Referer / logs). It must hand the token off
 * through the server-side session and redirect to a clean register URL.
 *
 * This covers only the QuickScanController side. The registration side
 * (RegisteredUserController::create) must be updated to prefer the session
 * value; see the remediation report for the exact coordinated change.
 */
class QuickScanTokenHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
        config([
            'quick_scan.enabled' => true,
            'quick_scan.analysis_contact_limit' => 500,
            'messaging.history.hmac_key' => 'quick-scan-test-hmac',
            'services.openai.api_key' => null,
        ]);
    }

    public function test_accept_stores_token_in_session_and_redirects_to_a_clean_register_url(): void
    {
        $platform = User::query()->firstOrCreate(['email' => 'handoff-platform@example.test'], [
            'name' => 'Quick Scan Platform Owner', 'password' => 'Strong-Password-2026!',
            'role' => 'super_admin', 'status' => true, 'must_change_password' => false,
        ]);
        $created = app(QuickScanSyntheticFixture::class)->create($platform, 6);
        $token = $created['token'];
        $this->assertSame(64, strlen($token));

        $response = $this->post(route('quick-scan.accept', ['token' => $token]));

        // Redirects to the CLEAN register route with no token query param.
        $response->assertRedirect(route('register'));
        $location = (string) $response->headers->get('Location');
        $this->assertStringNotContainsString('quick_scan', $location);
        $this->assertStringNotContainsString($token, $location);

        // Token is handed off via the server-side session for single-use pickup.
        $response->assertSessionHas(QuickScanController::HANDOFF_SESSION_KEY, $token);

        // The scan itself was accepted (expiry/state still enforced by QuickScanAccess).
        $this->assertSame('accepted', $created['scan']->fresh()->status);
    }

    public function test_crafted_query_token_is_ignored_by_registration(): void
    {
        $platform = User::query()->firstOrCreate(['email' => 'handoff-crafted@example.test'], [
            'name' => 'Quick Scan Platform Owner', 'password' => 'Strong-Password-2026!',
            'role' => 'super_admin', 'status' => true, 'must_change_password' => false,
        ]);
        $created = app(QuickScanSyntheticFixture::class)->create($platform, 4);
        $created['scan']->forceFill(['status' => 'accepted', 'accepted_at' => now()])->save();
        $token = $created['token'];
        config(['registration.public_enabled' => true]);

        // A hand-crafted/bookmarked ?quick_scan=<token> link (the removed legacy
        // fallback) must NOT bind the scan — the token in the URL is ignored.
        $response = $this->get(route('register', ['quick_scan' => $token]));

        $response->assertOk();
        $response->assertViewHas('quickScan', null);
        $response->assertViewHas('quickScanToken', null);
    }
}
