<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ApplySecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->requestId($request);
        Log::withContext(['request_id' => $requestId]);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        $policy = trim((string) config('security.content_security_policy'));
        if ($policy !== '') {
            $response->headers->set('Content-Security-Policy', $policy);
        }

        if ($request->isSecure()) {
            $maxAge = max(0, (int) config('security.hsts_max_age', 31536000));
            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}; includeSubDomains");
        }

        if ($request->is('login', 'register', 'forgot-password', 'reset-password/*', 'two-factor-challenge', 'security/*', 'admin/billing*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    private function requestId(Request $request): string
    {
        $provided = trim((string) $request->headers->get('X-Request-ID'));

        if ($provided !== '' && preg_match('/\A[a-zA-Z0-9._:-]{8,80}\z/', $provided) === 1) {
            return $provided;
        }

        return (string) Str::uuid();
    }
}
