<?php

namespace Tests\Feature;

use App\Http\Middleware\ForcePasswordChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StagingSchemaBaselineTest extends TestCase
{
    public function test_canonical_baseline_is_structure_only_and_matches_its_manifest(): void
    {
        $schemaPath = database_path('schema/mysql-schema.sql');
        $manifestPath = database_path('schema/mysql-schema.manifest.json');

        $this->assertFileExists($schemaPath);
        $this->assertFileExists($manifestPath);

        $sql = (string) file_get_contents($schemaPath);
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(103, substr_count($sql, 'CREATE TABLE `'));
        $this->assertSame(2, substr_count($sql, 'SQL SECURITY INVOKER VIEW `'));
        $this->assertStringNotContainsString('DEFINER=', strtoupper($sql));
        $this->assertDoesNotMatchRegularExpression('/AUTO_INCREMENT=\d+/i', $sql);
        $this->assertDoesNotMatchRegularExpression('/\b(?:REPLACE|LOAD\s+DATA|COPY)\b/i', $sql);

        preg_match_all('/^INSERT\s+INTO\s+`?([^`\s(]+)`?/mi', $sql, $dataStatements);
        $this->assertSame(['migrations'], array_values(array_unique(array_map('strtolower', $dataStatements[1]))));
        $this->assertSame(103, $manifest['included_base_table_count']);
        $this->assertCount(103, $manifest['included_base_tables']);
        $this->assertSame(2, $manifest['included_view_count']);
        $this->assertSame('2026_08_05_000001_create_messaging_core_tables', $manifest['first_pending_migration']);
        $this->assertSame(hash_file('sha256', $schemaPath), $manifest['baseline_sha256']);

        $safety = json_decode((string) file_get_contents(database_path('schema/mysql-schema.safety.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('passed', $safety['status']);
        $this->assertSame($manifest['baseline_sha256'], $safety['baseline_sha256']);
    }

    public function test_disposable_mysql_install_supports_representative_application_surfaces(): void
    {
        if (! filter_var(env('STAGING_SCHEMA_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Set STAGING_SCHEMA_INTEGRATION=true only for the guarded disposable MySQL database.');
        }

        $database = (string) DB::connection()->getDatabaseName();
        $host = strtolower((string) config('database.connections.mysql.host'));

        $this->assertSame('mysql', DB::getDefaultConnection());
        $this->assertContains($host, ['127.0.0.1', 'localhost', '::1']);
        $this->assertStringContainsString('staging_validation', strtolower($database));
        $this->assertTrue((bool) config('staging.schema_baseline_approved'));

        foreach ([
            'jobs',
            'queue_jobs',
            'failed_jobs',
            'messaging_connections',
            'messaging_phone_numbers',
            'messaging_onboarding_sessions',
            'messaging_consents',
            'messaging_connection_checks',
            'messaging_audit_logs',
            'messaging_webhook_events',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing canonical table: {$table}");
        }

        $views = DB::select("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
        $this->assertSame(['vw_ai_metrics_daily', 'vw_journey_summary'], array_map(fn ($view) => $view->TABLE_NAME, $views));
        $journeyView = DB::table('vw_journey_summary')->where('journey_name', 'Synthetic View Validation Journey')->first();
        $this->assertNotNull($journeyView);
        $this->assertSame(1, (int) $journeyView->total_closed_won);

        $this->withoutMiddleware(ForcePasswordChange::class);

        foreach (['login', 'register'] as $routeName) {
            $this->assertSurfaceDoesNotFail($routeName);
        }

        $admin = User::query()->where('role', 'admin')->whereNotNull('company_id')->firstOrFail();
        $this->actingAs($admin);

        foreach ([
            'admin.dashboard',
            'admin.clients.index',
            'admin.leads.index',
            'admin.opportunities.index',
            'admin.bookings.index',
            'admin.jobs.index',
            'admin.invoices.index',
            'admin.inbox.index',
            'admin.reports.garage-summary',
            'admin.calendar.index',
            'admin.settings.index',
            'admin.messaging.whatsapp.index',
        ] as $routeName) {
            $this->assertSurfaceDoesNotFail($routeName);
        }

        $manager = User::query()->where('role', 'manager')->whereNotNull('company_id')->firstOrFail();
        $this->actingAs($manager);
        $this->assertSurfaceDoesNotFail('manager.growth.index');

        $platformAdmin = User::query()->where('role', 'super_admin')->firstOrFail();
        $this->actingAs($platformAdmin);
        $this->assertSurfaceDoesNotFail('super-admin.messaging-connections.index');
    }

    private function assertSurfaceDoesNotFail(string $routeName): void
    {
        $response = $this->get(route($routeName));

        $this->assertLessThan(
            500,
            $response->getStatusCode(),
            "Application surface {$routeName} returned a server error."
        );
    }
}
