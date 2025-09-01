<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use App\View\Components\AppLayout;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Blade components
        Blade::component('app-layout', AppLayout::class);

        // Force HTTPS when running in production behind Azure’s proxy
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
