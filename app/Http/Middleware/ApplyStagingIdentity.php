<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyStagingIdentity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('staging')) {
            return $next($request);
        }

        if ($request->is('robots.txt')) {
            return response("User-agent: *\nDisallow: /\n", 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        if ($request->is('admin/whatsapp/connect*')
            || $request->is('admin/whatsapp/embedded-signup*')
            || $request->is('admin/whatsapp/disconnect')) {
            abort(403, 'Legacy production WhatsApp controls are disabled in staging.');
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        if (! auth()->check() || ! str_contains($contentType, 'text/html') || ! method_exists($response, 'getContent')) {
            return $response;
        }

        $content = (string) $response->getContent();
        if ($content === '' || str_contains($content, 'id="sayaraforce-staging-banner"')) {
            return $response;
        }

        $content = preg_replace(
            '/<title([^>]*)>.*?<\/title>/is',
            '<title$1>[STAGING] SayaraForce &#8212; Test Data Only</title>',
            $content,
            1
        ) ?? $content;

        $headMarker = '<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Crect width=%2764%27 height=%2764%27 rx=%2712%27 fill=%27%23f97316%27/%3E%3Ctext x=%2732%27 y=%2743%27 text-anchor=%27middle%27 font-size=%2736%27 font-family=%27Arial%27 fill=%27white%27%3ES%3C/text%3E%3C/svg%3E">';
        $content = preg_replace('/<\/head>/i', $headMarker."\n</head>", $content, 1) ?? $content;

        $banner = '<style id="sayaraforce-staging-style">body{padding-top:42px!important}#sayaraforce-staging-banner{position:fixed;inset:0 0 auto 0;z-index:2147483647;background:#c2410c;color:#fff;text-align:center;padding:11px 16px;font:800 13px/20px system-ui,sans-serif;letter-spacing:.08em;box-shadow:0 2px 8px rgba(0,0,0,.35)}</style><div id="sayaraforce-staging-banner" role="status">STAGING ENVIRONMENT &#8212; TEST DATA ONLY</div>';
        $content = preg_replace('/<body([^>]*)>/i', '<body$1>'.$banner, $content, 1) ?? $content;
        $response->setContent($content);

        return $response;
    }
}
