<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        'webhooks/twilio/*',
        'webhooks/email/*',
        // 'webhooks/meta/*', // (not needed since we’re on api middleware, but harmless)
    ];
}
