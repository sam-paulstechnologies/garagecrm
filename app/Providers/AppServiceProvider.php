<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AI services singletons
        $this->app->singleton(\App\Services\Ai\NlpService::class);
        if (class_exists(\App\Services\Ai\ActionSuggestService::class)) {
            $this->app->singleton(\App\Services\Ai\ActionSuggestService::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
