<?php

namespace App\Commercial;

use App\Models\Commercial\CommercialSetting;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Price;
use App\Models\Commercial\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class LaunchOfferService
{
    public const SETTING_KEY = 'launch_offer_enabled';

    public function isEnabled(): bool
    {
        if (! Schema::hasTable('commercial_settings')) {
            return false;
        }

        return (bool) CommercialSetting::query()
            ->where('key', self::SETTING_KEY)
            ->value('boolean_value');
    }

    public function durationCycles(): int
    {
        return max(0, (int) config('commercial.pricing.promotion_duration_paid_cycles', 0));
    }

    public function phaseForCheckout(Subscription $subscription, Price $price): string
    {
        if (! $this->hasLaunchPrice($price) || $this->durationCycles() <= 0) {
            return 'standard';
        }

        if ($subscription->launch_offer_qualified_at && ! $subscription->launch_offer_consumed_at
            && $subscription->introductory_cycles_completed < $this->durationCycles()) {
            return 'launch';
        }

        if ($subscription->launch_offer_qualified_at || $subscription->launch_offer_consumed_at) {
            return 'standard';
        }

        $currentPlan = (string) $subscription->planVersion?->plan?->code;
        $neverPaid = blank($subscription->payment_provider)
            && blank($subscription->provider_subscription_id)
            && in_array((string) $subscription->payment_status, ['', 'not_required'], true);

        return $this->isEnabled() && $currentPlan === Plans::FREE && $neverPaid
            ? 'launch'
            : 'standard';
    }

    public function isNewQualification(Subscription $subscription, string $phase): bool
    {
        return $phase === 'launch' && ! $subscription->launch_offer_qualified_at;
    }

    public function setEnabled(User $actor, bool $enabled): CommercialSetting
    {
        if (! $actor->isPlatformUser()) {
            throw new RuntimeException('Only platform administration may change commercial offer eligibility.');
        }

        return DB::transaction(function () use ($actor, $enabled): CommercialSetting {
            $setting = CommercialSetting::query()->where('key', self::SETTING_KEY)->lockForUpdate()->firstOrFail();
            $previous = (bool) $setting->boolean_value;
            if ($previous === $enabled) {
                return $setting;
            }

            $setting->update(['boolean_value' => $enabled, 'updated_by' => $actor->id]);
            EntitlementAuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => 'commercial.launch_offer_changed',
                'source' => 'platform_admin',
                'context' => [
                    'previous' => $previous,
                    'current' => $enabled,
                    'paid_monthly_cycles' => $this->durationCycles(),
                    'scope' => 'future_eligibility_only',
                ],
                'created_at' => now(),
            ]);

            return $setting->fresh();
        });
    }

    private function hasLaunchPrice(Price $price): bool
    {
        return $price->promotional_amount !== null
            && (float) $price->promotional_amount < (float) $price->list_amount;
    }
}
