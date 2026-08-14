# Access control policy

- Every user has a unique identity; shared interactive administrator accounts are prohibited.
- Platform super-admin and tenant admin require confirmed TOTP. Sensitive billing, Meta/WhatsApp, security, user-role and impersonation actions require recent step-up.
- Service/webhook identities use tokens, signatures or managed identity and never interactive TOTP.
- Tenant access is always company-scoped server-side. UI hiding is not access control. Super-admin access is explicit, audited and does not silently inherit tenant secrets.
- Azure access uses Entra/RBAC and managed identity. Application managed identity receives only the staging Key Vault secret-read role required. Avoid owner/contributor for runtime identity.
- Access requests require owner approval, purpose, least-privilege role and expiry where temporary. Review privileged access quarterly and all access at least every six months.
- Termination/offboarding revokes application, repository, Azure, supplier and recovery access promptly and rotates shared/exposed credentials.
- Emergency access is time-bound, logged, post-reviewed and tested without revealing secrets.

Evidence: user/role/2FA inventory, Azure/repository/supplier role exports, approvals, revocations and review sign-off.
