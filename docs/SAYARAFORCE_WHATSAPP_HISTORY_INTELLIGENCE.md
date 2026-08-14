# SayaraForce WhatsApp history intelligence

## Safety boundary

Coexistence history enters a tenant-scoped quarantine. Discovery never creates a Client, Lead, Opportunity, Booking, Job, campaign, reminder, automation, or outbound message. Only an authenticated tenant administrator with recent two-factor step-up can approve Track/Don't Track decisions and start an import. Provider identifiers remain server-controlled.

```text
Meta coexistence history
  -> encrypted quarantine batch and candidates
  -> bounded observational analysis
  -> administrator review
       -> Don't Track: suppress CRM processing and purge staged content
       -> Track: consume one cumulative history-contact allowance
           -> create/match Client
           -> attach historical Conversation/messages
           -> retain insight-only retention evidence
```

The existing `whatsapp_history_messages`, `whatsapp_synced_contacts`, canonical messaging connection, Meta webhook, Client, Conversation, MessageLog and RetentionAction models are reused. The additive tables record review batches, candidates, cumulative usage, tracking preferences, privacy-safe audits and generic commercial usage events. No competing WhatsApp number or provider-connection model is introduced.

## Review states

`pending_sync -> syncing -> awaiting_review -> importing -> completed|partial_failed`

Each candidate separately carries intelligence (`unselected|queued|analysing|analysed|deterministic|locked|analysis_failed`), review (`pending|track|dont_track`) and import (`not_requested|locked|requires_resync|created_client|matched_client|failed`) state. A provider history retry reuses the open connection-scoped batch and message fingerprints prevent duplicate rows.

Pending candidates remain quarantined when new live messages arrive. A brand-new sender with no history-review preference follows the ordinary live inbound CRM flow. Tracked senders follow the ordinary flow after import. Don't Track senders retain only minimum webhook security/idempotency evidence; their bodies and media metadata are not handed to CRM enrichment.

## Canonical launch limits

| Plan | Users | Locations | WhatsApp numbers | AI monitored customers / billing period | Cumulative history contacts | Campaigns / billing period | Recipients / campaign | Active workflows | History intelligence |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---|
| Free | 1 | 1 | 1 | 25 | 25 | 0 | 0 | 0 | Preview |
| Service | 3 | 1 | 1 | 300 | 300 | 1 | 100 | 0 | Basic |
| Growth | 10 | 2 | 2 | 1,000 | 1,000 | 5 | 500 | 5 | Full |
| Performance | 20 | 5 | 3 | 2,500 | 2,500 | 20 | 2,000 | 20 | Advanced |
| AI Pro | Custom | Custom | Custom | Fair use | Fair use | Fair use | Custom | Fair use | AI-assisted |

`config/commercial.php` and the versioned plan-entitlement rows are authoritative. A database plan ID, Blade condition, route input, browser-provided provider ID, or plan-name string never selects entitlement.

- Users, locations, WhatsApp numbers and active workflows are concurrent-resource limits.
- AI monitored customers and campaign creation use the current subscription billing period.
- Campaign recipients are checked per campaign at edit, enqueue and background execution time.
- History contacts are unique tenant-scoped HMAC identities and cumulative. Reconnect, resync or a new batch does not reset consumption. An exact candidate retry is idempotent.
- Limits never discard raw normal inbound messages or prevent access to existing business records. A limit only locks premium analysis/import or creation of the limited resource.
- AI Pro null allowances mean custom/fair-use entitlement, not a public promise of infinity.

## Intelligence and privacy

Analysis is observational. It emits only one relationship suggestion (`likely_customer`, `possible_personal`, `possible_colleague`, `unknown`) and one retention level (`high`, `medium`, `low`, `none`) with a short evidence summary. It cannot create or mutate operational records. Team-phone matching runs deterministically before semantic analysis and does not consume semantic AI work.

Input is bounded by message and character limits. The semantic response uses a strict allowlist and fails closed to conservative local evidence when unavailable or incompatible. Logs and audits exclude bodies, numbers, names, tokens, provider identifiers, prompts, secrets and hidden reasoning.

Don't Track is reversible, but purged staged content is not silently restored. Choosing Track later sets `requires_resync`; the administrator must deliberately resync before import. Imported historical message deletion is a separate explicit privacy option. Historical MessageLogs are flagged and ignored by the normal observer, so they cannot alter live unread state or trigger downstream CRM automation.

## Queue and retention operations

- `AnalyzeWhatsAppHistoryCandidate` is bounded, retryable and uses the database queue.
- `ImportTrackedWhatsAppHistory` imports approved candidates in batches of 50 and records partial failure without creating hidden actions.
- `whatsapp:purge-expired-history-review --dry-run` reports expired quarantines; an explicit non-dry run purges only unnecessary staged review content.
- `staging:whatsapp-history-uat --confirm` runs the complete synthetic discovery, analysis, Track/import and Don't Track rehearsal in a database transaction that is always rolled back. It refuses non-staging runtime identities and emits aggregate evidence only.

The staging scheduler remains disabled. The queue worker may process explicitly requested review jobs. WhatsApp autonomous, campaign and reminder delivery remain subject to the independent staging outbound deny-by-default controls.

## UI and administrator workflow

Open `/admin/messaging/whatsapp/history` after the WhatsApp connection is provider-verified. The page shows discovered, review-pending, eligible/locked, analysed, selected and imported counters; relationship and retention filters; a bounded message preview; explicit Track/Don't Track decisions; quota meter; and contextual upgrade guidance.

The history-sync, bulk-analysis, bulk-decision, privacy deletion and import actions require recent security step-up. Read-only review does not repeatedly prompt. Tenant ownership is checked again by every controller/service and cross-tenant route binding returns not found/forbidden.

## Controlled Meta UAT stop point

After a real approved coexistence connection and user-owned Meta action, request history once and verify:

1. Meta webhook signature, test WABA/phone allowlist and production denylist pass.
2. A single batch reaches `awaiting_review`.
3. Counters show discovered contacts and the plan allowance.
4. No Client, Lead, Opportunity, Booking, Job or outbound action is created.

Stop there. Do not choose Track or Don't Track for real contacts until the user explicitly authorizes review/import. Staging outbound remains disabled and production Meta assets remain untouched.
