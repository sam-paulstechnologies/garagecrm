<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Security\SecurityAudit;
use App\Security\SecurityStepUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class SecurityStepUpController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return redirect()->route('security.two-factor.show');
        }

        return view('security.step-up');
    }

    public function store(
        Request $request,
        TwoFactorAuthenticationProvider $provider,
        SecurityStepUp $stepUp,
    ): RedirectResponse {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);
        $user = $request->user();
        $key = 'security-step-up:'.$user->id.'|'.$request->ip();
        $max = max(3, (int) config('security.challenge_attempts_per_minute', 5));

        if (RateLimiter::tooManyAttempts($key, $max)) {
            app(SecurityAudit::class)->record('security.step_up_throttled', $user, $user, [], $request);
            throw ValidationException::withMessages(['code' => 'Too many attempts. Try again shortly.']);
        }

        $valid = Hash::check($validated['password'], $user->password)
            && $provider->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $validated['code'],
            );

        if (! $valid) {
            RateLimiter::hit($key, 60);
            app(SecurityAudit::class)->record('security.step_up_failed', $user, $user, [], $request);
            throw ValidationException::withMessages(['code' => 'Security verification failed.']);
        }

        RateLimiter::clear($key);
        $stepUp->mark($request);
        $request->session()->put('auth.password_confirmed_at', now()->getTimestamp());
        app(SecurityAudit::class)->record('security.step_up_completed', $user, $user, [], $request);

        return redirect()->to((string) $request->session()->pull('security.step_up_intended_url', route('dashboard')));
    }
}
