<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Job\Job;

class JobPolicy
{
    public function viewAny(User $user): bool { return $this->adminOrManager($user); }
    public function view(User $user, Job $job): bool { return $this->sameCompany($user, $job) && $this->adminOrManager($user); }
    public function create(User $user): bool { return $this->adminOrManager($user); }
    public function update(User $user, Job $job): bool { return $this->sameCompany($user, $job) && $this->adminOrManager($user); }
    public function delete(User $user, Job $job): bool { return $this->sameCompany($user, $job) && $this->adminOrManager($user); }

    protected function adminOrManager(User $user): bool
    {
        return in_array($user->role, ['admin','manager'], true);
    }

    protected function sameCompany(User $user, Job $job): bool
    {
        return (int)$user->company_id === (int)$job->company_id;
    }
}
