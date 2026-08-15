<?php

namespace App\Security;

/**
 * Fable remediation (M3): builds a real Content-Security-Policy from config.
 *
 * Shipped Report-Only by default so the strong policy (default-src/script-src)
 * can be validated against the live app — including the Meta Embedded Signup
 * inline scripts — before being enforced. Flip security.csp.enforce to move it
 * to the enforcing Content-Security-Policy header.
 */
class ContentSecurityPolicy
{
    public function enabled(): bool
    {
        return (bool) config('security.csp.enabled', false);
    }

    public function enforce(): bool
    {
        return (bool) config('security.csp.enforce', false);
    }

    public function headerName(): string
    {
        return $this->enforce() ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
    }

    public function policyString(): string
    {
        $directives = (array) config('security.csp.directives', []);
        $parts = [];

        foreach ($directives as $name => $value) {
            $value = trim((string) $value);
            $parts[] = $value === '' ? $name : "{$name} {$value}";
        }

        $append = trim((string) config('security.csp.append', ''));
        if ($append !== '') {
            $parts[] = $append;
        }

        $reportUri = trim((string) config('security.csp.report_uri', ''));
        if ($reportUri !== '') {
            $parts[] = "report-uri {$reportUri}";
        }

        return implode('; ', $parts);
    }
}
