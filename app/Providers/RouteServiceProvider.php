<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin/dashboard';

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        // Middleware aliases (safe binding)
        if (class_exists(\App\Http\Middleware\EnsureUserIsActive::class)) {
            $router->aliasMiddleware('active', \App\Http\Middleware\EnsureUserIsActive::class);
        }

        if (class_exists(\App\Http\Middleware\ForcePasswordChange::class)) {
            $router->aliasMiddleware('force_password', \App\Http\Middleware\ForcePasswordChange::class);
        }

        $this->routes(function () {

            /*
            |--------------------------------------------------------------------------
            | API Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/api.php'))) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Web Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/web.php'))) {
                Route::middleware('web')
                    ->group(base_path('routes/web.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Admin Routes (SAFE middleware handling)
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/admin.php'))) {

                $middleware = ['web', 'auth'];

                if (class_exists(\App\Http\Middleware\EnsureUserIsActive::class)) {
                    $middleware[] = 'active';
                }

                if (class_exists(\App\Http\Middleware\ForcePasswordChange::class)) {
                    $middleware[] = 'force_password';
                }

                Route::middleware($middleware)
                    ->group(base_path('routes/admin.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Webhooks (NO middleware)
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/webhooks.php'))) {
                Route::middleware([]) // 🔥 IMPORTANT FIX
                    ->group(base_path('routes/webhooks.php'));
            }
        });
    }
}