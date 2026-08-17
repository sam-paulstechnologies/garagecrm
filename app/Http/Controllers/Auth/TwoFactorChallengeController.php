<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Security\SecurityAudit;
use App\Security\SecurityStepUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthenticationProvider $provider): SymfonyResponse
    {
        $request->validate([
            'code' => ['nullable', 'digits:6', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:100', 'required_without:code'],
        ]);

        $user = User::query()->find($request->session()->get('login.id'));
        abort_unless($user && $user->hasEnabledTwoFactorAuthentication(), 401);

        $key = 'two-factor-login:'.$user->id.'|'.$request->ip();
        $max = max(3, (int) config('security.challenge_attempts_per_minute', 5));

        if (RateLimiter::tooManyAttempts($key, $max)) {
            app(SecurityAudit::class)->record('two_factor.challenge_throttled', $user, $user, [], $request);
            throw ValidationException::withMessages(['code' => 'Too many attempts. Try again shortly.']);
        }

        $usedRecoveryCode = false;
        $valid = false;

        if ($request->filled('code')) {
            $valid = $provider->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                (string) $request->input('code'),
            );
        } else {
            $submitted = trim((string) $request->input('recovery_code'));

            // M6: consume the recovery code atomically. A row lock + transaction
            // serialises concurrent submissions of the same code so exactly one
            // succeeds; the loser re-reads the row after the winner commits and
            // finds the code already replaced. Prevents the double-use race.
            [$valid, $usedRecoveryCode] = DB::transaction(function () use ($user, $submitted): array {
                $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->first();

                if ($locked === null) {
                    return [false, false];
                }

                $matched = collect($locked->recoveryCodes())->first(
                    fn (string $stored): bool => hash_equals($stored, $submitted)
                );

                if ($matched === null) {
                    return [false, false];
                }

                $locked->replaceRecoveryCode($matched);

                return [true, true];
            });

            if ($valid) {
                $user->refresh();
            }
        }

        if (! $valid) {
            RateLimiter::hit($key, 60);
            app(SecurityAudit::class)->record('two_factor.challenge_failed', $user, $user, [], $request);
            throw ValidationException::withMessages(['code' => 'The authentication code was invalid.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        if (! $usedRecoveryCode) {
            app(SecurityStepUp::class)->mark($request);
        }

        app(SecurityAudit::class)->record(
            $usedRecoveryCode ? 'two_factor.recovery_code_used' : 'two_factor.challenge_completed',
            $user,
            $user,
            [],
            $request,
        );

        $target = $this->intendedDestination($request);

        if ($request->header('X-Inertia')) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }

    private function intendedDestination(Request $request): string
    {
        $fallback = route('dashboard', absolute: false);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            $target = $intended;
        } else {
            $candidate = parse_url($intended);
            $application = parse_url((string) config('app.url'));

            if (! is_array($candidate) || ! is_array($application)
                || ! isset($candidate['host'], $application['host'])
                || ! hash_equals(strtolower((string) $application['host']), strtolower((string) $candidate['host']))
                || ($candidate['scheme'] ?? null) !== ($application['scheme'] ?? null)
                || ($candidate['port'] ?? null) !== ($application['port'] ?? null)) {
                return $fallback;
            }

            $target = ($candidate['path'] ?? '/').(isset($candidate['query']) ? '?'.$candidate['query'] : '');
        }

        $path = '/'.ltrim((string) parse_url($target, PHP_URL_PATH), '/');

        return in_array($path, ['/login', '/two-factor-challenge'], true)
            ? $fallback
            : $target;
    }
}
