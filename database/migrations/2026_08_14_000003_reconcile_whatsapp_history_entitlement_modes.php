<?php

use App\Commercial\Capabilities;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_versions') || ! Schema::hasTable('plan_entitlements')) {
            return;
        }

        DB::transaction(function (): void {
            foreach ((array) config('commercial.plans') as $planCode => $definition) {
                $versionId = DB::table('plan_versions')
                    ->where('code', $planCode.':'.config('commercial.catalogue_version'))
                    ->value('id');
                if (! $versionId) {
                    continue;
                }

                foreach (Capabilities::tenant() as $capability) {
                    $isLimit = Capabilities::isLimit($capability);
                    $limits = (array) ($definition['limits'] ?? []);
                    $allowance = $isLimit ? ($limits[$capability] ?? null) : null;
                    $enabled = $isLimit
                        ? array_key_exists($capability, $limits)
                        : in_array($capability, (array) ($definition['capabilities'] ?? []), true);
                    $expectedValue = $isLimit ? ['allowance' => $allowance] : ['enabled' => $enabled];
                    $expectedMode = $definition['modes'][$capability] ?? ($enabled ? 'enabled' : 'disabled');
                    $entitlement = DB::table('plan_entitlements')
                        ->where('plan_version_id', $versionId)
                        ->where('capability', $capability)
                        ->lockForUpdate()
                        ->first();
                    if (! $entitlement) {
                        throw new \RuntimeException("Entitlement mode repair refused a missing {$planCode}:{$capability} record.");
                    }

                    $actualValue = json_decode((string) $entitlement->value, true);
                    $actualAllowance = $entitlement->allowance === null ? null : (int) $entitlement->allowance;
                    if ($entitlement->value_type !== ($isLimit ? 'integer' : 'boolean')
                        || $actualValue !== $expectedValue
                        || $actualAllowance !== $allowance
                        || (bool) $entitlement->enabled !== $enabled) {
                        throw new \RuntimeException("Entitlement mode repair refused non-mode drift in {$planCode}:{$capability}.");
                    }

                    if ($entitlement->mode !== $expectedMode) {
                        DB::table('plan_entitlements')->where('id', $entitlement->id)->update([
                            'mode' => $expectedMode,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            if (Schema::hasTable('entitlement_audit_logs')) {
                DB::table('subscriptions')->orderBy('id')->get(['id', 'company_id'])->each(function (object $subscription): void {
                    DB::table('entitlement_audit_logs')->insert([
                        'company_id' => $subscription->company_id,
                        'subscription_id' => $subscription->id,
                        'event' => 'commercial.prelaunch_limit_modes_reconciled',
                        'source' => 'migration',
                        'context' => json_encode([
                            'catalogue' => config('commercial.catalogue_version'),
                            'scope' => 'mode_only',
                        ]),
                        'created_at' => now(),
                    ]);
                });
            }
        });
    }

    public function down(): void
    {
        // The prior dot-path result was invalid catalogue state. Reintroducing
        // it would make the immutable catalogue fail its own safety check.
    }
};
