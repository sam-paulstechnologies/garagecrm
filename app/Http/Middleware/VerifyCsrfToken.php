<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * You can use wildcards like 'webhooks/*' if you prefer.
     */
    protected $except = [
        'webhooks/twilio/whatsapp',
        'webhooks/email/inbound',
        // 'webhooks/*', // <- alternative wildcard
    ];
}
