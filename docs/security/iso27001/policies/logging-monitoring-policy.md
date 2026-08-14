# Logging and monitoring policy

Log security-relevant outcome and context: timestamp, environment, correlation ID, actor/tenant where appropriate, event type, target class/ID, result and safe reason code.

Never log passwords, TOTP/recovery codes, session/cookies, authorization headers, secrets/tokens, QR payloads, full provider payloads, full message/customer content or unmasked phone/email values. Global redaction is defence in depth, not permission to log secrets.

Monitor authentication/2FA throttling, role/access change, admin reset/impersonation, provider signature/replay failures, unknown/denied assets, billing reconciliation, outbound safety blocks, malware/upload rejection, queue/health failure, dependency/secret scan failure and Key Vault/reference status.

Staging retention is currently Application Insights 90 days and Log Analytics 30 days. Production retention and alert routing require business/legal approval. Clock sources use platform time. Access is RBAC restricted and reviewed quarterly. Suspected tampering or high-severity signal invokes incident response.
