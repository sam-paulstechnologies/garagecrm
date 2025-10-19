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
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make(Router::class);

        // Optional middleware aliases (safe if classes are absent)
        if (class_exists(\App\Http\Middleware\EnsureUserIsActive::class)) {
            $router->aliasMiddleware('active', \App\Http\Middleware\EnsureUserIsActive::class);
        }
        if (class_exists(\App\Http\Middleware\ForcePasswordChange::class)) {
            $router->aliasMiddleware('force_password', \App\Http\Middleware\ForcePasswordChange::class);
        }

        $this->routes(function () {
            // ---------------------------
            // API routes (single file)
            // ---------------------------
            $apiPath = base_path('routes/api.php');
            if (is_file($apiPath)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($apiPath);
            }

            // ---------------------------------------
            // API feature routes (modular directory)
            // routes/api/*.php  => same API group
            // ---------------------------------------
            $apiDir = base_path('routes/api');
            if (is_dir($apiDir)) {
                foreach (glob($apiDir . '/*.php') as $file) {
                    // Skip api.php if someone placed it inside the folder
                    if (realpath($file) === realpath($apiPath)) {
                        continue;
                    }
                    Route::middleware('api')
                        ->prefix('api')
                        ->group($file);
                }
            }

            // ---------------------------
            // Public web routes (single)
            // ---------------------------
            $webPath = base_path('routes/web.php');
            if (is_file($webPath)) {
                Route::middleware('web')
                    ->group($webPath);
            }

            // ------------------------------
            // Public webhooks (Twilio, Meta)
            // NOW SERVED VIA API MIDDLEWARE
            // ------------------------------
            $webhooksPath = base_path('routes/webhooks.php');
            if (is_file($webhooksPath)) {
                Route::middleware('api')   // <--- switched from 'web' to 'api'
                    ->group($webhooksPath);
            }

            // ---------------------------------------------------
            // Admin routes (single file; base/legacy definitions)
            // NOTE: Do NOT add prefix('admin') or ->name() inside routes/admin.php
            // ---------------------------------------------------
            $adminPath = base_path('routes/admin.php');
            if (is_file($adminPath)) {
                Route::middleware(['web', 'auth', 'active', 'force_password'])
                    ->prefix('admin')
                    ->as('admin.')
                    ->group($adminPath);
            }

            // ---------------------------------------------------------
            // Admin feature routes (modular directory)
            // routes/admin/*.php => same admin group (auth + prefixes)
            // NOTE: Inside these files, DO NOT add prefix('admin') or ->name().
            // ---------------------------------------------------------
            $adminDir = base_path('routes/admin');
            if (is_dir($adminDir)) {
                foreach (glob($adminDir . '/*.php') as $file) {
                    Route::middleware(['web', 'auth', 'active', 'force_password'])
                        ->prefix('admin')
                        ->as('admin.')
                        ->group($file);
                }
            }

            // ---------------------------------------------------------
            // Admin WhatsApp routes (consolidated here)
            // Keep WhatsApp admin web routes in routes/whatsapp.php
            // Inside routes/whatsapp.php, DO NOT add prefix('admin') or ->name('admin.')
            // (Use Route::prefix('whatsapp')->as('whatsapp.') only.)
            // ---------------------------------------------------------
            $whatsAppPath = base_path('routes/whatsapp.php');
            if (is_file($whatsAppPath)) {
                Route::middleware(['web', 'auth', 'active', 'force_password'])
                    ->prefix('admin')
                    ->as('admin.')
                    ->group($whatsAppPath);
            }

            // NOTE: Legacy routes/admin_whatsapp.php has been intentionally deprecated
            // to prevent duplicate loading of WhatsApp admin routes.
        });
    }
}
