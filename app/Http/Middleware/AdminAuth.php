<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Ensure the authenticated user is an admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Adjust if your role column / values are different
        if (!in_array($user->role, ['admin', 'owner'], true)) {
            abort(403, 'Only admins can access this area.');
        }

        return $next($request);
    }
}
