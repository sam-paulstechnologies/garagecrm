<?php

namespace App\Commercial;

use App\Models\System\Company;
use App\Models\User;

class CompanyAccessService
{
    public const ENABLED_STATUSES = ['active', 'trial', 'pilot'];

    public function userCanAccess(User $user): bool
    {
        if ($user->isPlatformUser()) {
            return $user->company_id === null;
        }

        if (! $user->company_id) {
            return false;
        }

        $company = $user->relationLoaded('company') ? $user->company : $user->company()->first();

        return $company instanceof Company && $this->companyCanAccess($company);
    }

    public function companyCanAccess(Company $company): bool
    {
        return in_array(strtolower((string) $company->status), self::ENABLED_STATUSES, true)
            && $company->suspended_at === null;
    }
}
