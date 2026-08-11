<?php

namespace App\Commercial;

use App\Models\Commercial\Price;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class PublicPricingCatalogue
{
    public function __construct(private readonly LaunchOfferService $launchOffer) {}

    /** @return array<int, array<string, mixed>> */
    public function plans(): array
    {
        if (! Schema::hasTable('plans') || ! Schema::hasTable('plan_versions') || ! Schema::hasTable('prices')) {
            return [];
        }

        $definitions = (array) config('commercial_public.plans');
        $prices = Price::query()
            ->where('status', 'active')
            ->where('currency', config('commercial.pricing.currency'))
            ->where('interval', config('commercial.pricing.interval'))
            ->with('planVersion.plan')
            ->get()
            ->keyBy(fn (Price $price): string => (string) $price->planVersion?->plan?->code);

        $offerEnabled = $this->launchOffer->isEnabled();

        return collect(Plans::codes())->map(function (string $code) use ($definitions, $prices, $offerEnabled): array {
            /** @var Price|null $price */
            $price = $prices->get($code);
            $definition = $definitions[$code] ?? null;
            if (! $price || ! is_array($definition)) {
                throw new RuntimeException("Public commercial catalogue is incomplete for {$code}.");
            }

            return [
                'code' => $code,
                'name' => (string) $price->planVersion->plan->name,
                'positioning' => (string) $definition['positioning'],
                'features' => array_values((array) $definition['features']),
                'cta' => (string) $definition['cta'],
                'currency' => (string) $price->currency,
                'launch_amount' => (float) $price->promotional_amount,
                'standard_amount' => (float) $price->list_amount,
                'display_amount' => $offerEnabled
                    ? (float) $price->promotional_amount
                    : (float) $price->list_amount,
                'launch_offer_enabled' => $offerEnabled,
                'promotion_cycles' => $this->launchOffer->durationCycles(),
                'renewal_behavior' => (string) $price->renewal_behavior,
                'recommended' => $code === Plans::SERVICE,
                'custom_from' => $code === Plans::AI_PRO,
            ];
        })->all();
    }
}
