<?php

namespace App\Commercial;

use App\Models\Commercial\Price;
use App\Models\System\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'usage_message' => $this->usageMessage($company, $current),
            'launch_amount' => $price?->promotional_amount,
            'standard_amount' => $price?->list_amount,
            'currency' => $price?->currency,
        ];
    }

    private function usageMessage(Company $company, string $plan): ?string
    {
        return match ($plan) {
            Plans::FREE => $this->freeUsage($company),
            Plans::SERVICE => $this->serviceUsage($company),
            Plans::GROWTH => $this->growthUsage($company),
            Plans::PERFORMANCE => $this->performanceUsage($company),
            default => null,
        };
    }

    private function freeUsage(Company $company): ?string
    {
        $inbound = $this->count('message_logs', $company, fn ($query) => $query->where('direction', 'in'));
        $used = Schema::hasTable('entitlement_usages') ? (int) DB::table('entitlement_usages')
            ->where('company_id', $company->id)
            ->where('capability', 'limit.ai_monitored_customers')
            ->sum('used') : 0;
        $limit = app(EntitlementService::class)->limit($company, 'limit.ai_monitored_customers') ?? 0;

        return $inbound > 0 || $used > 0
            ? "You received {$inbound} enquiries. SayaraForce monitored {$used} of {$limit} included customers this billing period."
            : null;
    }

    private function serviceUsage(Company $company): ?string
    {
        $bookings = $this->count('bookings', $company);

        return $bookings > 0
            ? "You are managing {$bookings} bookings. Growth shows which sources and staff drive them."
            : null;
    }

    private function growthUsage(Company $company): ?string
    {
        $leads = $this->count('leads', $company);
        $bookings = $this->count('bookings', $company);

        return $leads > 0 || $bookings > 0
            ? "Your real pipeline contains {$leads} leads and {$bookings} bookings. Performance connects them to campaign outcomes."
            : null;
    }

    private function performanceUsage(Company $company): ?string
    {
        $runs = $this->count('ai_analysis_runs', $company);

        return $runs > 0
            ? "SayaraForce recorded {$runs} AI analysis runs. AI Pro adds approval-controlled next actions."
            : null;
    }

    private function count(string $table, Company $company, ?callable $scope = null): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
            return 0;
        }

        $query = DB::table($table)->where('company_id', $company->id);
        if ($scope) {
            $scope($query);
        }

        return (int) $query->count();
    }
}
