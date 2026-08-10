<?php

namespace App\Http\Middleware;

use App\Commercial\EntitlementService;
use App\Commercial\CapabilityDeniedResponder;
use Closure;
use Illuminate\Http\Request;

class RequireCapability
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly CapabilityDeniedResponder $denied,
    ) {}

    public function handle(Request $request, Closure $next, string $capability)
    {
        $company = $request->user()?->company;

        abort_unless($company, 403, 'A tenant company is required.');

        if (! $this->entitlements->can($company, $capability)) {
            return $this->denied->respond($request, $company, $capability);
        }

        return $next($request);
    }
}
