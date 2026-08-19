<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\QuickScan\QuickScanSyntheticFixture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncryptedFieldRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'quick_scan.enabled' => true,
            'messaging.history.hmac_key' => 'encrypted-recovery-test-hmac',
        ]);
    }

    public function test_verifies_encrypted_fields_decrypt_after_restore(): void
    {
        // Seed representative encrypted PII (garage fields, message bodies, ...).
        $actor = User::factory()->create();
        app(QuickScanSyntheticFixture::class)->create($actor, 3);

        $this->artisan('staging:verify-encrypted-recovery', ['--sample' => 5])
            ->assertSuccessful();
    }

    public function test_refuses_to_run_against_a_production_database_host(): void
    {
        config([
            'database.connections.sqlite.host' => 'prod-db.internal',
            'staging.production.database_hosts' => 'prod-db.internal',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->artisan('staging:verify-encrypted-recovery');
    }
}
