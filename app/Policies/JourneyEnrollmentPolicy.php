<?php

namespace App\Policies;

use App\Models\User;
use App\Models\JourneyEnrollment;

class JourneyEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->adminOrManager($user);
    }

    public function view(User $user, JourneyEnrollment $e): bool
    {
        return $this->adminOrManager($user) && $this->sameCompany($user, $e);
    }

    public function act(User $user, JourneyEnrollment $e): bool
    {
        return $this->adminOrManager($user) && $this->sameCompany($user, $e);
    }

    protected function adminOrManager(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager'], true);
    }

    protected function sameCompany(User $user, JourneyEnrollment $e): bool
    {
        return (int) $user->company_id === (int) $e->company_id;
    }
}
