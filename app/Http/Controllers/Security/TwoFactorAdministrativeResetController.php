<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Security\SecurityAudit;
use App\Security\TwoFactorPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class TwoFactorAdministrativeResetController extends Controller
{
    public function __invoke(
        Request $request,
        User $user,
        TwoFactorPolicy $policy,
        DisableTwoFactorAuthentication $disable,
    ): RedirectResponse {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        abort_unless($policy->canReset($request->user(), $user), 403);

        $disable($user);
        $user->forceFill([
            'two_factor_recovery_codes_acknowledged_at' => null,
            'two_factor_reenrollment_required_at' => now(),
        ])->save();
        $user->tokens()->delete();

        app(SecurityAudit::class)->record(
            'two_factor.administratively_reset',
            $request->user(),
            $user,
            ['reason' => $data['reason']],
            $request,
        );

        return back()->with('success', 'Two-factor authentication was reset. The user must enroll again.');
    }
}
