<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Security\SecurityAudit;
use App\Security\SecurityStepUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): SymfonyResponse
    {
        $user = $request->validatedUser();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            Auth::guard('web')->logout();
            $request->session()->migrate(true);
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);
            app(SecurityAudit::class)->record('two_factor.challenge_started', $user, $user, [], $request);

            $target = route('two-factor.login', absolute: false);

            // The login page is an Inertia screen while the challenge is a
            // deliberately isolated Blade document. Force a top-level visit
            // so the challenge can never be rendered in Inertia's invalid
            // response iframe while the address bar remains on /login.
            if ($request->header('X-Inertia')) {
                return Inertia::location($target);
            }

            return redirect()->to($target);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        /*
         * GO-LIVE FIX:
         * Always send user through /dashboard after login.
         * This prevents a manager from being redirected back to an old /admin URL
         * stored in url.intended, which would cause an immediate 403 after login.
         */
        $userRole = strtolower(trim((string) $user->role));

        $target = $userRole === 'media_team' && Route::has('admin.lead-sources.meta')
            ? route('admin.lead-sources.meta', absolute: false)
            : route('dashboard', absolute: false);

        if ($request->header('X-Inertia')) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): SymfonyResponse
    {
        Auth::guard('web')->logout();

        app(SecurityStepUp::class)->clear($request);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $target = route('login', absolute: false);

        if ($request->header('X-Inertia')) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }
}
