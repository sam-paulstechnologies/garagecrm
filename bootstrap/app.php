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
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Azure App Service terminates TLS at its front end. This remains inert
        // unless the deployment explicitly sets TRUSTED_PROXIES (staging uses *).
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        $middleware->append(\App\Http\Middleware\ApplyStagingIdentity::class);
        $middleware->append(\App\Http\Middleware\ApplySecurityHeaders::class);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnforceRouteCapability::class,
            \App\Http\Middleware\RequireTwoFactorEnrollment::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | CSRF Exceptions
        |--------------------------------------------------------------------------
        | External webhook providers cannot send Laravel CSRF tokens.
        | These endpoints must be protected by their own webhook secret/signature.
        |--------------------------------------------------------------------------
        */
        $middleware->validateCsrfTokens(except: [
            'webhooks/email/inbound',
            // Billing providers authenticate with a signed raw payload, not a
            // browser session. Keep this exception narrower than webhooks/*.
            'webhooks/billing/*',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        */
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'force_password' => \App\Http\Middleware\ForcePasswordChange::class,
            'media_team.scope' => \App\Http\Middleware\EnsureMediaTeamMetaOnly::class,
            'entitled' => \App\Http\Middleware\RequireCapability::class,
            'two_factor.enforced' => \App\Http\Middleware\RequireTwoFactorEnrollment::class,
            'security.step-up' => \App\Http\Middleware\RequireRecentSecurityStepUp::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
