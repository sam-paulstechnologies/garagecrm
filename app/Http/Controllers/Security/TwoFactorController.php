<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Security\SecurityAudit;
use App\Security\TwoFactorPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class TwoFactorController extends Controller
{
    public function show(Request $request, TwoFactorPolicy $policy): View
    {
        $user = $request->user();
        $passwordConfirmed = (int) $request->session()->get('auth.password_confirmed_at', 0)
            >= now()->getTimestamp() - (int) config('auth.password_timeout', 10800);

        $team = collect();
        if ($user->isPlatformUser() || ($user->role === 'admin' && $user->company_id)) {
            $query = User::query();
            $user->isPlatformUser()
                ? $query->whereNull('company_id')->whereIn('role', User::platformRoles())
                : $query->where('company_id', $user->company_id);

            $team = $query
                ->orderBy('name')->get()->map(fn (User $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->role,
                    'required' => $policy->isMandatory($member),
                    'enabled' => $policy->isConfirmed($member),
                    'can_reset' => $policy->canReset($user, $member),
                ]);
        }

        return view('security.two-factor', [
            'required' => $policy->isMandatory($user),
            'enabled' => $policy->isConfirmed($user),
            'secretGenerated' => filled($user->two_factor_secret),
            'passwordConfirmed' => $passwordConfirmed,
            'qrCodeSvg' => $passwordConfirmed && filled($user->two_factor_secret)
                ? $user->twoFactorQrCodeSvg()
                : null,
            'team' => $team,
            'stepUpMinutes' => (int) config('security.step_up_window_minutes', 15),
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $user = $request->user();
        $enable($user, true);
        $user->forceFill([
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes_acknowledged_at' => null,
        ])->save();

        app(SecurityAudit::class)->record('two_factor.enrollment_started', $user, $user, [], $request);

        return redirect()->route('security.two-factor.show')
            ->with('status', 'Scan the QR code and enter the current six-digit code.');
    }

    public function confirm(
        Request $request,
        ConfirmTwoFactorAuthentication $confirm,
        GenerateNewRecoveryCodes $generate,
    ): RedirectResponse {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        try {
            $confirm($user, $validated['code']);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'code' => 'The authentication code was invalid.',
            ]);
        }
        $generate($user);
        $user->forceFill(['two_factor_recovery_codes_acknowledged_at' => null])->save();
        $request->session()->put('security.show_recovery_codes', true);

        app(SecurityAudit::class)->record('two_factor.confirmed', $user, $user, [], $request);

        return redirect()->route('security.two-factor.recovery-codes');
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('security.show_recovery_codes')) {
            return redirect()->route('security.two-factor.show');
        }

        return view('security.recovery-codes', [
            'recoveryCodes' => $request->user()->recoveryCodes(),
        ]);
    }

    public function acknowledgeRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['acknowledged' => ['accepted']]);
        abort_unless($request->session()->pull('security.show_recovery_codes'), 403);
        $user = $request->user();
        $user->forceFill([
            'two_factor_recovery_codes_acknowledged_at' => now(),
            'two_factor_reenrollment_required_at' => null,
        ])->save();
        $request->session()->forget('security.enrollment_gate_audited');

        app(SecurityAudit::class)->record('two_factor.recovery_codes_acknowledged', $user, $user, [], $request);

        return redirect()->route('dashboard')->with('success', 'Two-factor authentication is active.');
    }

    public function regenerate(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasConfirmedTwoFactorAuthentication(), 403);
        $generate($user);
        $user->forceFill(['two_factor_recovery_codes_acknowledged_at' => null])->save();
        $request->session()->put('security.show_recovery_codes', true);

        app(SecurityAudit::class)->record('two_factor.recovery_codes_regenerated', $user, $user, [], $request);

        return redirect()->route('security.two-factor.recovery-codes');
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $user = $request->user();
        $disable($user);
        $reenrollment = app(TwoFactorPolicy::class)->isMandatory($user) ? now() : null;
        $user->forceFill([
            'two_factor_recovery_codes_acknowledged_at' => null,
            'two_factor_reenrollment_required_at' => $reenrollment,
        ])->save();
        $request->session()->forget(['security_step_up_at', 'auth.password_confirmed_at']);

        // M5: terminate the user's other sessions and API tokens on self-disable.
        app(\App\Security\SecuritySessionInvalidator::class)->afterSelfTwoFactorDisable($user, $request);

        app(SecurityAudit::class)->record('two_factor.disabled', $user, $user, [], $request);

        return redirect()->route('security.two-factor.show')
            ->with('warning', app(TwoFactorPolicy::class)->isMandatory($user)
                ? 'Two-factor authentication is required for your role. Enroll again to continue.'
                : 'Two-factor authentication has been disabled.');
    }
}
