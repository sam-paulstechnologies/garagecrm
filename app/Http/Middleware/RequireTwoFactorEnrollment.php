<?php

namespace App\Http\Middleware;

use App\Security\SecurityAudit;
use App\Security\TwoFactorPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! app(TwoFactorPolicy::class)->isMandatory($user)
            || app(TwoFactorPolicy::class)->isConfirmed($user)
            || $this->isAllowedEnrollmentRequest($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            app(SecurityAudit::class)->record('two_factor.enrollment_required', $user, $user, [], $request);

            return response()->json([
                'message' => 'Two-factor enrollment is required.',
                'two_factor_enrollment_required' => true,
            ], 423);
        }

        if (! $request->session()->has('security.enrollment_gate_audited')) {
            app(SecurityAudit::class)->record('two_factor.enrollment_required', $user, $user);
            $request->session()->put('security.enrollment_gate_audited', true);
        }

        return redirect()->route('security.two-factor.show');
    }

    private function isAllowedEnrollmentRequest(Request $request): bool
    {
        return $request->routeIs(
            'security.two-factor.*',
            'password.confirm',
            'password.confirm.store',
            'logout',
        );
    }
}
