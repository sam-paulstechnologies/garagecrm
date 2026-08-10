<?php

namespace App\Commercial;

use App\Models\Commercial\Price;
use App\Models\System\Company;

class UpsellPresentationService
{
    private const LADDER = [
        Plans::FREE => [
            'next' => Plans::SERVICE,
            'message' => "You've seen what you're missing. Now don't miss another booking.",
        ],
        Plans::SERVICE => [
            'next' => Plans::GROWTH,
            'message' => "You're managing your bookings. Now see what's driving your business.",
        ],
        Plans::GROWTH => [
            'next' => Plans::PERFORMANCE,
            'message' => 'Stop measuring marketing by leads. Measure it by customers.',
        ],
        Plans::PERFORMANCE => [
            'next' => Plans::AI_PRO,
            'message' => 'You have seen what needs attention. Now let SayaraForce AI act on it.',
        ],
        Plans::AI_PRO => [
            'next' => null,
            'message' => 'This capability requires an explicit SayaraForce entitlement.',
        ],
    ];

    public function for(Company $company, string $capability): array
    {
        $subscription = $company->subscription()->with('planVersion.plan')->first();
        $current = (string) ($subscription?->planVersion?->plan?->code ?? Plans::FREE);
        $step = self::LADDER[$current] ?? self::LADDER[Plans::FREE];
        $next = $step['next'];
        $price = $next ? Price::query()
            ->where('code', $next.':'.config('commercial.catalogue_version').':aed-monthly')
            ->where('status', 'active')
            ->first() : null;

        return [
            'capability' => $capability,
            'current_plan' => $current,
            'next_plan' => $next,
            'message' => $step['message'],
            'launch_amount' => $price?->promotional_amount,
            'standard_amount' => $price?->list_amount,
            'currency' => $price?->currency,
        ];
    }
}
