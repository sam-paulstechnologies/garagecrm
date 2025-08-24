<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Usage:
     *  ->middleware('role:admin')                // single
     *  ->middleware('role:admin,manager')        // multiple
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $userRole = Auth::user()->role;

        // If middleware is used without arguments, just pass through.
        if (empty($roles)) {
            return $next($request);
        }

        // Support Laravel's "role:admin,manager" signature (roles already split by framework)
        if (!in_array($userRole, $roles, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
