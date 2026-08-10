<?php

namespace App\Commercial;

use App\Models\System\Company;
use Illuminate\Auth\Access\AuthorizationException;

final class CommercialActionGate
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function assertAllowed(Company|int $company, string $capability, bool $explicitApproval = false): void
    {
        $decision = $this->entitlements->decide($company, $capability);

        if (! $decision->allowed) {
            throw new AuthorizationException('This subscription does not include the requested action.');
        }

        if ($decision->mode === 'disabled') {
            throw new AuthorizationException('This commercial action is disabled.');
        }

        if ($decision->mode === 'approval_required' && ! $explicitApproval) {
            throw new AuthorizationException('This commercial action requires explicit approval.');
        }
    }
}
