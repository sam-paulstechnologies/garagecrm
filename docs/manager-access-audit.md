# Manager Access Audit

Date: 2026-07-08

## Route Envelope

Authenticated manager app routes live in `routes/manager.php` under:

`web`, `auth`, `active`, `force_password`, `role:manager`, prefix `manager`, name `manager.*`.

Admin routes live under `role:admin,media_team` plus `media_team.scope`; WhatsApp admin routes in `routes/whatsapp.php` require `role:admin`; super admin routes require `role:super_admin`.

Public exception: `/manager/booking/{token}` is intentionally unauthenticated in `routes/admin.php` for token-based customer booking handoff. It is not part of the authenticated manager console.

## Manager Console Matrix

| Route | Controller | View | Access status | Risk / fix |
| --- | --- | --- | --- | --- |
| `manager.dashboard` | `Manager\DashboardController@index` | `manager.dashboard` | Allowed, company-scoped counts | Polished nav and removed team shortcut |
| `manager.leads.index` | `Manager\LeadController@index` | `manager.leads.index` | Allowed, `company_id` scoped | Existing list blocks cross-company rows |
| `manager.leads.*` actions | `Manager\LeadController` | List-managed actions | Allowed, per-record `company_id` authorization | Direct cross-company actions 403 |
| `manager.opportunities.index/show/actions` | `Manager\OpportunityController` | `manager.opportunities.*` | Allowed, `company_id` scoped | Legacy stages blocked |
| `manager.bookings.index/show/actions` | `Manager\BookingController` | `manager.bookings.*` | Allowed, route binding and controller scope by company | Cross-company direct IDs 404/403 safely |
| `manager.jobs.index/show/actions` | `Manager\JobController` | `manager.jobs.*` | Allowed, route binding and controller scope by company | Completion requires invoice path |
| `manager.clients.index` | `Manager\ClientController@index` | `manager.clients.index` | Allowed, `company_id` scoped | View polished; archived clients hidden when column exists |
| `manager.invoices.index/show/actions` | `Manager\InvoiceController` | `manager.invoices.*` | Allowed, requires `invoices.company_id` and scopes every query | Cross-company direct IDs return 404 |
| `manager.inbox.*` | `Manager\InboxController` | Inertia `Manager/Inbox/Index` | Allowed, conversation/message queries scoped by company | Cross-company conversations abort 403 |
| `manager.growth.index` | `Manager\GrowthController@index` | `manager.growth.index` | Allowed as operational reporting; labeled Reports in nav | No admin campaign/settings controls exposed |
| `manager.team.index` | `Manager\TeamController@index` | `manager.team.index` | Scoped read-only route exists | Hidden from primary nav to avoid user-management surface |
| `manager.settings.index` | `Manager\SettingsController@index` | `manager.settings.index` | Safe placeholder route exists | Hidden from nav/dropdown; no credentials/config writes |

## Denied Areas

Managers must receive a clean 403 or safe not-found/redirect for:

- `admin.dashboard`
- `admin.settings.*`
- `admin.whatsapp.*`
- `admin.lead-sources.*`
- `admin.marketing.*`
- `admin.ai.*`
- `admin.audience-segmentations.*`
- `admin.documents.*`
- `super-admin.*`

## Nav Decision

Manager primary navigation now exposes operational work only:

Dashboard, Leads, Opportunities, Bookings, Jobs, Invoices, Clients, Calendar if present, Reports, Inbox.
