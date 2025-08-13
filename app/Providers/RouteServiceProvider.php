<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Path to your application's "home" route.
     */
    public const HOME = '/dashboard';

    /**
     * Boot the route configuration.
     */
    public function boot(): void
    {
        $this->routes(function () {
            // ✅ This correctly loads routes/api.php
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
