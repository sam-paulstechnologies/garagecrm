<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Usage:
     *  ->middleware('role:admin')
     *  ->middleware('role:admin,manager')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (empty($roles)) {
            return $next($request);
        }

        $userRole = strtolower(trim((string) Auth::user()->role));

        $roles = array_map(function ($role) {
            return strtolower(trim((string) $role));
        }, $roles);

        if (!in_array($userRole, $roles, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}