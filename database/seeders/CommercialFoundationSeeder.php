<?php

namespace Database\Seeders;

use App\Commercial\Capabilities;
use App\Models\Commercial\PlanEntitlement;
use App\Models\Commercial\PlanVersion;
use App\Models\Commercial\Price;
use App\Models\System\Plan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use LogicException;

class CommercialFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $catalogueVersion = (string) config('commercial.catalogue_version');
        $effectiveFrom = (string) config('commercial.effective_from');
        $pricing = (array) config('commercial.pricing');

        foreach ((array) config('commercial.plans') as $code => $definition) {
            $plan = Plan::query()->updateOrCreate(['code' => $code], [
                'name' => $definition['name'],
                'rank' => $definition['rank'],
                'description' => $definition['description'],
                'price' => $definition['promotional_amount'],
                'currency' => $pricing['currency'],
                'whatsapp_limit' => $definition['limits']['limit.ai_monitored_customers'],
                'user_limit' => $definition['limits']['limit.users'],
                'features' => array_values($definition['capabilities']),
                'status' => true,
            ]);

            $version = PlanVersion::query()->firstOrCreate(
                ['code' => $code.':'.$catalogueVersion],
                ['plan_id' => $plan->id, 'version' => 1, 'effective_from' => $effectiveFrom, 'status' => 'active'],
            );
            $this->assertSameCommercialRecord(
                (int) $version->plan_id === (int) $plan->id
                    && $version->version === 1
                    && $version->status === 'active'
                    && $version->effective_from?->equalTo(CarbonImmutable::parse($effectiveFrom))
                    && $version->effective_to === null,
                'plan version',
                $code,
            );

            $price = Price::query()->firstOrCreate(
                ['code' => $code.':'.$catalogueVersion.':aed-monthly'],
                [
                    'plan_version_id' => $version->id,
                    'currency' => $pricing['currency'],
                    'interval' => $pricing['interval'],
                    'list_amount' => $definition['list_amount'],
                    'promotional_amount' => $definition['promotional_amount'],
                    'promotion_effective_from' => $effectiveFrom,
                    'promotion_duration_months' => $pricing['promotion_duration_months'],
                    'renewal_behavior' => $pricing['renewal_behavior'],
                    'status' => 'active',
                ],
            );
            $this->assertSameCommercialRecord(
                (int) $price->plan_version_id === (int) $version->id
                    && $price->currency === $pricing['currency']
                    && $price->interval === $pricing['interval']
                    && (string) $price->list_amount === number_format((float) $definition['list_amount'], 2, '.', '')
                    && (string) $price->promotional_amount === number_format((float) $definition['promotional_amount'], 2, '.', '')
                    && $price->promotion_effective_from?->equalTo(CarbonImmutable::parse($effectiveFrom))
                    && $price->promotion_effective_to === null
                    && $price->promotion_duration_months === $pricing['promotion_duration_months']
                    && $price->renewal_behavior === $pricing['renewal_behavior']
                    && $price->status === 'active',
                'price',
                $code,
            );

            foreach (Capabilities::tenant() as $capability) {
                $isLimit = Capabilities::isLimit($capability);
                $allowance = $isLimit ? ($definition['limits'][$capability] ?? null) : null;
                $enabled = $isLimit
                    ? array_key_exists($capability, (array) ($definition['limits'] ?? []))
                    : in_array($capability, $definition['capabilities'], true);

                $entitlement = PlanEntitlement::query()->firstOrCreate(
                    ['plan_version_id' => $version->id, 'capability' => $capability],
                    [
                        'value_type' => $isLimit ? 'integer' : 'boolean',
                        'value' => $isLimit ? ['allowance' => $allowance] : ['enabled' => $enabled],
                        'allowance' => $allowance,
                        'enabled' => $enabled,
                        'mode' => $definition['modes'][$capability] ?? ($enabled ? 'enabled' : 'disabled'),
                    ],
                );
                $expectedMode = $definition['modes'][$capability] ?? ($enabled ? 'enabled' : 'disabled');
                $this->assertSameCommercialRecord(
                    $entitlement->value_type === ($isLimit ? 'integer' : 'boolean')
                        && $entitlement->value === ($isLimit ? ['allowance' => $allowance] : ['enabled' => $enabled])
                        && $entitlement->allowance === $allowance
                        && (bool) $entitlement->enabled === $enabled
                        && $entitlement->mode === $expectedMode,
                    'entitlement',
                    $code.':'.$capability,
                );
            }
        }
    }

    private function assertSameCommercialRecord(bool $condition, string $type, string $code): void
    {
        if (! $condition) {
            throw new LogicException("Immutable {$type} {$code} differs from the canonical catalogue; create a new version.");
        }
    }
}
