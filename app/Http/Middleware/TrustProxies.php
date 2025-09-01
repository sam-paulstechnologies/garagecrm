<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Trust all proxies (safe on Azure App Service fronted by its load balancer).
     * If you later front this with a specific proxy list, replace '*' with the IPs.
     */
    protected $proxies = '*';

    /**
     * Headers used to detect proxies and protocol.
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
