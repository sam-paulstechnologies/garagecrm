<?php

namespace App\Commercial\Jobs;

use App\Commercial\EntitlementService;
use Closure;
use Illuminate\Support\Facades\Log;

class RequireJobEntitlement
{
    public function handle(EntitlementAwareJob $job, Closure $next): mixed
    {
        $companyId = $job->entitlementCompanyId();
        $capability = $job->entitlementCapability();

        $decision = $companyId
            ? app(EntitlementService::class)->decide($companyId, $capability)
            : null;

        $approvalSatisfied = $decision?->mode !== 'approval_required'
            || ($job instanceof ExplicitlyApprovedEntitlementJob && $job->commercialActionApproved());

        if (! $companyId || ! $decision?->allowed || $decision->mode === 'disabled' || ! $approvalSatisfied) {
            Log::notice('Commercially gated job skipped.', [
                'company_id' => $companyId,
                'capability' => $capability,
                'job' => $job::class,
                'reason' => ! $approvalSatisfied ? 'approval_required' : ($decision?->reason ?? 'company_unresolved'),
            ]);

            return null;
        }

        return $next($job);
    }
}
