<?php

namespace App\Console\Commands;

use App\Commercial\Plans;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\Commercial\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MapStripeSandboxPrice extends Command
{
    protected $signature = 'billing:map-stripe-sandbox-price
        {plan : Stable plan code: service, growth, performance, or ai_pro}
        {phase : Price phase: launch or standard}
        {provider_price_id : Stripe Sandbox Price identifier beginning price_}
        {--dry-run : Validate and report without writing}
        {--confirm : Persist the mapping after all validation passes}
        {--replace-current= : Explicit current Price ID required for an audited mapping correction}';

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
        $replaceCurrent = trim((string) $this->option('replace-current'));

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

            if ($replaceCurrent !== '') {
                return $this->replaceMapping(
                    $price,
                    $planCode,
                    $phase,
                    $providerPriceId,
                    $replaceCurrent,
                    (float) $amount,
                    $dryRun,
                );
            }

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

    private function replaceMapping(
        Price $price,
        string $planCode,
        string $phase,
        string $providerPriceId,
        string $replaceCurrent,
        float $amount,
        bool $dryRun,
    ): int {
        if (! preg_match('/^price_[A-Za-z0-9]{8,}$/', $replaceCurrent)) {
            throw new RuntimeException('replace-current must be a Stripe Price identifier in price_... format.');
        }
        if ($replaceCurrent === $providerPriceId) {
            throw new RuntimeException('replace-current must differ from the replacement Stripe Price ID.');
        }

        $this->assertStripeSandboxPrice($providerPriceId, $amount);
        $state = $this->inspectReplacement($price, $phase, $providerPriceId, $replaceCurrent);
        if ($dryRun) {
            $this->info(($state === 'unchanged' ? 'Dry run passed; mapping is already corrected: ' : 'Dry run replacement passed: ')
                ."{$planCode} / {$phase} -> {$providerPriceId}");

            return self::SUCCESS;
        }

        $result = DB::transaction(function () use (
            $price,
            $planCode,
            $phase,
            $providerPriceId,
            $replaceCurrent,
            $amount,
        ): string {
            $state = $this->inspectReplacement(
                $price,
                $phase,
                $providerPriceId,
                $replaceCurrent,
                lock: true,
            );
            if ($state === 'unchanged') {
                return $state;
            }

            $mapping = PriceProviderMapping::query()
                ->where('price_id', $price->id)
                ->where('payment_provider', 'stripe')
                ->where('price_phase', $phase)
                ->lockForUpdate()
                ->firstOrFail();
            $mapping->update(['provider_price_id' => $providerPriceId]);
            EntitlementAuditLog::query()->create([
                'event' => 'billing.stripe_price_mapping_corrected',
                'source' => 'artisan',
                'context' => [
                    'plan_code' => $planCode,
                    'price_code' => $price->code,
                    'price_phase' => $phase,
                    'previous_provider_price_id' => $replaceCurrent,
                    'provider_price_id' => $providerPriceId,
                    'currency' => 'AED',
                    'interval' => 'month',
                    'expected_amount' => number_format($amount, 2, '.', ''),
                    'environment' => app()->environment(),
                ],
                'created_at' => now(),
            ]);

            return 'corrected';
        });

        $this->info(($result === 'unchanged' ? 'Mapping already corrected; no change: ' : 'Mapping corrected and audited: ')
            ."{$planCode} / {$phase} -> {$providerPriceId}");

        return self::SUCCESS;
    }

    private function inspectReplacement(
        Price $price,
        string $phase,
        string $providerPriceId,
        string $replaceCurrent,
        bool $lock = false,
    ): string {
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
        if (! $slot || $slot->status !== 'active') {
            throw new RuntimeException('the canonical plan/phase does not have an active mapping to correct.');
        }
        if ($slot->provider_price_id === $providerPriceId) {
            return 'unchanged';
        }
        if ($slot->provider_price_id !== $replaceCurrent) {
            throw new RuntimeException('the current mapping differs from the explicitly reviewed replace-current value.');
        }

        $external = $externalQuery->first();
        if ($external && $external->id !== $slot->id) {
            throw new RuntimeException('the replacement Stripe Price ID is already mapped to another canonical plan/phase.');
        }
        if (Subscription::query()
            ->where('payment_provider', 'stripe')
            ->where('provider_price_id', $replaceCurrent)
            ->exists()) {
            throw new RuntimeException('an existing Stripe subscription references the current mapping; automatic replacement is unsafe.');
        }
        if (BillingCheckoutSession::query()
            ->where('payment_provider', 'stripe')
            ->where('provider_price_id', $replaceCurrent)
            ->whereIn('status', BillingCheckoutSession::ACTIVE_STATUSES)
            ->exists()) {
            throw new RuntimeException('an active checkout references the current mapping; complete or expire it before replacement.');
        }

        return 'replace';
    }

    private function assertStripeSandboxPrice(string $providerPriceId, float $amount): void
    {
        $secret = (string) config('billing.stripe.secret_key');
        $apiBase = rtrim((string) config('billing.stripe.api_base'), '/');
        $apiVersion = (string) config('billing.stripe.api_version');
        if ((string) config('billing.mode') !== 'test' || ! str_starts_with($secret, 'sk_test_')) {
            throw new RuntimeException('a resolved Stripe test key and BILLING_MODE=test are required for mapping correction.');
        }
        if ($apiBase !== 'https://api.stripe.com') {
            throw new RuntimeException('mapping correction requires the official Stripe API base.');
        }

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->withHeaders(['Stripe-Version' => $apiVersion])
            ->get($apiBase.'/v1/prices/'.$providerPriceId);
        if (! $response->successful()) {
            $type = (string) $response->json('error.type', 'unknown_error');
            $code = (string) $response->json('error.code', 'unknown_code');
            throw new RuntimeException("Stripe rejected the replacement Price ID ({$response->status()} {$type}/{$code}).");
        }

        $expectedCents = (int) round($amount * 100);
        if ($response->json('id') !== $providerPriceId
            || $response->json('active') !== true
            || $response->json('livemode') !== false
            || strtolower((string) $response->json('currency')) !== 'aed'
            || (int) $response->json('unit_amount') !== $expectedCents
            || $response->json('recurring.interval') !== 'month'
            || (int) $response->json('recurring.interval_count') !== 1) {
            throw new RuntimeException('the replacement Price does not match the canonical active AED monthly Sandbox price.');
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
        $definitionKey = $phase === 'launch' ? 'promotional_amount' : 'list_amount';
        $configuredAmount = config("commercial.plans.{$planCode}.{$definitionKey}");
        if ($configuredAmount === null
            || number_format((float) $amount, 2, '.', '') !== number_format((float) $configuredAmount, 2, '.', '')) {
            throw new RuntimeException("canonical {$phase} amount differs from the reviewed commercial catalogue.");
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
