<?php

namespace App\Http\Middleware;

use App\Security\SecurityConfigurationValidator;
use App\Security\SecurityStepUp;
use App\Security\TwoFactorPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRecentSecurityStepUp
{
    public function handle(Request $request, Closure $next): Response
    {
        $policy = app(TwoFactorPolicy::class);

        // Fail-closed: only pass an unconfirmed user through when verification is
        // genuinely not required (local/testing, or an explicit "audit" stage).
        // In a protected environment with unset/invalid enforcement this now
        // enforces instead of silently allowing the sensitive action (H2).
        if (! $policy->isConfirmed($request->user())
            && ! app(SecurityConfigurationValidator::class)->sensitiveActionsRequireVerification()) {
            return $next($request);
        }

        if (! $policy->isConfirmed($request->user())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Two-factor authentication is required for this sensitive action.'], 428);
            }

            return redirect()->route('security.two-factor.show')
                ->with('warning', 'Enable two-factor authentication before performing this sensitive action.');
        }

        if (app(SecurityStepUp::class)->isRecent($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Recent security verification is required.'], 428);
        }

        $request->session()->put('security.step_up_intended_url', url()->previous());

        return redirect()->route('security.step-up.show')
            ->with('warning', 'Confirm your password and authenticator code to continue.');
    }
}
