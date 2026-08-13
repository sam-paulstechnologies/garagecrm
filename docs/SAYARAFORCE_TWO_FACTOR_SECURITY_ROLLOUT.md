# SayaraForce TOTP Two-Factor Security Rollout

## Architecture

SayaraForce uses Laravel Fortify's standards-compliant TOTP provider, encrypted secret and recovery-code storage, QR generation, and eight single-use recovery codes. Existing Breeze-style registration, password reset, and session controllers remain authoritative.

Mandatory policy is role based, never plan based. `super_admin`, `platform_admin`, and tenant `admin` users require confirmed TOTP plus acknowledgement that recovery codes were saved. Operational roles may enroll voluntarily. Webhooks, queues, schedulers, and other machine identities are not subject to interactive TOTP.

Fresh password authentication for a user with confirmed 2FA creates only a challenge session. The full authenticated session is created and regenerated only after a valid TOTP or recovery code. Recovery-code authentication does not establish recent step-up status.

## Enforcement modes

- `off`: 2FA is available voluntarily; mandatory-role routing is disabled.
- `audit`: reserved for production inventory and communications; enforcement remains disabled while reporting is reviewed.
- `required_admins`: platform and tenant administrators are restricted to enrollment until 2FA is confirmed.

Staging uses `required_admins`. Production must remain `off` until explicitly approved.

## Step-up and protected operations

Recent security verification requires the current password and a fresh TOTP. It lasts 15 minutes by default and protects billing checkout/cancellation/portal access, WhatsApp number/connect/disconnect/retry operations, user and role mutations, platform-user administration, entitlement/module overrides, garage suspension, and launch-offer control.

There is no trusted-device feature in V1. Every fresh login for an enrolled user challenges 2FA.

## Lost-device recovery

1. The account owner uses one single-use recovery code.
2. If codes are unavailable, a confirmed and recently stepped-up platform administrator may reset a tenant user. A tenant admin may reset only an ordinary user in the same tenant, never another admin or platform user.
3. A reset requires an explicit reason, is audited, invalidates the TOTP secret, recovery codes, and API tokens, and forces fresh enrollment.

No administrator can view an existing seed, QR payload, OTP, or recovery code.

## Production rollout gate

1. Keep `TWO_FACTOR_ENFORCEMENT=off` during deployment and migration.
2. Back up the production database and verify rollback for the additive migration.
3. Inventory platform and tenant admins without retrieving secrets or customer data.
4. Validate at least two controlled platform recovery paths and ownership contacts.
5. Enable `audit`; monitor required-user counts and enrollment readiness.
6. Conduct user communications and supervised enrollment.
7. Obtain explicit production approval.
8. Switch to `required_admins` through the guarded production runtime, verify health/login/enrollment, and retain a rapid rollback to `audit` or `off`.

The additive database migration may remain deployed while enforcement is rolled back. Disabling enforcement does not delete existing 2FA configuration.
