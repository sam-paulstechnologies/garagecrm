<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $mustChange = false;
        if ($user) {
            if (isset($user->must_change_password)) {
                $mustChange = (bool) $user->must_change_password;
            } elseif (isset($user->force_password_reset)) {
                $mustChange = (bool) $user->force_password_reset;
            }
        }

        if ($mustChange && !$request->routeIs('password.force.edit', 'password.force.update')) {
            return redirect()->route('password.force.edit');
        }

        return $next($request);
    }
}
