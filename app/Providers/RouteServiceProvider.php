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

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        */
        if (class_exists(\App\Http\Middleware\EnsureUserIsActive::class)) {
            $router->aliasMiddleware('active', \App\Http\Middleware\EnsureUserIsActive::class);
        }

        if (class_exists(\App\Http\Middleware\ForcePasswordChange::class)) {
            $router->aliasMiddleware('force_password', \App\Http\Middleware\ForcePasswordChange::class);
        }

        if (class_exists(\App\Http\Middleware\RoleMiddleware::class)) {
            $router->aliasMiddleware('role', \App\Http\Middleware\RoleMiddleware::class);
        }

        $this->routes(function () {

            /*
            |--------------------------------------------------------------------------
            | API Routes
            |--------------------------------------------------------------------------
            | routes/api.php already uses Route::prefix('v1'), so we add only /api here.
            | Final URL example: /api/v1/webhooks/meta/leads
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/api.php'))) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Main Web Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/web.php'))) {
                Route::middleware('web')
                    ->group(base_path('routes/web.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Admin Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/admin.php'))) {
                Route::middleware($this->protectedWebMiddleware())
                    ->group(base_path('routes/admin.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Manager Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/manager.php'))) {
                Route::middleware($this->protectedWebMiddleware())
                    ->group(base_path('routes/manager.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Tenant Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/tenant.php'))) {
                Route::middleware($this->protectedWebMiddleware())
                    ->group(base_path('routes/tenant.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Mechanic Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/mechanic.php'))) {
                Route::middleware($this->protectedWebMiddleware())
                    ->group(base_path('routes/mechanic.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | WhatsApp Admin/Web Routes
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/whatsapp.php'))) {
                Route::middleware($this->protectedWebMiddleware())
                    ->group(base_path('routes/whatsapp.php'));
            }

            /*
            |--------------------------------------------------------------------------
            | Public Webhooks
            |--------------------------------------------------------------------------
            */
            if (is_file(base_path('routes/webhooks.php'))) {
                Route::middleware([])
                    ->group(base_path('routes/webhooks.php'));
            }
        });
    }

    private function protectedWebMiddleware(): array
    {
        $middleware = ['web', 'auth'];

        if (class_exists(\App\Http\Middleware\EnsureUserIsActive::class)) {
            $middleware[] = 'active';
        }

        if (class_exists(\App\Http\Middleware\ForcePasswordChange::class)) {
            $middleware[] = 'force_password';
        }

        return $middleware;
    }
}