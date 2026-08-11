<?php

namespace App\Console\Commands;

use App\Commercial\Plans;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MapStripeSandboxPrice extends Command
{
    protected $signature = 'billing:map-stripe-sandbox-price
        {plan : Stable plan code: service, growth, performance, or ai_pro}
        {phase : Price phase: launch or standard}
        {provider_price_id : Stripe Sandbox Price identifier beginning price_}
        {--dry-run : Validate and report without writing}
        {--confirm : Persist the mapping after all validation passes}';

    protected $description = 'Map one canonical SayaraForce price phase to a Stripe Sandbox Price ID';

    public function handle(): int
    {
        if (! app()->environment(['staging', 'testing'])) {
            $this->error('Refused: Stripe Sandbox price mapping is restricted to staging/test environments.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $confirm = (bool) $this->option('confirm');
        if ($dryRun === $confirm) {
            $this->error('Refused: specify exactly one of --dry-run or --confirm.');

            return self::FAILURE;
        }

        $planCode = (string) $this->argument('plan');
        $phase = (string) $this->argument('phase');
        $providerPriceId = (string) $this->argument('provider_price_id');

        try {
            $price = $this->canonicalPrice($planCode, $phase, $providerPriceId);
            $amount = $phase === 'launch' ? $price->promotional_amount : $price->list_amount;
            $expected = sprintf(
                '%s / %s -> %s (AED %s monthly)',
                $planCode,
                $phase,
                $providerPriceId,
                number_format((float) $amount, 2, '.', ','),
            );

            if ($dryRun) {
                $this->inspectConflicts($price, $phase, $providerPriceId);
                $this->info('Dry run passed: '.$expected);

                return self::SUCCESS;
            }

            $result = DB::transaction(function () use ($price, $planCode, $phase, $providerPriceId, $amount): string {
                $existing = $this->inspectConflicts($price, $phase, $providerPriceId, lock: true);
                if ($existing) {
                    return 'unchanged';
                }

                PriceProviderMapping::query()->create([
                    'price_id' => $price->id,
                    'payment_provider' => 'stripe',
                    'price_phase' => $phase,
                    'provider_price_id' => $providerPriceId,
                    'status' => 'active',
                ]);
                EntitlementAuditLog::query()->create([
                    'event' => 'billing.stripe_price_mapping_created',
                    'source' => 'artisan',
                    'context' => [
                        'plan_code' => $planCode,
                        'price_code' => $price->code,
                        'price_phase' => $phase,
                        'provider_price_id' => $providerPriceId,
                        'currency' => $price->currency,
                        'interval' => $price->interval,
                        'expected_amount' => number_format((float) $amount, 2, '.', ''),
                        'environment' => app()->environment(),
                    ],
                    'created_at' => now(),
                ]);

                return 'created';
            });

            $this->info(($result === 'created' ? 'Mapping created: ' : 'Mapping already exact; no change: ').$expected);

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error('Refused: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function canonicalPrice(string $planCode, string $phase, string $providerPriceId): Price
    {
        if (! in_array($planCode, [Plans::SERVICE, Plans::GROWTH, Plans::PERFORMANCE, Plans::AI_PRO], true)) {
            throw new RuntimeException('plan must be service, growth, performance, or ai_pro.');
        }
        if (! in_array($phase, ['launch', 'standard'], true)) {
            throw new RuntimeException('phase must be launch or standard.');
        }
        if (! preg_match('/^price_[A-Za-z0-9]{8,}$/', $providerPriceId)) {
            throw new RuntimeException('provider_price_id must be a Stripe Price identifier in price_... format.');
        }

        $catalogueVersion = (string) config('commercial.catalogue_version');
        $priceCode = $planCode.':'.$catalogueVersion.':aed-monthly';
        $price = Price::query()
            ->with('planVersion.plan')
            ->where('code', $priceCode)
            ->where('status', 'active')
            ->first();
        if (! $price
            || $price->planVersion?->code !== $planCode.':'.$catalogueVersion
            || $price->planVersion?->status !== 'active'
            || $price->planVersion?->plan?->code !== $planCode) {
            throw new RuntimeException("canonical active price {$priceCode} was not found.");
        }

        $expectedCurrency = strtoupper((string) config('commercial.pricing.currency'));
        $expectedInterval = (string) config('commercial.pricing.interval');
        if ($expectedCurrency !== 'AED' || strtoupper((string) $price->currency) !== 'AED') {
            throw new RuntimeException('canonical Stripe Sandbox prices must use AED.');
        }
        if ($expectedInterval !== 'month' || $price->interval !== 'month') {
            throw new RuntimeException('canonical Stripe Sandbox prices must use a monthly interval.');
        }

        $amount = $phase === 'launch' ? $price->promotional_amount : $price->list_amount;
        if ($amount === null || (float) $amount <= 0) {
            throw new RuntimeException("canonical {$phase} amount must be greater than zero.");
        }

        return $price;
    }

    private function inspectConflicts(
        Price $price,
        string $phase,
        string $providerPriceId,
        bool $lock = false,
    ): ?PriceProviderMapping {
        $slotQuery = PriceProviderMapping::query()
            ->where('price_id', $price->id)
            ->where('payment_provider', 'stripe')
            ->where('price_phase', $phase);
        $externalQuery = PriceProviderMapping::query()
            ->where('payment_provider', 'stripe')
            ->where('provider_price_id', $providerPriceId);
        if ($lock) {
            $slotQuery->lockForUpdate();
            $externalQuery->lockForUpdate();
        }

        $slot = $slotQuery->first();
        $external = $externalQuery->first();
        if ($slot) {
            if ($slot->provider_price_id !== $providerPriceId || $slot->status !== 'active') {
                throw new RuntimeException('the canonical plan/phase already has a different or inactive Stripe mapping.');
            }

            return $slot;
        }
        if ($external) {
            throw new RuntimeException('the Stripe Price ID is already mapped to another canonical plan/phase.');
        }

        return null;
    }
}
