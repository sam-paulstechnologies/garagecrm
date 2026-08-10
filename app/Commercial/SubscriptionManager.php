<?php

namespace App\Commercial;

use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\PlanVersion;
use App\Models\Commercial\Price;
use App\Models\Commercial\Subscription;
use App\Models\System\Company;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionManager
{
    public function assignFree(Company $company): Subscription
    {
        return $this->assignPlan($company, Plans::FREE);
    }

    public function assignPlan(Company $company, string $planCode, string $status = 'active'): Subscription
    {
        if (! in_array($planCode, Plans::codes(), true)) {
            throw new RuntimeException('Unknown canonical plan code.');
        }

        return DB::transaction(function () use ($company, $planCode, $status): Subscription {
            $version = PlanVersion::query()
                ->where('code', $planCode.':'.config('commercial.catalogue_version'))
                ->where('status', 'active')
                ->with('plan')
                ->first();

            if (! $version) {
                throw new RuntimeException('Commercial catalogue is not provisioned. Registration refused.');
            }

            $price = Price::query()
                ->where('plan_version_id', $version->id)
                ->where('currency', config('commercial.pricing.currency'))
                ->where('interval', config('commercial.pricing.interval'))
                ->where('status', 'active')
                ->first();
            if (! $price) {
                throw new RuntimeException('Commercial price catalogue is not provisioned. Subscription refused.');
            }

            $previous = Subscription::query()->with('planVersion')->where('company_id', $company->id)->first();
            $previousPlanVersion = $previous?->planVersion?->code;
            $previousStatus = $previous?->status;
            $now = now();
            $sameCommercialVersion = $previous?->plan_version_id === $version->id && $previous?->price_id === $price->id;
            $promotionStartedAt = $sameCommercialVersion
                ? $previous->promotion_started_at
                : ($price->isPromotionAvailableAt($now) ? $now : null);
            $promotionEndsAt = $promotionStartedAt && $price->promotion_duration_months
                ? $promotionStartedAt->copy()->addMonths($price->promotion_duration_months)
                : null;
            $subscription = Subscription::query()->updateOrCreate(
                ['company_id' => $company->id],
                [
                    'plan_version_id' => $version->id,
                    'price_id' => $price->id,
                    'status' => $status,
                    'started_at' => $sameCommercialVersion ? $previous->started_at : $now,
                    'current_period_start' => $now->copy()->startOfDay(),
                    'current_period_end' => $now->copy()->addMonth()->startOfDay(),
                    'promotion_started_at' => $promotionStartedAt,
                    'promotion_ends_at' => $promotionEndsAt,
                    'cancel_at_period_end' => false,
                    'cancelled_at' => null,
                    'grandfathered' => $status === 'legacy_grandfathered',
                    'payment_provider' => null,
                    'provider_customer_id' => null,
                    'provider_subscription_id' => null,
                    'provider_price_id' => null,
                    'payment_status' => $planCode === Plans::FREE ? 'not_required' : 'pending',
                    'grace_ends_at' => null,
                    'suspended_at' => null,
                    'cancellation_requested_at' => null,
                    'provider_state_updated_at' => null,
                    'introductory_cycles_completed' => 0,
                    'standard_price_transition_requested_at' => null,
                ],
            );

            $company->forceFill(['plan_id' => $version->plan_id])->save();

            EntitlementAuditLog::query()->create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'actor_id' => auth()->id(),
                'event' => $previous ? 'subscription.changed' : 'subscription.assigned',
                'source' => 'application',
                'context' => [
                    'plan_code' => $planCode,
                    'plan_version' => $version->code,
                    'price_code' => $price->code,
                    'previous_plan_version' => $previousPlanVersion,
                    'previous_status' => $previousStatus,
                    'status' => $status,
                ],
                'created_at' => $now,
            ]);

            return $subscription;
        });
    }
}
