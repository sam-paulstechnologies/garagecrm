<?php

namespace App\Security;

use App\Models\User;

class TwoFactorPolicy
{
    public function enforcementMode(): string
    {
        $mode = (string) config('security.two_factor_enforcement', 'off');

        return in_array($mode, ['off', 'audit', 'required_admins'], true) ? $mode : 'off';
    }

    public function isMandatory(User $user): bool
    {
        if ($user->two_factor_reenrollment_required_at !== null) {
            return true;
        }

        // Fail-closed: in a protected environment an unset/invalid enforcement
        // value forces privileged MFA rather than silently disabling it (H2).
        if (! app(SecurityConfigurationValidator::class)->privilegedMfaMandatory()) {
            return false;
        }

        $roles = $user->isPlatformUser()
            ? config('security.mandatory_roles.platform', [])
            : config('security.mandatory_roles.tenant', []);

        return in_array(strtolower((string) $user->role), $roles, true);
    }

    public function isConfirmed(User $user): bool
    {
        return $user->hasConfirmedTwoFactorAuthentication();
    }

    public function canReset(User $actor, User $target): bool
    {
        if (! $this->isConfirmed($actor)) {
            return false;
        }

        if ($actor->isPlatformUser()) {
            return ! $target->isPlatformUser() && $target->id !== $actor->id;
        }

        return $actor->role === 'admin'
            && $actor->company_id !== null
            && $actor->company_id === $target->company_id
            && ! in_array((string) $target->role, ['admin', 'super_admin', 'platform_admin'], true)
            && $target->id !== $actor->id;
    }
}
