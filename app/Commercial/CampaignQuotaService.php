<?php

namespace App\Commercial;

use App\Models\Commercial\EntitlementUsage;
use App\Models\Commercial\Subscription;
use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignQuotaService
{
    public const COUNT = 'limit.campaigns_per_period';

    public const RECIPIENTS = 'limit.campaign_recipients';

    public function __construct(private readonly EntitlementService $entitlements) {}

    public function create(Company $company, callable $creator): Model
    {
        return DB::transaction(function () use ($company, $creator): Model {
            $subscription = Subscription::query()->where('company_id', $company->id)->lockForUpdate()->firstOrFail();
            [$start, $end] = $this->period($subscription);
            $decision = $this->entitlements->decide($company, self::COUNT);
            $aggregate = EntitlementUsage::query()->firstOrCreate([
                'company_id' => $company->id, 'capability' => self::COUNT, 'period_start' => $start,
            ], ['period_end' => $end, 'used' => 0, 'metadata' => ['unit' => 'campaign']]);
            $aggregate = EntitlementUsage::query()->whereKey($aggregate->id)->lockForUpdate()->firstOrFail();
            if (! $decision->allowed || ($decision->limit !== null && $aggregate->used >= $decision->limit)) {
                throw ValidationException::withMessages([
                    'plan' => "You've used {$aggregate->used} of ".($decision->limit ?? 0).' campaigns this billing period.',
                ]);
            }

            $campaign = $creator();
            if (! $campaign instanceof Model || ! $campaign->getKey()) {
                throw new \RuntimeException('Campaign quota creator did not return a persisted model.');
            }
            $inserted = DB::table('entitlement_usage_events')->insertOrIgnore([
                'company_id' => $company->id, 'capability' => self::COUNT,
                'resource_type' => $campaign->getTable(), 'resource_key' => (string) $campaign->getKey(),
                'period_start' => $start, 'period_end' => $end, 'units' => 1,
                'metadata' => json_encode(['channel' => 'whatsapp']), 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($inserted === 1) {
                $aggregate->increment('used');
            }

            return $campaign;
        }, 3);
    }

    public function assertRecipients(Company $company, int $recipients): void
    {
        $decision = $this->entitlements->decide($company, self::RECIPIENTS);
        if (! $decision->allowed || ($decision->limit !== null && $recipients > $decision->limit)) {
            throw ValidationException::withMessages([
                'audience' => 'Your plan allows a maximum of '.($decision->limit ?? 0).' contacts per campaign.',
            ]);
        }
    }

    /** @return array{used:int,limit:?int,remaining:?int,recipients:?int,mode:?string} */
    public function summary(Company $company): array
    {
        $subscription = Subscription::query()->where('company_id', $company->id)->firstOrFail();
        [$start] = $this->period($subscription);
        $count = $this->entitlements->decide($company, self::COUNT);
        $recipients = $this->entitlements->decide($company, self::RECIPIENTS);
        $used = (int) EntitlementUsage::query()
            ->where('company_id', $company->id)
            ->where('capability', self::COUNT)
            ->where('period_start', $start)
            ->value('used');

        return [
            'used' => $used,
            'limit' => $count->limit,
            'remaining' => $count->limit === null ? null : max(0, $count->limit - $used),
            'recipients' => $recipients->limit,
            'mode' => $count->mode,
        ];
    }

    private function period(Subscription $subscription): array
    {
        $start = $subscription->current_period_start?->copy()->startOfSecond() ?? now()->startOfMonth();
        $end = $subscription->current_period_end?->copy()->startOfSecond() ?? $start->copy()->addMonth();

        return [$start, $end];
    }
}
