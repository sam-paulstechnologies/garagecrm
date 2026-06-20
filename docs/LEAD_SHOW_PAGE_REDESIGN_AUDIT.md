# Lead Show Page Redesign Audit

## 1. Current Lead Show Page Structure Before

- The previous lead show page used `resources/views/admin/leads/show.blade.php` with stacked partials:
  - `show-partials/_header.blade.php`
  - `show-partials/_summary.blade.php`
  - `show-partials/_details.blade.php`
  - `show-partials/_source_attribution.blade.php`
  - `show-partials/_communications.blade.php`
  - `show-partials/_message_logs.blade.php`
- It showed status, score, source, hot flag, basic details, communications, and message logs.
- It did not have a Salesforce-style status path, right-side score/activity column, or lead-specific audit timeline.

## 2. Files Changed

- `app/Http/Controllers/Admin/LeadController.php`
- `app/Models/Client/Lead.php`
- `app/Models/LeadActivityLog.php`
- `database/migrations/2026_06_20_000000_create_lead_activity_logs_table.php`
- `routes/admin.php`
- `resources/views/admin/leads/show.blade.php`
- `docs/LEAD_SHOW_PAGE_REDESIGN_AUDIT.md`

## 3. Status Mapping Used

| Display Status | Posted Value | Stored Internal Status |
| --- | --- | --- |
| New | `new` | `new` |
| Attempting Contact | `attempting_contact` | `attempting_contact` |
| Contact On-Hold | `contact_on_hold` | `attempting_contact` |
| Disqualified | `disqualified` | `lost` |
| Converted | `converted` | `converted` |

`Lead::normalizeStatus()` already collapses `contact_on_hold` to `attempting_contact` and `disqualified` to `lost`, so the UI keeps the friendly Salesforce-like labels while respecting the app's current controlled status values.

## 4. Audit / Activity Logging Approach

- No existing lead-specific audit table was found.
- Existing activity sources were communications, message logs, and conversations.
- Added additive `lead_activity_logs` table for future UI status, update, hot flag, and archive actions.
- Added `App\Models\LeadActivityLog`.
- Added `Lead::activityLogs()` relation.
- The show-page timeline blends:
  - persisted lead activity logs,
  - synthetic lead-created and last-updated snapshots,
  - communication rows,
  - WhatsApp message log rows.
- Phone and email field old/new values are masked in the new activity logger.

## 5. Editable Fields Supported

- Full edits continue to use the existing lead edit page: `admin.leads.edit`.
- The show page adds compact `Edit` controls beside safe business fields and section-level edit links.
- System fields such as tenant/company ownership, created timestamps, updated timestamps, and audit fields are not made inline-editable.
- Inline editing was not added because no existing inline edit system was found.

## 6. Lead Score / Justification Approach

- The page uses the existing `Lead::calculateScore()` plus the controller's WhatsApp activity adjustments.
- The right-side score card shows:
  - score,
  - Hot / Warm / Cold label,
  - derived reasons from available lead fields,
  - suggested next action.
- The explanation is labeled as field-derived and does not claim to be an AI prediction.

## 7. Created By / Modified By Support

- The leads table/model does not expose reliable `created_by` / `updated_by` fields for leads.
- The UI displays created and last modified timestamps.
- Created By and Last Modified By display `System / unavailable` until the schema has reliable user attribution.
- Future status/update/archive actions are attributed through `lead_activity_logs.user_id`.

## 8. Archive Behavior

- Existing lead archive behavior is non-destructive.
- The show-page Archive button uses the existing `admin.leads.destroy` route.
- `LeadController::destroy()` sets `is_active = 0` and `status = lost`.
- The archive action is logged to `lead_activity_logs`.

## 9. WhatsApp / Contact Behavior

- The show page links WhatsApp actions to the existing internal inbox route `admin.inbox.index`.
- The URL includes available context: `conversation`, `lead_id`, and normalized E.164 phone.
- No external WhatsApp Web link was added.
- Phone call actions use the existing `PhoneNumberService` and `tel:+971...` behavior.

## 10. Gaps / Assumptions

- Contact On-Hold is not currently distinct in storage; it maps to `attempting_contact`.
- Disqualified is not currently distinct in storage; it maps to `lost`.
- Created By / Last Modified By user attribution is unavailable on existing lead rows.
- The internal inbox is an Inertia screen; this pass links to it with lead/conversation/phone context without creating a duplicate WhatsApp system.
- The new audit table is additive and requires migration before the new activity logs can be written in a running environment.

## 11. Validation Results

- `php artisan route:list`: passed; `admin.leads.status`, `admin.leads.show`, `admin.leads.edit`, and `admin.leads.destroy` are registered.
- `php artisan migrate --force`: blocked by an older unrecorded `2025_10_18_000000_create_message_logs_table` migration because `message_logs` already exists.
- `php artisan migrate --path=database/migrations/2026_06_20_000000_create_lead_activity_logs_table.php --force`: passed.
- `php artisan test`: passed, 28 tests / 69 assertions. PHPUnit reported one existing doc-comment metadata deprecation warning.
- `php -l app/Models/Lead.php`: failed because this project does not have `app/Models/Lead.php`; the lead model is `app/Models/Client/Lead.php`.
- `php -l app/Models/Client/Lead.php`: passed.
- `php -l app/Http/Controllers/Admin/LeadController.php`: passed.
- `php -l app/Models/LeadActivityLog.php`: passed.
- `php -l database/migrations/2026_06_20_000000_create_lead_activity_logs_table.php`: passed.
- `php -l routes/admin.php`: passed.
- `php -l resources/views/admin/leads/show.blade.php`: passed.
- Laravel render smoke check of `LeadController::show()` with an existing user/lead: passed and produced HTML.
- Browser/manual click validation was not completed in an authenticated browser session in this pass.
