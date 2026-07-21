# SayaraForce Operations Center Audit

## Repository State

- Branch at implementation start: `main`.
- HEAD at implementation start: `08499806`.
- Existing Operations Center: no routes, controllers, views, docs, or tests existed.
- Super Admin role storage: `users.role` is a string column from `2026_07_06_000000_add_access_fields_to_users_table.php`; no restrictive role enum migration is required.
- Environment recorded before DB-changing commands: `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=garagecrm`.
- This implementation adds no database migrations and performs no schema changes.

## Discovered Workflow

SayaraForce’s operational workflow is represented by real code in Admin, Manager, WhatsApp, Journey, Booking, Job, Invoice, and Super Admin surfaces:

1. Lead capture comes from website/public intake, Meta leads, WhatsApp, import, or manual entry.
2. Leads can be qualified and moved into opportunity pipeline state.
3. Opportunities can become booking requests or confirmed bookings.
4. Confirmed bookings can create or reuse jobs.
5. Jobs drive service work, completion, invoice/estimate state, and customer updates.
6. Retention and journey jobs wake later follow-up.
7. WhatsApp inbound is received by the Meta webhook, dispatched to `ProcessInboundWhatsApp`, interpreted by conversation services, sent through the configured provider, and logged.

## Route Catalogue

The catalogue is generated at runtime from Laravel’s route collection, not a handwritten list. Each node can include:

- route name;
- URI and method;
- controller/action;
- middleware-derived permission summary;
- valid direct page URL only when the route is a GET route without required parameters;
- source file when the controller can be resolved.

## Permissions Matrix

| Area | Middleware / Role | Visibility |
| --- | --- | --- |
| Super Admin Control Center | `auth`, `active`, `force_password`, `role:super_admin` | Super Admin only |
| Operations Center | `auth`, `active`, `force_password`, `role:super_admin` | Super Admin only |
| Admin workspace | Admin route groups | Tenant admin/media constraints |
| Manager workspace | Manager route groups | Tenant manager constraints |
| Webhooks | API/public webhook middleware | No Operations Center UI access |

Manager users are blocked from Operations Center routes by the existing Super Admin route middleware, so they cannot see source files, database details, or platform architecture nodes.

## Technical Architecture

The Operations Center is intentionally additive:

- `OperationsCatalogueService` builds graph nodes/edges from real routes and selected source files.
- `OperationsCenterController` serves one page and two progressive JSON endpoints.
- `resources/views/super_admin/operations/partials/graph-renderer.blade.php` is the shared renderer for Journey Flow, Mind Map, and Technical Map.
- Technical Map initial payload is capped so it does not load the full catalogue at once.
- Dragging graph nodes writes only to `localStorage`; it never updates application records.

## Caching And Query Optimisation

The graph data endpoint uses Laravel cache for 10 minutes. Catalogue generation reads routes and filesystem metadata and is designed to avoid database queries. Node expansion is progressive and fetched only when selected.
