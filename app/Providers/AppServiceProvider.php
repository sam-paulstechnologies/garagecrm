<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use App\View\Components\AppLayout;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::component('app-layout', AppLayout::class);

        // Force HTTPS in production (behind proxy)
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Ensure cURL/OpenSSL use the configured CA bundle (web + CLI)
        $ca = config('services.curl_ca_bundle');
        if (is_string($ca) && $ca !== '' && @is_file($ca)) {
            @putenv("CURL_CA_BUNDLE={$ca}");
            @putenv("SSL_CERT_FILE={$ca}");
            @ini_set('curl.cainfo', $ca);
            @ini_set('openssl.cafile', $ca);
        }
    }
}
