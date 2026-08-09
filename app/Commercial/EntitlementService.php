<?php

namespace App\Commercial;

use App\Models\Commercial\CompanyEntitlementOverride;
use App\Models\Commercial\EntitlementUsage;
use App\Models\Commercial\PlanEntitlement;
use App\Models\Commercial\Subscription;
use App\Models\System\Company;
use Illuminate\Auth\Access\AuthorizationException;

class EntitlementService
{
    public function __construct(private readonly CompanyAccessService $companyAccess) {}

    public function can(Company|int $company, string $capability): bool
    {
        return $this->decide($company, $capability)->allowed;
    }

    public function assertCan(Company|int $company, string $capability): void
    {
        $decision = $this->decide($company, $capability);

        if (! $decision->allowed) {
            throw new AuthorizationException('This subscription does not include the requested capability.');
        }
    }

    public function limit(Company|int $company, string $capability): ?int
    {
        return $this->decide($company, $capability)->limit;
    }

    public function remaining(Company|int $company, string $capability): ?int
    {
        return $this->decide($company, $capability)->remaining;
    }

    public function subscriptionState(Company|int $company): string
    {
        $resolved = $this->resolveCompany($company);

        return $resolved?->subscription()->value('status') ?? 'unsubscribed';
    }

    public function decide(Company|int $company, string $capability): EntitlementDecision
    {
        $resolved = $this->resolveCompany($company);

        if (! Capabilities::isTenant($capability) || Capabilities::isPlatform($capability)) {
            return $this->denied($capability, 'registry', 'unknown_or_platform_capability', 'unresolved');
        }

        if (! $resolved || ! $this->companyAccess->companyCanAccess($resolved)) {
            return $this->denied($capability, 'company', 'company_disabled', 'unsubscribed');
        }

        $subscription = $resolved->subscription()->with('planVersion')->first();
        $state = $subscription?->status ?? 'unsubscribed';

        if (! $subscription || ! $subscription->isEntitledState()) {
            return $this->denied($capability, 'subscription', 'subscription_not_entitled', $state);
        }

        $override = CompanyEntitlementOverride::query()
            ->where('company_id', $resolved->id)
            ->where('capability', $capability)
            ->effective()
            ->latest('id')
            ->first();

        if ($override) {
            return $this->fromValues(
                $resolved,
                $subscription,
                $capability,
                'override',
                (bool) $override->enabled,
                $override->allowance,
                $override->mode,
            );
        }

        if ($subscription->status === 'legacy_grandfathered' || $subscription->grandfathered) {
            $entry = data_get($subscription->grandfathered_snapshot, 'capabilities.'.$capability);

            if (is_bool($entry)) {
                return $this->fromValues($resolved, $subscription, $capability, 'grandfathered', $entry, null, null);
            }
            if (is_array($entry) && array_key_exists('enabled', $entry)) {
                return $this->fromValues(
                    $resolved,
                    $subscription,
                    $capability,
                    'grandfathered',
                    (bool) $entry['enabled'],
                    isset($entry['allowance']) ? (int) $entry['allowance'] : null,
                    $entry['mode'] ?? null,
                );
            }

            return $this->denied($capability, 'grandfathered', 'capability_not_snapshotted', $state);
        }

        $entitlement = PlanEntitlement::query()
            ->where('plan_version_id', $subscription->plan_version_id)
            ->where('capability', $capability)
            ->first();

        if (! $entitlement) {
            return $this->denied($capability, 'plan', 'capability_not_defined', $state);
        }

        return $this->fromValues(
            $resolved,
            $subscription,
            $capability,
            'plan',
            (bool) $entitlement->enabled,
            $entitlement->allowance,
            $entitlement->mode,
        );
    }

    private function fromValues(
        Company $company,
        Subscription $subscription,
        string $capability,
        string $source,
        bool $enabled,
        ?int $limit,
        ?string $mode,
    ): EntitlementDecision {
        $remaining = null;

        if ($limit !== null) {
            $periodStart = $subscription->current_period_start?->copy()->startOfSecond() ?? now()->startOfMonth();
            $used = (int) EntitlementUsage::query()
                ->where('company_id', $company->id)
                ->where('capability', $capability)
                ->where('period_start', $periodStart)
                ->value('used');
            $remaining = max(0, $limit - $used);
        }

        $allowed = $enabled && ($limit === null || $remaining > 0);

        return new EntitlementDecision(
            allowed: $allowed,
            capability: $capability,
            source: $source,
            reason: $allowed ? 'granted' : ($enabled ? 'allowance_exhausted' : 'disabled'),
            subscriptionState: $subscription->status,
            limit: $limit,
            remaining: $remaining,
            mode: $mode,
        );
    }

    private function denied(string $capability, string $source, string $reason, string $state): EntitlementDecision
    {
        return new EntitlementDecision(false, $capability, $source, $reason, $state);
    }

    private function resolveCompany(Company|int $company): ?Company
    {
        return $company instanceof Company ? $company : Company::query()->find($company);
    }
}
