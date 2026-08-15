<?php

namespace App\Security;

use Illuminate\Support\Facades\App;

/**
 * Fable remediation (H2): mandatory MFA / step-up must not silently fail open.
 *
 * The previous design mapped an absent/unknown TWO_FACTOR_ENFORCEMENT value to
 * "off", so a protected environment that lost the setting would silently drop
 * mandatory 2FA and step-up re-authentication with no other signal.
 *
 * This validator centralises the decision and fails CLOSED in protected
 * (non-local/testing) environments:
 *
 *   - required_admins : enforce (healthy baseline).
 *   - audit           : an EXPLICIT, recognised non-enforcing rollout stage —
 *                       respected, but reported as "degraded".
 *   - off / unset / unknown (protected env): treated as a baseline failure and
 *                       FAIL CLOSED — privileged roles are forced to enrol and
 *                       sensitive actions require verification — while readiness
 *                       reports the invalid state.
 *
 * Local/testing environments honour the configured value verbatim so the test
 * suite and developer workflow are not disrupted.
 *
 * Non-privileged routes are never bricked: a privileged user without 2FA is
 * redirected to enrolment, not hard-failed.
 */
class SecurityConfigurationValidator
{
    public const VALID_MODES = ['off', 'audit', 'required_admins'];

    public function rawEnforcement(): ?string
    {
        $value = config('security.two_factor_enforcement');

        if ($value === null) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }

    public function configuredModeIsValid(): bool
    {
        return in_array($this->rawEnforcement(), self::VALID_MODES, true);
    }

    public function environmentIsProtected(): bool
    {
        return ! App::environment(['local', 'testing']);
    }

    /**
     * Whether privileged roles MUST have confirmed 2FA in this environment.
     */
    public function privilegedMfaMandatory(): bool
    {
        if (! $this->environmentIsProtected()) {
            return $this->rawEnforcement() === 'required_admins';
        }

        // Protected env: enforce unless the operator explicitly chose the
        // recognised non-enforcing "audit" stage. off/unset/unknown fail closed.
        return $this->rawEnforcement() !== 'audit';
    }

    /**
     * Whether sensitive (step-up protected) actions must require recent
     * verification instead of silently passing through.
     */
    public function sensitiveActionsRequireVerification(): bool
    {
        if (! $this->environmentIsProtected()) {
            return $this->rawEnforcement() === 'required_admins';
        }

        return $this->rawEnforcement() !== 'audit';
    }

    public function baselineSatisfied(): bool
    {
        if (! $this->environmentIsProtected()) {
            return true;
        }

        return $this->rawEnforcement() === 'required_admins';
    }

    /**
     * A protected environment running below baseline through an INVALID value
     * (unset/unknown/off) — the silent-fail-open case H2 targets.
     */
    public function isFailingClosedDueToMisconfiguration(): bool
    {
        if (! $this->environmentIsProtected()) {
            return false;
        }

        return ! in_array($this->rawEnforcement(), ['required_admins', 'audit'], true);
    }

    public function status(): string
    {
        if ($this->baselineSatisfied()) {
            return 'ok';
        }

        if ($this->environmentIsProtected() && $this->rawEnforcement() === 'audit') {
            return 'degraded';
        }

        return 'critical';
    }

    /**
     * Non-secret readiness snapshot for health/monitoring surfaces.
     *
     * @return array<string,mixed>
     */
    public function readiness(): array
    {
        return [
            'two_factor_enforcement' => $this->rawEnforcement() ?? '(unset)',
            'configured_mode_valid' => $this->configuredModeIsValid(),
            'environment_protected' => $this->environmentIsProtected(),
            'privileged_mfa_mandatory' => $this->privilegedMfaMandatory(),
            'step_up_enforced' => $this->sensitiveActionsRequireVerification(),
            'baseline_satisfied' => $this->baselineSatisfied(),
            'status' => $this->status(),
        ];
    }

    public function readinessMessage(): string
    {
        return match ($this->status()) {
            'ok' => 'Security baseline satisfied (privileged MFA enforced).',
            'degraded' => 'Security baseline in explicit audit mode; privileged MFA not enforced.',
            default => 'Security baseline NOT satisfied: two-factor enforcement is unset/invalid in a protected environment. Failing closed for privileged and sensitive actions.',
        };
    }
}
