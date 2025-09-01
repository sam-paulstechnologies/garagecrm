<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use App\View\Components\AppLayout;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::component('app-layout', AppLayout::class);

        // Force HTTPS in production (behind Azure proxy)
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
