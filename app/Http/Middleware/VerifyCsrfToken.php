<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'public/website-lead/*',

        'webhooks/*',
        'webhooks/twilio/*',
        'webhooks/email/*',
        'webhooks/meta/*',
    ];
}
