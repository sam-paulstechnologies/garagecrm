<?php

namespace App\Commercial;

use App\Models\Commercial\Price;
use RuntimeException;

final class PublicPricingCatalogue
{
    /** @return array<int, array<string, mixed>> */
    public function plans(): array
    {
        $definitions = (array) config('commercial_public.plans');
        $prices = Price::query()
            ->where('status', 'active')
            ->where('currency', config('commercial.pricing.currency'))
            ->where('interval', config('commercial.pricing.interval'))
            ->with('planVersion.plan')
            ->get()
            ->keyBy(fn (Price $price): string => (string) $price->planVersion?->plan?->code);

        return collect(Plans::codes())->map(function (string $code) use ($definitions, $prices): array {
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
                'promotion_cycles' => (int) $price->promotion_duration_months,
                'renewal_behavior' => (string) $price->renewal_behavior,
                'recommended' => $code === Plans::SERVICE,
                'custom_from' => $code === Plans::AI_PRO,
            ];
        })->all();
    }
}
