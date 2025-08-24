<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForcePasswordChange;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path to your application's "home" route.
     * Used by some auth scaffolding for post-login redirects.
     */
    public const HOME = '/dashboard';

    /**
     * Boot the route configuration.
     */
    public function boot(): void
    {
        // ✅ Ensure aliases exist even if Kernel isn't picked up due to cache
        Route::aliasMiddleware('active', EnsureUserIsActive::class);
        Route::aliasMiddleware('force_password', ForcePasswordChange::class);

        $this->routes(function () {
            // API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Admin routes
            // NOTE: Do NOT duplicate 'admin' prefix or name('admin.') inside routes/admin.php.
            // Define plain Route::get('/dashboard', ...) etc. in that file.
            Route::middleware(['web', 'auth', 'active', 'force_password'])
                ->prefix('admin')
                ->as('admin.')
                ->group(base_path('routes/admin.php'));
        });
    }
}
