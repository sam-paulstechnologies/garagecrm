<?php

namespace Tests\Feature\Security;

use App\Security\SecurityConfigurationValidator;
use Tests\TestCase;

class SecurityConfigurationValidatorTest extends TestCase
{
    private function validator(): SecurityConfigurationValidator
    {
        return app(SecurityConfigurationValidator::class);
    }

    private function asProtectedEnvironment(): void
    {
        // Simulate a deployed (non-local/testing) environment.
        $this->app->detectEnvironment(fn () => 'staging');
    }

    public function test_testing_environment_honours_configured_mode(): void
    {
        config(['security.two_factor_enforcement' => 'required_admins']);
        $this->assertTrue($this->validator()->privilegedMfaMandatory());

        config(['security.two_factor_enforcement' => 'off']);
        $this->assertFalse($this->validator()->privilegedMfaMandatory());
        $this->assertFalse($this->validator()->sensitiveActionsRequireVerification());
    }

    public function test_protected_environment_with_required_admins_is_ok(): void
    {
        $this->asProtectedEnvironment();
        config(['security.two_factor_enforcement' => 'required_admins']);

        $v = $this->validator();
        $this->assertTrue($v->privilegedMfaMandatory());
        $this->assertTrue($v->sensitiveActionsRequireVerification());
        $this->assertTrue($v->baselineSatisfied());
        $this->assertSame('ok', $v->status());
    }

    public function test_protected_environment_with_unset_enforcement_fails_closed(): void
    {
        $this->asProtectedEnvironment();
        config(['security.two_factor_enforcement' => null]);

        $v = $this->validator();
        $this->assertTrue($v->privilegedMfaMandatory(), 'Unset enforcement must fail closed to mandatory.');
        $this->assertTrue($v->sensitiveActionsRequireVerification());
        $this->assertFalse($v->baselineSatisfied());
        $this->assertTrue($v->isFailingClosedDueToMisconfiguration());
        $this->assertSame('critical', $v->status());
    }

    public function test_protected_environment_with_off_fails_closed(): void
    {
        $this->asProtectedEnvironment();
        config(['security.two_factor_enforcement' => 'off']);

        $v = $this->validator();
        $this->assertTrue($v->privilegedMfaMandatory());
        $this->assertTrue($v->sensitiveActionsRequireVerification());
        $this->assertSame('critical', $v->status());
    }

    public function test_protected_environment_with_unknown_value_fails_closed(): void
    {
        $this->asProtectedEnvironment();
        config(['security.two_factor_enforcement' => 'totally-invalid']);

        $v = $this->validator();
        $this->assertFalse($v->configuredModeIsValid());
        $this->assertTrue($v->privilegedMfaMandatory());
        $this->assertSame('critical', $v->status());
    }

    public function test_protected_environment_explicit_audit_is_degraded_not_enforced(): void
    {
        $this->asProtectedEnvironment();
        config(['security.two_factor_enforcement' => 'audit']);

        $v = $this->validator();
        $this->assertFalse($v->privilegedMfaMandatory(), 'Explicit audit stage is a recognised non-enforcing choice.');
        $this->assertFalse($v->sensitiveActionsRequireVerification());
        $this->assertFalse($v->isFailingClosedDueToMisconfiguration());
        $this->assertSame('degraded', $v->status());
    }

    public function test_healthz_security_check_reports_critical_as_503(): void
    {
        $this->asProtectedEnvironment();
        config(['security.two_factor_enforcement' => null]);

        $this->getJson('/healthz?check=security')
            ->assertStatus(503)
            ->assertJsonPath('status', 'critical');
    }

    public function test_healthz_liveness_still_ok(): void
    {
        $this->get('/healthz')->assertOk()->assertSee('OK');
    }
}
