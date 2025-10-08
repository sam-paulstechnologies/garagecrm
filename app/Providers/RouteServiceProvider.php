<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path to your application's "home" route.
     * Used by some auth scaffolding for post-login redirects.
     */
    public const HOME = '/dashboard';

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware aliases via the router (works well with route:cache)
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make(Router::class);

        // These classes should exist in App\Http\Middleware
        // If you sometimes toggle them out during dev, wrap in class_exists checks.
        if (class_exists(\App\Http\Middleware\EnsureUserIsActive::class)) {
            $router->aliasMiddleware('active', \App\Http\Middleware\EnsureUserIsActive::class);
        }
        if (class_exists(\App\Http\Middleware\ForcePasswordChange::class)) {
            $router->aliasMiddleware('force_password', \App\Http\Middleware\ForcePasswordChange::class);
        }

        $this->routes(function () {
            // API routes
            $apiPath = base_path('routes/api.php');
            if (is_file($apiPath)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($apiPath);
            }

            // Public web routes
            $webPath = base_path('routes/web.php');
            if (is_file($webPath)) {
                Route::middleware('web')
                    ->group($webPath);
            }

            // Admin routes (do NOT re-prefix/name inside routes/admin.php)
            $adminPath = base_path('routes/admin.php');
            if (is_file($adminPath)) {
                Route::middleware(['web', 'auth', 'active', 'force_password'])
                    ->prefix('admin')
                    ->as('admin.')
                    ->group($adminPath);
            }
        });
    }
}
