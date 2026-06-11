<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureMediaTeamMetaOnly
{
    private const ALLOWED_ADMIN_ROUTES = [
        'admin.lead-sources.meta',
        'admin.lead-sources.meta.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || strtolower(trim((string) $user->role)) !== 'media_team') {
            return $next($request);
        }

        foreach (self::ALLOWED_ADMIN_ROUTES as $routePattern) {
            if ($request->routeIs($routePattern)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            abort(403, 'Media team users can only access Meta lead form sync.');
        }

        if (Route::has('admin.lead-sources.meta')) {
            return redirect()->route('admin.lead-sources.meta');
        }

        abort(403, 'Media team users can only access Meta lead form sync.');
    }
}
