# Current-state security assessment

Assessment date: 2026-08-15. Evidence was gathered from the current `origin/staging` source tree, local automated tests, dependency manifests, and read-only Azure staging inspection.

## Established controls

- Company-scoped route binding, queries, policies and adversarial tenant-isolation tests cover critical CRM, billing, messaging and security paths.
- Fortify password, mandatory admin TOTP, one-time recovery codes, recent step-up, rate limiting, session regeneration and security audit events are implemented.
- Commercial entitlements fail closed and use stable plan/capability identities.
- Stripe Sandbox uses signature validation, test-mode guards, idempotent provider events and mapped internal prices; browser returns do not activate entitlements.
- Meta/WhatsApp webhook verification, asset allow/deny controls, replay/idempotency and provider-controlled identifiers exist.
- App Service is staging-isolated, HTTPS only, TLS 1.2 minimum, FTPS disabled, system managed identity enabled, health checked, with a separate MySQL server, Key Vault, storage and observability.
- Staging outbound WhatsApp and SMS are disabled, mail is logged, the scheduler is disabled, and the queue worker is isolated.

## Remediation implemented in this release

- Private, tenant-scoped upload storage with content/MIME validation and authenticated streaming.
- Security headers, request correlation, cache prevention on sensitive pages and log-context redaction.
- Encryption at rest for provider tokens, a safe legacy encryption command, and removal of tenant-submitted provider secrets/assets.
- OAuth state validation, provider-host restrictions, stricter webhook fail-closed behaviour and endpoint throttling.
- SSRF-resistant remote attachment ingestion and strict size/host/DNS/redirect controls.
- Stored-XSS and unsafe URL sink corrections.
- AI policy failures/unknown decisions route to human handoff rather than autonomous execution.
- Dependency upgrades, zero-known-vulnerability lockfile audits, secret scanning and SBOM generation in CI.

## Residual architecture facts

- Azure platform encryption at rest and TLS in transit are relied upon; application-level encryption is applied to high-risk tokens and selected quarantined content. The product must not be described as end-to-end encrypted.
- Staging MySQL has seven-day backups but no geo-redundant backup or HA. The production design needs an approved RPO/RTO and independently witnessed restore evidence.
- Key Vault, storage and telemetry public network endpoints remain enabled in staging. Access is credential/RBAC controlled, but private endpoints and network segmentation are a recommended production treatment.
- App Service client affinity is enabled in the current staging resource. Database sessions make it unnecessary and it should be disabled in an approved infrastructure change.
- Supplier legal terms, UAE privacy assessment, data-processing agreements and retention schedules require counsel/owner approval.
- No independent penetration test or ISO certification audit has been performed.
