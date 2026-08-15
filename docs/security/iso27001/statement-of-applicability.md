# Statement of Applicability (working draft)

This is a management-system implementation aid, not a certification statement. Applicability and status require management approval and independent review. `Implemented` means repository/staging evidence exists; `Partial` means treatment/evidence remains; `Planned` is not implemented.

| ISO/IEC 27001:2022 Annex A control | Applicable | Status | Rationale / evidence |
|---|---|---|---|
| A.5.1 Policies for information security | Yes | Implemented | policy set in this pack |
| A.5.2 Roles and responsibilities | Yes | Partial | roles documented; named appointments/sign-off required |
| A.5.3 Segregation of duties | Yes | Partial | tenant/platform application separation, step-up; formal release approver evidence required. **MANAGEMENT ACTION REQUIRED:** a single personal account currently holds Azure subscription Owner across both production and staging (`cd04aa2d…`), with no PIM/just-in-time elevation — a duty-concentration gap disclosed in the risk register (R-016) |
| A.5.7 Threat intelligence | Yes | Partial | dependency advisories/audits; broader threat-intel process planned |
| A.5.8 Security in project management | Yes | Implemented | staging-first gates and secure SDLC policy |
| A.5.9 Inventory of information/assets | Yes | Implemented | asset register |
| A.5.10 Acceptable use | Yes | Partial | policy drafted; personnel acknowledgement required |
| A.5.12–A.5.14 Classification/transfer | Yes | Partial | classification and data-flow registers; transfer procedure training required |
| A.5.15–A.5.18 Access/identity/authentication/access review | Yes | Partial | RBAC, 2FA, step-up, managed identity; recurring review evidence required |
| A.5.19–A.5.23 Supplier/cloud security | Yes | Partial | supplier register and policy; contracts/DPA review open. **LEGAL/MANAGEMENT ACTION REQUIRED:** no signed OpenAI data-processing agreement is in place. Prompt PII minimization (name/phone/email stripped before send — `App\Services\Ai\PromptPiiMinimizer`) reduces but does not eliminate the residual supplier-processing risk (R-018) |
| A.5.24–A.5.28 Incident planning/response/evidence | Yes | Partial | IR runbook and audit logs; exercise required |
| A.5.29–A.5.30 Disruption/ICT readiness | Yes | Partial | DR/BC plans; restore/failover evidence open |
| A.5.31–A.5.34 Legal/privacy/records | Yes | Partial | registers/minimisation; counsel decisions and complete retention workflow open |
| A.5.35–A.5.36 Independent review/compliance | Yes | Planned | external VAPT/certification review not performed |
| A.5.37 Documented procedures | Yes | Implemented | runbooks/policies/change gates |
| A.6.1–A.6.8 People controls | Yes | Partial | technical reporting channels; HR screening/terms/training/offboarding evidence external |
| A.7.1–A.7.14 Physical controls | Yes | Partial/Shared | Azure datacentre controls are supplier-responsibility; office/device controls require organisation evidence |
| A.8.1 Endpoint devices | Yes | Partial | acceptable-use requirement; MDM/device evidence open |
| A.8.2 Privileged access | Yes | Implemented | role isolation, mandatory 2FA/step-up, auditing |
| A.8.3 Information access restriction | Yes | Implemented | tenant scopes, policies, entitlements. All live uploads use `App\Security\Uploads\PrivateUploadStorage` (private disk + authorized streamed downloads); a dead public-disk `Tenant\JobCardController` (used `Storage::disk('public')`) was removed so no live path writes customer files to a public disk |
| A.8.4 Source-code access | Yes | Partial | repository controls assumed; branch-protection/admin inventory evidence required |
| A.8.5 Secure authentication | Yes | Implemented | Fortify/TOTP/throttling; sessions and tokens are invalidated on credential/security change (`App\Security\SecuritySessionInvalidator`). Mandatory privileged MFA now fails closed in protected environments — an unset/invalid `TWO_FACTOR_ENFORCEMENT` forces enrolment/step-up instead of silently disabling MFA (`App\Security\SecurityConfigurationValidator`) |
| A.8.6 Capacity management | Yes | Partial | App Service/telemetry; alert thresholds and load test open |
| A.8.7 Malware protection | Yes | Partial | upload allowlist/magic MIME; independent malware scanning for documents planned |
| A.8.8 Vulnerability management | Yes | Implemented/Partial | lockfile audits/secret scan/SBOM; external VAPT open |
| A.8.9 Configuration management | Yes | Implemented | Bicep, env guard, staging verification |
| A.8.10 Information deletion | Yes | Partial | Quick Scan/history purge; full tenant/DSAR deletion open |
| A.8.11 Data masking | Yes | Implemented | masked phones/provider references and synthetic evidence |
| A.8.12 Data leakage prevention | Yes | Partial | log redaction/private storage/secret scan; enterprise DLP external |
| A.8.13 Information backup | Yes | Partial | Azure backup configured; an isolated restore target exists and a restore was executed. **PENDING:** decryption of application-encrypted fields after restore (correct `APP_KEY`/Key Vault availability) must be separately evidenced before recovery can be claimed usable (R-014) |
| A.8.14 Redundancy | Yes | Partial | staging intentionally lacks HA; production decision required |
| A.8.15–A.8.17 Logging/monitoring/clock | Yes | Partial | audit/app telemetry and platform time; alert/SIEM procedures need exercise |
| A.8.18 Privileged utilities | Yes | Implemented | environment/target guards on destructive/admin commands |
| A.8.19 Software installation | Yes | Partial | lockfiles/CI; workstation policy evidence open |
| A.8.20–A.8.22 Network security/segregation | Yes | Partial | isolated resources/TLS; private endpoints open |
| A.8.23 Web filtering | Contextual | Partial | server-side outbound host allowlists; workforce web filtering external |
| A.8.24 Cryptography | Yes | Implemented/Partial | TLS/KV/encrypted fields; key lifecycle evidence needs recurring review. **GAP:** Key Vault secrets have no expiry set and no documented rotation/lifecycle governance (R-019); application-field decryption after restore is unproven (see A.8.13) |
| A.8.25–A.8.31 Secure SDLC/coding/testing/separation | Yes | Implemented/Partial | staging-only release, tests, scans, environment isolation. Browser hardening: a strong Content-Security-Policy with `default-src`/`script-src` now ships **Report-Only** (staged rollout, enforce flag `SECURITY_CSP_ENFORCE`; `App\Security\ContentSecurityPolicy`). The previously-enforced header carried no `script-src`/`default-src` and gave little XSS mitigation — so **CSP XSS mitigation is Partial/staged until the enforce flag is switched on after Meta/Stripe UAT** (R-017) |
| A.8.32 Change management | Yes | Implemented | reviewed commit/deploy/migration gates |
| A.8.33 Test information | Yes | Implemented | synthetic-only staging policy |
| A.8.34 Protection during audit testing | Yes | Implemented | production read-only/out-of-bounds rule and scoped evidence |

Rows updated after the Fable audit and the August 2026 remediation are cross-referenced to code and risks in `remediation-2026-08-status.md`. Statuses remain a self-assessment; no external VAPT or certification has been performed.
