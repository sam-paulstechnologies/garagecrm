# Phone Number Handling Audit

## Current Phone Fields Found

- Leads use `phone` and `phone_norm` on `App\Models\Client\Lead`.
- Clients use `phone`, `phone_norm`, `whatsapp`, and some import paths check optional `whatsapp_number`.
- Conversations use `customer_phone` on `conversations`.
- Message logs use `to_number` and `from_number` on `message_logs`.
- WhatsApp message records use a mix of `to`, `to_number`, `from`, `from_number`, `phone`, and payload fields depending on schema/version.
- Company and settings screens include manager/company WhatsApp number fields such as `whatsapp_manager_number`, `whatsapp.manager_number`, and `whatsapp_number`.

## Current Raw Storage Behavior

- The app already stores raw/display lead and client numbers in `phone`.
- The app stores normalized lookup numbers in `phone_norm`.
- Existing `phone_norm` convention is digit-only, for example `971599992001`, not `+971599992001`.
- This pass preserves that storage convention to avoid breaking existing duplicate matching, imports, and conversation lookup.

## Current Display Behavior

- Lead list previously showed email under the lead name and displayed phone as plain text.
- Client profile/detail pages already build `tel:+...` links from normalized phone values.
- Lead details still keep email and phone data available outside the list table.

## Current WhatsApp Behavior

- Admin inbox route is `admin.inbox.index` at `/admin/inbox`.
- Admin inbox JSON list is `admin.inbox.list` at `/admin/inbox/list`.
- Admin inbox message route is `admin.inbox.messages` at `/admin/inbox/messages/{conversation}`.
- The active admin inbox is the Inertia page `resources/js/Pages/Admin/Inbox/Index.jsx`.
- The inbox accepts a `search` query parameter and searches `customer_name`, `customer_phone`, and last message preview.
- Existing client profile links open WhatsApp inside the app using `route('admin.inbox.index', ['search' => <phone digits>])`.

## Current Tel/Call Behavior

- Client profile and details pages already use `tel:+...`.
- Lead table now uses `PhoneNumberService::buildTelUrl()` to build `tel:+...` links.

## Current Duplicate Matching Behavior

- Lead duplicate/reuse checks use `phone_norm` and `email_norm`.
- `LeadResolver`, `LeadFactory`, `LeadCreationService`, import services, Meta/Google webhook services, and client import services all perform phone-based duplicate or reuse checks.
- Several legacy/import paths still have local `normalizePhone()` implementations that produce the same digit-only UAE-style lookup key.
- This pass updates `Lead::normalizePhone()` and `Client::normalizePhone()` to delegate to `PhoneNumberService::buildWhatsappLookupKey()`, so callers that use the models now share one lookup-key behavior.

## Gaps/Risks

- Phone normalization logic is still duplicated in several older import/webhook/WhatsApp services.
- `phone_norm` is digit-only while outbound WhatsApp services often expect E.164 with a leading `+`.
- Some code assumes UAE when converting `05...` or `5...` numbers, even when no explicit country/market is available.
- No broad backfill was performed in this pass.
- No heavy phone-number package is currently used.

## UAE/GCC Normalization Recommendation

- Keep raw input in `phone` unless a migration/backfill policy is explicitly approved.
- Keep `phone_norm` as the digit-only lookup key for compatibility.
- Use E.164 only for action URLs and outbound provider calls.
- If a lead/client country or market field exists, pass it into the phone service. No lead country/market field was found in the lead list flow, so this pass defaults action normalization to UAE (`AE`).
- Consider replacing remaining local `normalizePhone()` methods with `PhoneNumberService` in a follow-up pass.
- A package such as libphonenumber could improve global validation, but it was not added because current requirements can be met with lightweight UAE/GCC-safe logic.

## Exact Behavior Implemented In This Pass

- Added `App\Services\PhoneNumberService`.
- `cleanRawPhone($value)` strips spaces, dashes, brackets, and non-number formatting while preserving a leading `+`.
- `normalizeToE164($value, $country = 'AE')` returns a `+` E.164 value when possible.
- `formatForDisplay($value)` displays E.164 when normalization succeeds.
- `buildTelUrl($value)` returns `tel:+...`.
- `buildWhatsappLookupKey($value)` returns digit-only lookup keys compatible with existing `phone_norm` and inbox search behavior.
- `isValidMobileLikeNumber($value)` validates UAE mobile-like numbers as `+9715XXXXXXXX`.
- Lead and Client model normalization now delegates to `PhoneNumberService::buildWhatsappLookupKey()`.
- Lead list phone links now use `tel:+...`.
- Lead list WhatsApp/status links now open the internal admin inbox with `search=<normalized digits>`.

## Examples

| Raw input | E.164 action value | Lookup key |
| --- | --- | --- |
| `971599992001` | `+971599992001` | `971599992001` |
| `+971599992001` | `+971599992001` | `971599992001` |
| `0599992001` | `+971599992001` | `971599992001` |
| `00971599992001` | `+971599992001` | `971599992001` |

## Assumptions

- UAE is the default market when no country/market context is available.
- Existing `phone_norm` must remain digit-only in this pass.
- Archived leads are represented by `is_active = 0` and `status = lost` through the existing lead destroy/archive behavior.
