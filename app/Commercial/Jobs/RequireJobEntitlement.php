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

        if (! $companyId || ! app(EntitlementService::class)->can($companyId, $capability)) {
            Log::notice('Commercially gated job skipped.', [
                'company_id' => $companyId,
                'capability' => $capability,
                'job' => $job::class,
            ]);

            return null;
        }

        return $next($job);
    }
}
