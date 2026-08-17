# Incident scenario playbooks

These scenario playbooks extend `incident-response-plan.md`. Follow the base plan's
severity model, roles, evidence-handling and closure steps; the scenarios below add
threat-specific detect / contain / rotate / scope / restore / communicate actions.

Ground rules for every scenario:

- Do **not** put secrets, customer rows or provider payloads in tickets or chat.
  Use the restricted incident/assurance channel.
- Preserve evidence before eradication: snapshot logs/config/version/access history
  with checksums and a custody record; prefer read-only provider/cloud queries.
- **Legal/regulatory notification obligations and deadlines are jurisdiction-specific
  and are LEGAL REVIEW REQUIRED.** Engineering does not decide or assert notification
  timing. Do not copy any statutory deadline into these runbooks.
- "Rotate" means generate a new secret, deploy it, then invalidate the old one and
  confirm the old value fails.

---

## 1. Meta App Secret exposure

- **Detect:** secret found in logs/repo/screenshot/paste; unexpected Graph API calls;
  secret-scan hit (`ops/security/scan-secrets.php`); Meta developer alert.
- **Contain:** treat the App Secret as compromised; disable affected webhook
  subscriptions if abuse is active; enable stricter webhook fail-closed.
- **Preserve evidence:** capture where the secret was exposed, exposure window, and
  any Graph API access logs (read-only).
- **Revoke/rotate:** reset the Meta App Secret in the Meta App dashboard; update the
  Key Vault reference; redeploy so the app loads the new value; reconfirm webhook
  signature validation with the new secret.
- **Assess scope:** review WhatsApp/Meta webhook and message audit for
  unauthorized asset registration, message sends or subscription changes.
- **Restore:** re-enable subscriptions once the new secret is confirmed; verify
  signature validation and asset allow/deny controls pass.
- **Communicate internally:** notify integration owner and security via restricted
  channel with exposure window and rotation confirmation.
- **Provider escalation:** open a Meta developer support/security case if fraudulent
  API activity is observed.
- **Post-incident review:** root cause of exposure, secret-handling hardening, add
  detection for the exposure vector.

## 2. Cross-tenant data disclosure

- **Detect:** cross-tenant adversarial test failure; a tenant reports foreign data;
  audit log shows access to a `company_id` other than the actor's; anomaly in scoped
  query results.
- **Contain:** disable the affected route/feature or the offending user session; do
  **not** perform destructive cleanup; preserve availability for unaffected tenants.
- **Preserve evidence:** capture the request (correlation ID), the leaking query/route,
  the actor identity and the set of tenants/records potentially exposed.
- **Revoke/rotate:** revoke sessions of any actor who exploited the gap; force
  re-auth; if credentials may have been captured, rotate them.
- **Assess scope:** enumerate which tenants and record types were reachable and for
  how long; confirm whether data was actually read or only reachable.
- **Restore:** deploy the scope fix; add/repair the automated tenant-isolation
  backstop (global scope/guard) plus a regression test reproducing the gap.
- **Communicate internally:** security owner + engineering; prepare a factual scope
  summary for privacy/legal — do not assert "no impact" before evidence supports it.
- **Provider escalation:** none (internal), unless data reached a third party.
- **Post-incident review:** why the scope check was missed; strengthen the backstop;
  extend adversarial suite to the affected path.

## 3. Stripe webhook-secret compromise

- **Detect:** signing-secret exposure; unexpected entitlement/plan changes;
  signature-validation failures spike; Stripe dashboard alert.
- **Contain:** the webhook already validates signatures and uses an idempotency
  ledger and server-side price mapping; if forged events are suspected, tighten to
  fail-closed and pause automated entitlement changes if needed.
- **Preserve evidence:** capture recent provider events, idempotency ledger entries
  and any entitlement changes in the exposure window.
- **Revoke/rotate:** roll the Stripe webhook signing secret in the Stripe dashboard;
  update the Key Vault reference and redeploy; confirm old-secret events are rejected.
- **Assess scope:** reconcile internal entitlements against Stripe as source of truth;
  identify any entitlement granted without a genuine signed event.
- **Restore:** reverse/reconcile any illegitimate entitlement change; resume normal
  processing after the new secret is confirmed.
- **Communicate internally:** commercial owner + security; billing reconciliation
  summary.
- **Provider escalation:** contact Stripe support if fraudulent events originated
  outside a rotation you controlled.
- **Post-incident review:** secret handling, reconciliation cadence, alerting on
  signature-failure spikes.

## 4. Privileged account takeover

- **Detect:** unexpected privileged login/geo/device; security-audit events for
  role change, 2FA reset or admin action; step-up challenges failing then succeeding.
- **Contain:** disable the affected account; invalidate its sessions and tokens
  (`SecuritySessionInvalidator`); block further privileged actions by that identity.
- **Preserve evidence:** capture the security audit trail, source IPs/devices, and
  every privileged action taken during the suspected window.
- **Revoke/rotate:** force password reset and 2FA re-enrolment; rotate any secret the
  account could read; review recovery-code usage.
- **Assess scope:** list all administrative actions, data accessed, tenants touched
  and any created/altered accounts or roles.
- **Restore:** restore correct roles/permissions; re-enable the account only after
  identity is re-verified; confirm mandatory MFA is enforced (fail-closed state).
- **Communicate internally:** security owner immediately (SEV-1); factual scope for
  privacy/legal.
- **Provider escalation:** if the identity provider or email account was the entry
  vector, escalate to that provider.
