<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    public function test_report_only_strong_policy_is_sent_by_default(): void
    {
        config(['security.csp.enabled' => true, 'security.csp.enforce' => false]);

        $response = $this->get('/login');

        $reportOnly = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertNotNull($reportOnly);
        $this->assertStringContainsString("default-src 'self'", $reportOnly);
        $this->assertStringContainsString("script-src 'self'", $reportOnly);

        // Baseline enforced protections remain (no regression).
        $enforced = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($enforced);
        $this->assertStringContainsString("object-src 'none'", $enforced);
        $this->assertStringContainsString("frame-ancestors 'self'", $enforced);
    }

    public function test_enforce_mode_sends_strong_enforced_policy(): void
    {
        config(['security.csp.enabled' => true, 'security.csp.enforce' => true]);

        $response = $this->get('/login');

        $enforced = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($enforced);
        $this->assertStringContainsString("default-src 'self'", $enforced);
        $this->assertStringContainsString('upgrade-insecure-requests', $enforced);
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_x_powered_by_is_not_disclosed(): void
    {
        $response = $this->get('/login');
        $this->assertNull($response->headers->get('X-Powered-By'));
    }
}
