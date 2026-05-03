<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Application Timezone
|--------------------------------------------------------------------------
| Forces Laravel runtime/PHP date functions to use Dubai timezone.
| Also add APP_TIMEZONE=Asia/Dubai in your .env file.
|--------------------------------------------------------------------------
*/

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Dubai'));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 🔥 CRITICAL FIX (ROLE + ACTIVE + FORCE PASSWORD)
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'force_password' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();