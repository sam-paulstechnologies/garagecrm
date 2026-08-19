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

    public function test_ingestion_stamps_canonical_retention_deadline(): void
    {
        $scan = $this->makeScan();

        // Customer history has been ingested, so a deterministic deadline must
        // exist even though the scan never reached report_ready.
        $this->assertNotNull($scan->customer_data_expires_at);
        $this->assertGreaterThan(0, $scan->messages()->count());
    }

    public function test_stalled_failed_scan_past_deadline_is_physically_purged(): void
    {
        // Reproduces the M2 gap: customer history ingested, analysis fails, no
        // report generated, original purge job absent — both report_expires_at
        // and purge_scheduled_at are NULL.
        $scan = $this->makeScan();
        $this->assertGreaterThan(0, $scan->messages()->count());

        $scan->forceFill([
            'status' => 'failed',
            'failure_code' => 'meta_connection_failed',
            'report_expires_at' => null,
            'purge_scheduled_at' => null,
            'customer_data_expires_at' => now()->subHour(),
        ])->save();

        $result = app(QuickScanRetentionEnforcer::class)->enforce();

        $this->assertSame(1, $result['stalled']);
        $this->assertGreaterThanOrEqual(1, $result['purged']);

        $scan->refresh();
        $this->assertSame('purged', $scan->status);
        $this->assertSame(0, $scan->messages()->count());
        $this->assertSame(0, $scan->candidates()->count());
        $this->assertNotNull($scan->purged_at);
    }

    public function test_stalled_scan_within_deadline_is_preserved(): void
    {
        $scan = $this->makeScan();
        $scan->forceFill([
            'status' => 'history_syncing',
            'report_expires_at' => null,
            'purge_scheduled_at' => null,
            'customer_data_expires_at' => now()->addHours(48),
        ])->save();

        $result = app(QuickScanRetentionEnforcer::class)->enforce();

        $this->assertSame(0, $result['stalled']);
        $this->assertSame('history_syncing', $scan->refresh()->status);
        $this->assertGreaterThan(0, $scan->messages()->count());
    }

    public function test_orphaned_legacy_scan_with_null_deadline_is_purged(): void
    {
        // Legacy/edge row that holds customer data but never received a
        // canonical deadline; bounded by created_at so only stale rows sweep.
        $scan = $this->makeScan();
        $scan->forceFill([
            'status' => 'analysing',
            'report_expires_at' => null,
            'purge_scheduled_at' => null,
            'customer_data_expires_at' => null,
            'created_at' => now()->subDays(30),
        ])->save();

        $result = app(QuickScanRetentionEnforcer::class)->enforce();

        $this->assertSame(1, $result['orphaned']);
        $this->assertSame('purged', $scan->refresh()->status);
        $this->assertSame(0, $scan->messages()->count());
    }

    public function test_converted_scan_is_never_swept(): void
    {
        $scan = $this->makeScan();
        $messageCount = $scan->messages()->count();
        $company = \App\Models\System\Company::query()->create(['name' => 'Converted Garage', 'status' => 'active']);

        // A converted scan handed its data to tenant quarantine; even with an
        // expired deadline it must never be swept.
        $scan->forceFill([
            'status' => 'accepted',
            'converted_company_id' => $company->id,
            'report_expires_at' => null,
            'purge_scheduled_at' => null,
            'customer_data_expires_at' => now()->subDays(10),
        ])->save();

        $result = app(QuickScanRetentionEnforcer::class)->enforce();

        $this->assertSame(0, $result['stalled']);
        $this->assertSame(0, $result['orphaned']);
        $this->assertSame($messageCount, $scan->refresh()->messages()->count());
    }
}
