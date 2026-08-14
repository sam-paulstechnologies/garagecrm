<?php

namespace App\Console\Commands;

use App\Billing\Gateways\StripeBillingGateway;
use App\Support\Staging\StagingSafety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class VerifyLiveStaging extends Command
{
    protected $signature = 'staging:verify-live {--json}';

    protected $description = 'Verify the live staging schema, synthetic data, and provider isolation';

    public function handle(StagingSafety $safety): int
    {
        try {
            $safety->assertRuntimeIsolated();
            $database = (string) DB::connection()->getDatabaseName();
            $baseTables = (int) DB::table('information_schema.tables')
                ->where('table_schema', $database)->where('table_type', 'BASE TABLE')->count();
            $views = (int) DB::table('information_schema.tables')
                ->where('table_schema', $database)->where('table_type', 'VIEW')->count();

            $this->assertSame(141, $baseTables, 'base-table count');
            $this->assertSame(2, $views, 'view count');

            $messagingTables = [
                'messaging_connections', 'messaging_phone_numbers', 'messaging_onboarding_sessions',
                'messaging_consents', 'messaging_connection_checks', 'messaging_audit_logs',
                'messaging_webhook_events',
            ];
            foreach ($messagingTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing messaging table: {$table}.");
                }
            }
            if (! Schema::hasTable('messaging_number_claims')) {
                throw new RuntimeException('Missing messaging number-claim table.');
            }

            $commercialTables = [
                'plan_versions', 'prices', 'plan_entitlements', 'subscriptions',
                'company_entitlement_overrides', 'entitlement_usages',
                'entitlement_audit_logs', 'billing_provider_events',
                'commercial_settings',
            ];
            foreach ($commercialTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing commercial foundation table: {$table}.");
                }
            }

            $aiMeteringTables = ['ai_customer_usages', 'ai_analysis_runs'];
            foreach ($aiMeteringTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing AI metering table: {$table}.");
                }
            }

            $billingTables = ['price_provider_mappings', 'billing_checkout_sessions', 'billing_invoices'];
            foreach ($billingTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing billing engine table: {$table}.");
                }
            }
            $notificationTables = ['push_devices', 'notification_intents'];
            foreach ($notificationTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing mobile notification table: {$table}.");
                }
            }
            if (! Schema::hasTable('product_events')) {
                throw new RuntimeException('Missing commercial product-event table.');
            }
            if (! Schema::hasTable('security_audit_logs')) {
                throw new RuntimeException('Missing security audit table.');
            }
            $historyIntelligenceTables = [
                'whatsapp_history_import_batches', 'whatsapp_history_candidates',
                'whatsapp_history_contact_usages', 'whatsapp_tracking_preferences',
                'whatsapp_history_audit_logs', 'entitlement_usage_events',
            ];
            foreach ($historyIntelligenceTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing history intelligence table: {$table}.");
                }
            }
            $quickScanTables = [
                'quick_scan_workspaces', 'quick_scan_provider_sessions', 'quick_scan_candidates',
                'quick_scan_messages', 'quick_scan_provider_events', 'quick_scan_events',
            ];
            foreach ($quickScanTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing Quick Scan table: {$table}.");
                }
            }
            if (! config('quick_scan.enabled')) {
                throw new RuntimeException('Quick Scan must be explicitly enabled in staging.');
            }
            $this->assertSame(500, (int) config('quick_scan.analysis_contact_limit'), 'Quick Scan internal analysis limit');
            foreach ([
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'two_factor_recovery_codes_acknowledged_at', 'two_factor_reenrollment_required_at',
            ] as $column) {
                if (! Schema::hasColumn('users', $column)) {
                    throw new RuntimeException("Missing two-factor user column: {$column}.");
                }
            }

            $twoFactorEnforcement = (string) config('security.two_factor_enforcement');
            if (! in_array($twoFactorEnforcement, ['off', 'audit', 'required_admins'], true)) {
                throw new RuntimeException('Two-factor enforcement mode is invalid.');
            }
            $this->assertSame(15, (int) config('security.step_up_window_minutes'), 'security step-up window');
            $this->assertSame(10, (int) DB::table('price_provider_mappings')
                ->where('payment_provider', 'fake')->where('status', 'active')->count(), 'fake billing price mapping count');
            $stripeMappingCount = $this->assertStripeMappingsAreAbsentOrCanonical();
            $this->assertSame(1, (int) DB::table('commercial_settings')
                ->where('key', 'launch_offer_enabled')->where('boolean_value', true)->count(), 'enabled launch-offer setting count');
            $this->assertSame(5, (int) DB::table('prices')
                ->where('currency', 'AED')->where('interval', 'month')
                ->where('promotion_duration_months', 3)->count(), 'three-cycle canonical price count');
            $billingProvider = (string) config('billing.provider');
            if (! in_array($billingProvider, ['fake', 'stripe'], true)) {
                throw new RuntimeException('Staging billing provider must be an approved test provider.');
            }
            if ((string) config('billing.mode') !== 'test') {
                throw new RuntimeException('Staging billing mode must remain test.');
            }
            if ((string) config('billing.stripe.api_version') !== StripeBillingGateway::SUPPORTED_API_VERSION) {
                throw new RuntimeException('Stripe webhook/API fixture version is not the reviewed Dahlia contract.');
            }
            if ($billingProvider === 'stripe') {
                app(StripeBillingGateway::class)->assertSandboxConfiguration();
                $this->assertSame(8, $stripeMappingCount, 'active Stripe Sandbox price mapping count');
            }
            $this->assertSame(0, (int) DB::table('subscriptions as s')
                ->where('s.payment_provider', 'fake')
                ->where('s.payment_status', 'paid')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('billing_invoices as bi')
                        ->whereColumn('bi.subscription_id', 's.id')
                        ->where('bi.payment_provider', 'fake')
                        ->where('bi.status', 'paid');
                })->count(), 'paid fake subscription without paid invoice count');

            foreach (['company_id', 'booking_id', 'client_id', 'description', 'status'] as $column) {
                if (! Schema::hasColumn('jobs', $column)) {
                    throw new RuntimeException("Operational jobs column is missing: {$column}.");
                }
            }
            foreach (['queue', 'payload', 'attempts', 'available_at'] as $column) {
                if (! Schema::hasColumn('queue_jobs', $column)) {
                    throw new RuntimeException("Queue storage column is missing: {$column}.");
                }
            }

            $tenantCount = (int) DB::table('companies')->count();
            $userCount = (int) DB::table('users')->count();
            $this->assertAtLeast(2, $tenantCount, 'synthetic tenant count');
            $this->assertAtLeast(4, $userCount, 'synthetic user count');
            $this->assertSame(2, (int) DB::table('companies')
                ->whereIn('email', ['tenant-a@staging.sayaraforce.test', 'tenant-b@staging.sayaraforce.test'])
                ->count(), 'required synthetic tenant count');
            $this->assertAtLeast(4, (int) DB::table('users')
                ->where('email', 'like', '%@staging.sayaraforce.test')
                ->count(), 'required synthetic user count');
            $this->assertSame(5, (int) DB::table('plans')
                ->whereIn('code', ['free', 'service', 'growth', 'performance', 'ai_pro'])->count(), 'canonical plan count');
            $expectedLimits = [
                'free' => [1, 1, 1, 25, 25, 0, 0, 0],
                'service' => [3, 1, 1, 300, 300, 1, 100, 0],
                'growth' => [10, 2, 2, 1000, 1000, 5, 500, 5],
                'performance' => [20, 5, 3, 2500, 2500, 20, 2000, 20],
                'ai_pro' => [null, null, null, null, null, null, null, null],
            ];
            $limitCapabilities = [
                'limit.users', 'limit.locations', 'limit.whatsapp_numbers',
                'limit.ai_monitored_customers', 'limit.whatsapp_history_contacts',
                'limit.campaigns_per_period', 'limit.campaign_recipients', 'limit.active_workflows',
            ];
            foreach ($expectedLimits as $planCode => $limits) {
                foreach ($limitCapabilities as $index => $capability) {
                    $entitlement = DB::table('plan_entitlements as pe')
                        ->join('plan_versions as pv', 'pv.id', '=', 'pe.plan_version_id')
                        ->join('plans as p', 'p.id', '=', 'pv.plan_id')
                        ->where('pv.code', $planCode.':'.config('commercial.catalogue_version'))
                        ->where('p.code', $planCode)
                        ->where('pe.capability', $capability)
                        ->first(['pe.enabled', 'pe.allowance']);
                    if (! $entitlement || ! $entitlement->enabled
                        || ($limits[$index] === null && $entitlement->allowance !== null)
                        || ($limits[$index] !== null && (int) $entitlement->allowance !== $limits[$index])) {
                        throw new RuntimeException("Unexpected {$planCode} {$capability} allowance.");
                    }
                }
            }
            $this->assertSame($tenantCount, (int) DB::table('subscriptions')->count(), 'explicit subscription count');
            $this->assertSame(1, (int) DB::table('subscriptions as s')
                ->join('companies as c', 'c.id', '=', 's.company_id')
                ->join('plan_versions as pv', 'pv.id', '=', 's.plan_version_id')
                ->join('plans as p', 'p.id', '=', 'pv.plan_id')
                ->where('c.email', 'tenant-a@staging.sayaraforce.test')->where('p.code', 'ai_pro')->count(), 'primary synthetic plan mapping');
            $this->assertSame(1, (int) DB::table('subscriptions as s')
                ->join('companies as c', 'c.id', '=', 's.company_id')
                ->join('plan_versions as pv', 'pv.id', '=', 's.plan_version_id')
                ->join('plans as p', 'p.id', '=', 'pv.plan_id')
                ->where('c.email', 'tenant-b@staging.sayaraforce.test')->where('p.code', 'service')->count(), 'secondary synthetic plan mapping');
            foreach (['clients', 'leads', 'vehicles', 'conversations'] as $table) {
                $this->assertSame(2, (int) DB::table($table)->count(), "{$table} synthetic row count");
            }

            $this->assertSame(0, (int) DB::table('companies')
                ->whereNotNull('meta_access_token')->orWhereNotNull('meta_waba_id')->orWhereNotNull('meta_phone_number_id')
                ->count(), 'legacy provider credential count');
            foreach (array_merge($messagingTables, [
                'whatsapp_connect_sessions', 'whatsapp_connection_audits', 'whatsapp_history_messages',
                'whatsapp_messages', 'whatsapp_synced_contacts', 'whatsapp_webhook_events',
            ]) as $table) {
                $this->assertSame(0, (int) DB::table($table)->count(), "{$table} provider row count");
            }

            DB::select('SELECT * FROM vw_ai_metrics_daily LIMIT 1');
            DB::select('SELECT * FROM vw_journey_summary LIMIT 1');

            $result = [
                'status' => 'passed',
                'environment' => 'staging',
                'base_tables' => $baseTables,
                'views' => $views,
                'messaging_tables' => count($messagingTables),
                'messaging_number_claim_tables' => 1,
                'commercial_tables' => count($commercialTables),
                'ai_metering_tables' => count($aiMeteringTables),
                'billing_tables' => count($billingTables),
                'test_billing_invoices' => (int) DB::table('billing_invoices')->where('test_mode', true)->count(),
                'stripe_price_mappings' => $stripeMappingCount,
                'stripe_api_version' => (string) config('billing.stripe.api_version'),
                'billing_provider' => $billingProvider,
                'billing_mode' => (string) config('billing.mode'),
                'billing_checkout_enabled' => (bool) config('billing.checkout_enabled'),
                'notification_tables' => count($notificationTables),
                'product_event_tables' => 1,
                'security_audit_tables' => 1,
                'history_intelligence_tables' => count($historyIntelligenceTables),
                'quick_scan_tables' => count($quickScanTables),
                'quick_scan_enabled' => (bool) config('quick_scan.enabled'),
                'quick_scan_analysis_contact_limit' => (int) config('quick_scan.analysis_contact_limit'),
                'two_factor_enforcement' => $twoFactorEnforcement,
                'synthetic_tenants' => $tenantCount,
                'synthetic_users' => $userCount,
                'self_service_staging_tenants' => max(0, $tenantCount - 2),
                'provider_records' => 0,
                'jobs_table' => 'operational',
                'queue_jobs_table' => 'database_queue',
            ];

            $this->line($this->option('json') ? json_encode($result, JSON_THROW_ON_ERROR) : 'Live staging verification passed.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Live staging verification failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function assertSame(int $expected, int $actual, string $label): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("Unexpected {$label}: expected {$expected}, found {$actual}.");
        }
    }

    private function assertAtLeast(int $minimum, int $actual, string $label): void
    {
        if ($actual < $minimum) {
            throw new RuntimeException("Unexpected {$label}: expected at least {$minimum}, found {$actual}.");
        }
    }

    private function assertStripeMappingsAreAbsentOrCanonical(): int
    {
        $mappings = DB::table('price_provider_mappings as ppm')
            ->join('prices as pr', 'pr.id', '=', 'ppm.price_id')
            ->join('plan_versions as pv', 'pv.id', '=', 'pr.plan_version_id')
            ->join('plans as p', 'p.id', '=', 'pv.plan_id')
            ->where('ppm.payment_provider', 'stripe')
            ->get([
                'p.code as plan_code', 'ppm.price_phase', 'ppm.provider_price_id',
                'ppm.status', 'pr.currency', 'pr.interval',
            ]);
        if ($mappings->isEmpty()) {
            return 0;
        }

        $expected = collect(['service', 'growth', 'performance', 'ai_pro'])
            ->crossJoin(['launch', 'standard'])
            ->map(fn (array $slot): string => implode(':', $slot))
            ->sort()
            ->values();
        $actual = $mappings->map(function (object $mapping): string {
            if ($mapping->status !== 'active'
                || strtoupper((string) $mapping->currency) !== 'AED'
                || $mapping->interval !== 'month'
                || ! preg_match('/^price_[A-Za-z0-9]{8,}$/', (string) $mapping->provider_price_id)) {
                throw new RuntimeException('Stripe price mapping is not an active AED monthly Sandbox mapping.');
            }

            return $mapping->plan_code.':'.$mapping->price_phase;
        })->sort()->values();
        if ($actual->all() !== $expected->all()) {
            throw new RuntimeException('Stripe price mappings must be absent or contain all eight canonical paid-plan phases.');
        }

        return $mappings->count();
    }
}
