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
        // Bind the AI NLP service so you can resolve it via app(NlpService::class)
        $this->app->singleton(\App\Services\Ai\NlpService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep your existing Vite prefetch settings
        Vite::prefetch(concurrency: 3);
    }
}
