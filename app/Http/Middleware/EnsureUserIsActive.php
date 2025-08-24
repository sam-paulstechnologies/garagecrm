<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
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

        return $next($request);
    }
}
