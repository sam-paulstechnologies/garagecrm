<?php

namespace App\Http\MIddleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    // Trust Azure’s front-end proxy/load balancer
    protected $proxies = '*';

    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
