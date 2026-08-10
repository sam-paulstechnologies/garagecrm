<?php

namespace App\Commercial;

use App\Models\System\Company;

final class LegacyCommercialMigrationPlanner
{
    /**
     * Build an aggregate, read-only migration inventory. Tenant names and
     * contact fields are deliberately never loaded or returned.
     *
     * @return array<string, mixed>
     */
    public function plan(bool $includeCompanyIds = false): array
    {
        $counts = [
            'explicit_canonical' => 0,
            'legacy_grandfathered' => 0,
            'legacy_contract_requires_snapshot' => 0,
            'canonical_plan_reference_requires_approval' => 0,
            'subscription_requires_manual_review' => 0,
            'planless_requires_manual_review' => 0,
        ];
        $reviewIds = [];

        Company::query()
            ->select(['id', 'plan_id'])
            ->with([
                'plan:id,code',
                'subscription:id,company_id,plan_version_id,status,grandfathered',
                'subscription.planVersion:id,plan_id,code',
                'subscription.planVersion.plan:id,code',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($companies) use (&$counts, &$reviewIds): void {
                foreach ($companies as $company) {
                    $classification = $this->classify($company);
                    $counts[$classification]++;
                    if (! in_array($classification, ['explicit_canonical', 'legacy_grandfathered'], true)) {
                        $reviewIds[] = (int) $company->id;
                    }
                }
            });

        return [
            'status' => 'read_only',
            'companies_scanned' => array_sum($counts),
            'classifications' => $counts,
            'automatic_changes' => 0,
            'recommended_default' => 'shadow_evaluate_then_explicitly_approve_or_grandfather',
            'company_ids_requiring_review' => $includeCompanyIds ? $reviewIds : null,
        ];
    }

    private function classify(Company $company): string
    {
        $subscription = $company->subscription;
        if ($subscription) {
            if ($subscription->grandfathered || $subscription->status === 'legacy_grandfathered') {
                return 'legacy_grandfathered';
            }

            $code = (string) $subscription->planVersion?->plan?->code;

            return in_array($code, Plans::codes(), true)
                ? 'explicit_canonical'
                : 'subscription_requires_manual_review';
        }

        if (! $company->plan) {
            return 'planless_requires_manual_review';
        }

        return in_array((string) $company->plan->code, Plans::codes(), true)
            ? 'canonical_plan_reference_requires_approval'
            : 'legacy_contract_requires_snapshot';
    }
}