- **Post-incident review:** entry vector, MFA/step-up gaps, privileged-access review
  cadence, consider PIM/JIT.

## 5. Quick Scan deletion failure

- **Detect:** `PurgeQuickScan` job failures/backlog; Quick Scan records past their
  retention window; reconciliation mismatch across Quick Scan stores.
- **Contain:** pause new Quick Scan intake if purge is broken to avoid growing
  retained PII; do not hard-delete manually without a custody record.
- **Preserve evidence:** capture job logs, failure cause, and the count/age of
  records that should have purged.
- **Revoke/rotate:** n/a (data-lifecycle incident) unless a credential caused it.
- **Assess scope:** identify which Quick Scan candidates/messages/events/sessions
  were retained beyond policy and for how long.
- **Restore:** fix the purge job; re-run reconciliation so all Quick Scan stores
  match the retention policy; verify no orphaned PII remains.
- **Communicate internally:** privacy owner + engineering; retention-breach summary.
- **Provider escalation:** none, unless retained data was shared externally.
- **Post-incident review:** why purge failed, add purge-success monitoring and a
  reconciliation check; over-retention notification is **LEGAL REVIEW REQUIRED**.

## 6. Azure credential compromise

- **Detect:** unexpected Azure sign-in/activity; unrecognized role assignment or
  resource change; Azure security alert; managed-identity anomaly.
- **Contain:** the compromised subscription Owner is a single personal account
  (see R-016) — treat as SEV-1; disable/lock the account; revoke active sessions;
  if abuse is active, apply resource locks to critical resources.
- **Preserve evidence:** export Azure activity logs and role-assignment history
  (read-only) before making changes.
- **Revoke/rotate:** reset the account credential and MFA; rotate any secret the
  identity could read (Key Vault, deploy credentials, storage keys); reissue
  managed-identity-based access as needed.
- **Assess scope:** enumerate resources, secrets and data planes reachable by the
  identity; check for created backdoor identities, federated credentials or role
  grants.
- **Restore:** remove unauthorized changes; restore least-privilege RBAC; re-enable
  the account only after identity re-verification.
- **Communicate internally:** executive + operations + security immediately.
- **Provider escalation:** open a Microsoft Azure support/security case for confirmed
  compromise.
- **Post-incident review:** drive R-016 remediation — split ownership, least-privilege
  RBAC, PIM/JIT, break-glass; add alerting on role-assignment changes.

## 7. Database compromise

- **Detect:** anomalous DB queries/connections; unexpected schema/data changes;
  MySQL access from an unexpected network/identity; integrity-check mismatch.
- **Contain:** isolate the database (network/firewall), revoke the suspected
  credential, and stop workers/app writes if active tampering is suspected — preserve
  data for forensics, do not wipe.
- **Preserve evidence:** snapshot the database and relevant logs with checksums and a
  custody record before remediation.
- **Revoke/rotate:** rotate DB credentials and any application secret with DB access;
  reissue via Key Vault + managed identity; confirm old credentials fail.
- **Assess scope:** determine which tables/tenants were read or altered; check for
  exfiltration indicators; note that fields under application-level encryption are not
  readable without the `APP_KEY`.
- **Restore:** restore from a verified backup to an isolated target; **decrypt
  application-encrypted fields with the restored `APP_KEY`/Key Vault to confirm the
  restore is usable (R-014, currently unproven)**; reconcile integrity before cutover.
- **Communicate internally:** security + operations (SEV-1); factual scope for
  privacy/legal.
- **Provider escalation:** Azure support for platform-level compromise.
- **Post-incident review:** entry vector, network segmentation/private-endpoint
  treatment, credential handling, restore-decryption evidence.

## 8. Lost/invalid APP_KEY or inability to decrypt application fields

- **Detect:** decrypt exceptions across encrypted casts; app errors reading provider
  tokens/quarantined content; post-restore data unreadable; `APP_KEY` missing/changed.
- **Contain:** stop writes that would encrypt with a wrong/new key (avoid mixing
  ciphertext under two keys); put affected features into a safe/degraded mode rather
  than looping on decrypt failures.
- **Preserve evidence:** record which key was expected, what is configured now, and
  which records/fields fail to decrypt.
- **Revoke/rotate:** if the correct key still exists in Key Vault or a secure backup,
  restore it to configuration; do **not** rotate `APP_KEY` before recovery — a new key
  cannot decrypt existing ciphertext.
- **Assess scope:** enumerate affected encrypted fields (provider tokens, quarantined
  content, other casts) and dependent features.
- **Restore:** with the correct key restored, verify decryption of a sample of each
  encrypted field type; if the key is unrecoverable, affected ciphertext is
  unrecoverable — plan credential re-collection (e.g. re-authorize provider tokens)
  and document data loss.
- **Communicate internally:** engineering + security + operations; if provider tokens
  must be re-collected, coordinate with integration owner.
- **Provider escalation:** none for the key itself; re-authorization may require
  provider (Meta/Stripe) flows.
- **Post-incident review:** key custody and backup, Key Vault availability, add a
  startup/health check that fails loudly on a wrong/missing `APP_KEY`; feed into the
  R-014 restore-decryption evidence gap.

---

Jurisdiction-specific breach-notification obligations for all scenarios above are
**LEGAL REVIEW REQUIRED** and are decided by privacy/legal, not engineering.
Maintain contacts, provider case channels and break-glass details outside this
repository. Exercise these scenarios in tabletops at least annually and after
material architecture change.
