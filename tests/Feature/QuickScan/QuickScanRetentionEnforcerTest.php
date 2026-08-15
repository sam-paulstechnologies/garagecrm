<?php

namespace Tests\Feature\QuickScan;

use App\Models\QuickScan\QuickScanWorkspace;
use App\Models\User;
use App\QuickScan\QuickScanRetentionEnforcer;
use App\QuickScan\QuickScanSyntheticFixture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickScanRetentionEnforcerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['quick_scan.enabled' => true, 'quick_scan.abandoned_purge_grace_hours' => 24]);
    }

    private function makeScan(int $contacts = 3): QuickScanWorkspace
    {
        $actor = User::factory()->create();

        return app(QuickScanSyntheticFixture::class)->create($actor, $contacts)['scan']->fresh();
    }

    public function test_abandoned_expired_scan_is_physically_purged(): void
    {
        $scan = $this->makeScan();
        $this->assertGreaterThan(0, $scan->messages()->count());

        $scan->forceFill([
            'status' => 'report_ready',
            'report_expires_at' => now()->subDays(2),
            'purge_scheduled_at' => null,
        ])->save();

        $result = app(QuickScanRetentionEnforcer::class)->enforce();

        $this->assertSame(1, $result['abandoned']);
        $this->assertGreaterThanOrEqual(1, $result['purged']);

        $scan->refresh();
        $this->assertSame('purged', $scan->status);
        $this->assertSame(0, $scan->messages()->count());
        $this->assertSame(0, $scan->candidates()->count());
    }

    public function test_due_but_unpurged_scan_is_purged(): void
    {
        $scan = $this->makeScan();
        $scan->forceFill(['status' => 'declined', 'purge_scheduled_at' => now()->subHour()])->save();

        app(QuickScanRetentionEnforcer::class)->enforce();

        $this->assertSame('purged', $scan->refresh()->status);
        $this->assertSame(0, $scan->messages()->count());
    }

    public function test_accepted_scan_is_preserved(): void
    {
        $scan = $this->makeScan();
        $messageCount = $scan->messages()->count();

        $scan->forceFill([
            'status' => 'accepted',
            'report_expires_at' => now()->subDays(5),
            'purge_scheduled_at' => null,
        ])->save();

        app(QuickScanRetentionEnforcer::class)->enforce();

        $scan->refresh();
        $this->assertSame('accepted', $scan->status);
        $this->assertSame($messageCount, $scan->messages()->count());
    }

    public function test_fresh_scan_within_retention_is_not_purged(): void
    {
        $scan = $this->makeScan();
        $scan->forceFill([
            'status' => 'report_ready',
            'report_expires_at' => now()->addDays(2),
            'purge_scheduled_at' => null,
        ])->save();

        $result = app(QuickScanRetentionEnforcer::class)->enforce();

        $this->assertSame(0, $result['abandoned']);
        $this->assertSame('report_ready', $scan->refresh()->status);
        $this->assertGreaterThan(0, $scan->messages()->count());
    }

    public function test_dry_run_reports_without_purging(): void
    {
        $scan = $this->makeScan();
        $scan->forceFill([
            'status' => 'report_ready',
            'report_expires_at' => now()->subDays(2),
            'purge_scheduled_at' => null,
        ])->save();

        $result = app(QuickScanRetentionEnforcer::class)->enforce(dryRun: true);

        $this->assertSame(1, $result['abandoned']);
        $this->assertSame(0, $result['purged']);
        $this->assertSame('report_ready', $scan->refresh()->status);
        $this->assertGreaterThan(0, $scan->messages()->count());
    }
}
