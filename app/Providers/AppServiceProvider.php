<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

// ✅ R2 Observer wiring
use App\Models\MessageLog;
use App\Observers\MessageLogObserver;

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
        /*
        |--------------------------------------------------------------------------
        | Force HTTPS in production
        |--------------------------------------------------------------------------
        | Azure terminates SSL before the request reaches Laravel.
        | Without this, Laravel may generate http:// URLs and browser blocks login.
        */
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        // ✅ R2: Auto-generate AI suggestions when inbound messages are logged
        if (class_exists(MessageLog::class) && class_exists(MessageLogObserver::class)) {
            MessageLog::observe(MessageLogObserver::class);
        }
    }
}