<?php

namespace App\Http\Middleware;

use App\Commercial\CompanyAccessService;
use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function __construct(private readonly CompanyAccessService $companyAccess) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $isActive = isset($user->is_active)
            ? (bool) $user->is_active
            : (isset($user->status) ? ((int) $user->status === 1) : true);

        if (!$isActive) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is inactive. Please contact the administrator.'
            ]);
        }

        abort_unless(
            $this->companyAccess->userCanAccess($user),
            403,
            'This company is not currently permitted to use SayaraForce.'
        );

        return $next($request);
    }
}
