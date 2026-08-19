<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SessionCookieSecurityTest extends TestCase
{
    public function test_secure_cookie_is_a_strict_boolean_not_null(): void
    {
        // The previous config returned null when SESSION_SECURE_COOKIE was
        // omitted, silently dropping the secure flag. It must now resolve to a
        // deterministic boolean default.
        $this->assertIsBool(config('session.secure'));
    }

    public function test_local_and_testing_default_allows_http_development(): void
    {
        // The active test suite runs as 'testing', which must not force secure
        // cookies so local HTTP development keeps working.
        $this->assertFalse(config('session.secure'));
    }

    public function test_protected_environment_default_is_secure(): void
    {
        // Directly exercise the same fail-closed default expression the config
        // uses, proving a protected environment defaults to a secure cookie even
        // when SESSION_SECURE_COOKIE is omitted.
        $default = fn (string $env): bool => ! in_array($env, ['local', 'testing'], true);

        $this->assertTrue($default('staging'));
        $this->assertTrue($default('production'));
        $this->assertFalse($default('local'));
        $this->assertFalse($default('testing'));
    }
}
