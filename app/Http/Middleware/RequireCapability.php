<?php

namespace App\Http\Middleware;

use App\Commercial\EntitlementService;
use Closure;
use Illuminate\Http\Request;

class RequireCapability
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function handle(Request $request, Closure $next, string $capability)
    {
        $company = $request->user()?->company;

        abort_unless($company && $this->entitlements->can($company, $capability), 403, 'Capability not included in this subscription.');

        return $next($request);
    }
}
