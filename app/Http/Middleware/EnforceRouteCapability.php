<?php

namespace App\Http\Middleware;

use App\Commercial\CapabilityDeniedResponder;
use App\Commercial\EntitlementService;
use App\Commercial\RouteCapabilityMap;
use Closure;
use Illuminate\Http\Request;

class EnforceRouteCapability
{
    public function __construct(
        private readonly RouteCapabilityMap $routes,
        private readonly EntitlementService $entitlements,
        private readonly CapabilityDeniedResponder $denied,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $capability = $this->routes->capabilityFor($request->route()?->getName());

        if (! $user || $user->isPlatformUser() || ! $capability) {
            return $next($request);
        }

        $company = $user->company;
        if (! $company || ! $this->entitlements->can($company, $capability)) {
            return $company
                ? $this->denied->respond($request, $company, $capability)
                : abort(403, 'A tenant company is required.');
        }

        return $next($request);
    }
}
