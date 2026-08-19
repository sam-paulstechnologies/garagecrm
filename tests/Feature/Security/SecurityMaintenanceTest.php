<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\QuickScan\QuickScanSyntheticFixture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SecurityMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'quick_scan.enabled' => true,
            'quick_scan.customer_data_retention_hours' => 96,
            'messaging.history.hmac_key' => 'security-maintenance-test-hmac',
        ]);
    }

    public function test_security_maintenance_runs_retention_and_sends_no_outbound(): void
    {
        Mail::fake();
        Notification::fake();
        Queue::fake();

        // A stalled scan holding customer PII past its canonical deadline.
        $actor = User::factory()->create();
        $scan = app(QuickScanSyntheticFixture::class)->create($actor, 3)['scan']->fresh();
        $scan->forceFill([
            'status' => 'failed',
            'report_expires_at' => null,
            'purge_scheduled_at' => null,
            'customer_data_expires_at' => now()->subHour(),
        ])->save();
        $this->assertGreaterThan(0, $scan->messages()->count());

        $this->artisan('security:run-maintenance')->assertSuccessful();

        // Retention actually executed: the stalled scan's customer data is gone.
        $this->assertSame('purged', $scan->refresh()->status);
        $this->assertSame(0, $scan->messages()->count());

        // Outbound count is exactly zero: no mail, notifications, or queued jobs.
        Mail::assertNothingSent();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    public function test_security_maintenance_is_idempotent(): void
    {
        Mail::fake();
        Notification::fake();

        $this->artisan('security:run-maintenance')->assertSuccessful();
        $this->artisan('security:run-maintenance')->assertSuccessful();

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }
}
