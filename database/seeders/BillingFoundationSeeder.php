<?php

namespace Database\Seeders;

use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use Illuminate\Database\Seeder;
use LogicException;

class BillingFoundationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Fake billing mappings must never be seeded in production.');
        }

        Price::query()->orderBy('id')->each(function (Price $price): void {
            foreach (['launch', 'standard'] as $phase) {
                $providerId = 'price_fake_'.$phase.'_'.substr(hash('sha256', $price->code), 0, 24);
                $mapping = PriceProviderMapping::query()->firstOrCreate(
                    [
                        'price_id' => $price->id,
                        'payment_provider' => 'fake',
                        'price_phase' => $phase,
                    ],
                    ['provider_price_id' => $providerId, 'status' => 'active'],
                );
                if ($mapping->provider_price_id !== $providerId || $mapping->status !== 'active') {
                    throw new LogicException('Fake billing price mapping differs from the deterministic staging catalogue.');
                }
            }
        });
    }
}
