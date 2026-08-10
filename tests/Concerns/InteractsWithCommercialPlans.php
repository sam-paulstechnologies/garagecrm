<?php

namespace Tests\Concerns;

use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Models\Commercial\PlanVersion;
use App\Models\System\Company;
use Database\Seeders\CommercialFoundationSeeder;

trait InteractsWithCommercialPlans
{
    protected function assignCanonicalPlan(int|Company $company, string $plan = Plans::AI_PRO): Company
    {
        if (! PlanVersion::query()->where('code', $plan.':'.config('commercial.catalogue_version'))->exists()) {
            $this->seed(CommercialFoundationSeeder::class);
        }

        $resolved = $company instanceof Company ? $company : Company::query()->findOrFail($company);
        app(SubscriptionManager::class)->assignPlan($resolved, $plan);

        return $resolved->fresh();
    }
}
