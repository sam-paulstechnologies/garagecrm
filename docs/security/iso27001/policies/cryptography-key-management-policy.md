# Cryptography and key-management policy

- TLS 1.2 or stronger is required for external/application/database/provider transport. Certificate validation must not be disabled.
- Restricted application secrets reside in Key Vault and enter App Service via managed references. Do not print values; diagnostics report resolution/class only.
- Provider tokens stored in application tables use framework authenticated encryption and are hidden from serialization. Passwords remain one-way hashed.
- Separate secrets/keys exist per environment. Production material is never copied to staging.
- Key creation uses provider/CSPRNG mechanisms; access follows least privilege. Rotation is required on compromise, staff/vendor transition, provider policy or scheduled review.
- Rotation records owner, secret name (not value), systems, timestamp, validation and rollback. Old values are revoked after verified cutover.
- Backups and logs inherit platform encryption/access controls. Exported evidence excludes keys and plaintext credentials.
- Algorithms and libraries are maintained through supported frameworks/providers; proprietary cryptography is prohibited.

Key-compromise suspicion triggers incident response, rotation and impact review.
