# SayaraForce Quick Scan

## Purpose and safety boundary

Quick Scan is a platform-created, temporary WhatsApp Business opportunity assessment. It lets a garage authorize a bounded, read-only analysis before creating a SayaraForce tenant or subscription. A scan never creates a Client, Lead, Opportunity, Booking, Job, invoice, campaign, reminder, subscription, or outbound customer communication.

The feature is disabled by default through `QUICK_SCAN_ENABLED=false`. Staging explicitly enables it. Only a platform administrator with recent security step-up can create, revoke, regenerate, follow up, purge, or run a synthetic scan.

## Data flow

```text
Platform administrator creates scan
  -> cryptographic 64-character expiring link / QR
  -> garage opens mobile landing page
  -> explicit versioned consent
  -> optional staff-number exclusion
  -> garage completes Meta Embedded Signup itself
  -> provider-controlled assets are verified
  -> staging allowlists and production denylists pass
  -> supported history enters Quick Scan quarantine
  -> shared bounded history signal engine analyses a configured sample
  -> aggregate, masked report
  -> Start SayaraForce OR Not now
```

Normal tenant webhook routing always runs first. A history event can enter Quick Scan only when no canonical tenant connection resolves and the verified WABA/phone belongs to one completed scan provider session. Live messages, statuses and echoes for a scan are recorded only as ignored idempotency evidence; they never enter normal CRM processing.

## Workspace and states

The additive schema contains:

- `quick_scan_workspaces`: encrypted garage prospect fields, consent, state, aggregate metrics, expiry, outcome and conversion/purge evidence.
- `quick_scan_provider_sessions`: encrypted temporary token and provider assets, cryptographic state, provider verification and history-sync timestamps.
- `quick_scan_candidates` / `quick_scan_messages`: temporary isolated customer-level history quarantine.
- `quick_scan_provider_events`: idempotent provider event receipt and encrypted payload quarantine.
- `quick_scan_events`: privacy-minimized product/security audit events.

States are `consent_pending`, `connection_pending`, `history_syncing`, `analysing`, `report_ready`, `accepted`, `declined`, `expired`, `purging`, `purged`, and `failed` (with `created` reserved for controlled initialization).

## Secure link, consent and Meta verification

The public URL contains only a random 64-character credential. The database stores a lookup hash and an encrypted recoverable copy for the authorized platform QR/copy-link screen. Links default to 24 hours, are revocable and can be regenerated; report access defaults to 72 hours.

Consent records the scan, time, policy version and authorized garage-level prospect context. It states that processing is temporary and observational, suggestions are not guaranteed conclusions, no customer will be contacted, and customer-level data is purged after a declined outcome.

Quick Scan reuses `MetaEmbeddedSignupService` for code exchange, access-token inspection, provider-granted WABA/phone discovery, connection-mode validation, WABA subscription verification and supported history request. The browser cannot establish provider truth: its IDs must exist within the inspected token's granted assets, match the claimed WhatsApp number, pass staging allowlists/production denylists, and not belong to a tenant or another scan. Provider secrets and identifiers are never shown in the garage report.

## Analysis and internal cost controls

`HistorySignalAnalyzer` is shared with tenant WhatsApp History Intelligence. It provides the same bounded deterministic/classification/retention/missed-enquiry/quote/service rules without creating a second intelligence engine. Optional staff numbers are scan-scoped HMAC identities and are deterministically excluded before semantic analysis.

Quick Scan uses `QUICK_SCAN_ANALYSIS_CONTACT_LIMIT`, initially 500 in staging. This is an internal acquisition limit, not a plan promise. Analysis is idempotent per candidate and history fingerprint, bounds message text, prefers deterministic signals, records contacts discovered/analysed, external AI calls and duration, and fails to conservative rules when external AI is unavailable. It never writes tenant `ai_customer_usages` or `whatsapp_history_contact_usages`.

“Potential missed enquiry” requires service/quote intent plus visible absence of later resolution and sufficient inactivity. Retention levels remain suggestions (`high`, `medium`, `low`, `none`). The report does not claim lost customers, completed service, or fabricated revenue.

## Garage report

The responsive report shows factual aggregates only: discovered/analysed conversations, likely customer conversations, potential retention customers, high-retention opportunities, potential missed enquiries, quotes worth reviewing, service-related conversations, insufficient evidence, personal/staff/noise and locked sample size. Up to three masked examples use labels such as `Customer A•••`; no full phone, name, raw conversation, WABA, provider ID, token, prompt or hidden reasoning appears.

The browser view is printable. It is designed for 390px phone, 768px tablet and desktop widths without requiring a PDF for the initial release.

## Accept and conversion

`Start with SayaraForce` records acceptance and opens the normal public registration form with the garage prospect prefilled. Registration still creates the normal company, garage, explicit Free subscription and tenant administrator, and the existing mandatory-admin 2FA enrollment applies.

Customer-level scan candidates/messages are rebound only into the canonical WhatsApp history quarantine. No Client is created. The tenant administrator must still use the normal Track / Don't Track review before any CRM import. Provider identifiers are not automatically transferred: a verified Meta reconnection remains deliberate until safe ownership transfer can be proven without duplicate WABA subscriptions or phone connections.

## Decline, purge and sales follow-up

`Not now` marks the scan declined and schedules the database-queue purge after `QUICK_SCAN_DECLINE_PURGE_DELAY_HOURS` (24 hours in staging). The purge physically deletes candidate/message rows, removes provider payloads, tokens, IDs, hashes, claimed/staff numbers and identifiable evidence summaries, rotates/revokes the public credential, and records deletion evidence.

Only garage-level encrypted prospect details and aggregate findings remain: garage/contact information supplied for the demo, source/owner, scan date, counts, outcome, follow-up date, notes, consent/audit lifecycle and purge evidence. No garage-customer message, name, number, provider customer ID or customer-specific semantic summary remains.

## Outbound and provider asset guarantees

Quick Scan has no outbound messaging service, route or job. Its provider router accepts history only and fails closed for live messages/statuses/echoes. It cannot schedule reminders, campaigns or automation. Global staging WhatsApp/SMS/email delivery guards remain independently disabled. Decline/purge is local: it does not deregister a phone, delete/transfer a WABA, remove the WhatsApp Business App, or mutate provider business assets.

## Platform workflow and synthetic rehearsal

Platform administrators use `/platform/quick-scans` to create a scan, show/copy its link, display a QR, inspect aggregate report/state, set a garage-level follow-up, revoke/regenerate, purge, or create the controlled synthetic fixture. List pages show no customer content.

The synthetic fixture creates exactly 500 isolated contacts with deterministic service, retention, unresolved quote, personal/staff and insufficient-evidence scenarios. It makes no Meta or external AI request. Automated rehearsals prove report generation, acceptance into normal registration/history review without Clients, and decline followed by physical purge while aggregate sales evidence remains.

## Controlled real-garage stop point

This release stops after synthetic staging verification. A real garage may be connected only after separate authorization, reviewed Meta staging assets/allowlists and garage-owned consent/login/OTP/QR/PIN actions. No salesperson or automated test completes those human-owned actions.
