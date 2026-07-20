# Platform Marketing Audit

## Existing Reusable Components

- Super Admin control center exists under `routes/super_admin.php`, `App\Http\Controllers\SuperAdmin`, and `resources/views/super_admin`.
- Existing garage WhatsApp flow is handled by `MetaWhatsAppWebhookController`, `ProcessInboundWhatsApp`, `ConversationEngine`, `ConversationGuard`, `MessageLogger`, and company-level Meta settings.
- Existing OpenAI/NLP logic is in `App\Services\Ai\NlpService`.
- Azure queue worker is a continuous WebJob at `App_Data/jobs/continuous/sayaraforce-queue`.

## Flows Not Touched

- Garage inbound WhatsApp processing remains in `MetaWhatsAppWebhookController` and `ProcessInboundWhatsApp`.
- Garage lead creation, bookings, inbox, manager/admin WhatsApp behavior, and tenant company credentials remain unchanged.
- Platform prospects are not stored in garage lead, client, booking, job, invoice, or message log tables.

## Safe Extension Points

- Super Admin-only route group: `/super-admin/marketing/*`.
- New `platform_marketing_*` tables.
- A webhook router in front of the Meta WhatsApp endpoint that delegates to the existing garage controller unless a platform channel/prospect/conversation match is found.
- New queue names: `platform-marketing` and `platform-marketing-high`.
- New isolated AI service under `App\Services\PlatformMarketing\Ai`.

## Webhook Routing Constraints

The router may treat a payload as platform marketing only when the Meta `phone_number_id` matches an active platform channel and the sender matches an existing platform prospect with an active campaign recipient or platform conversation. Everything else falls through to the existing garage controller.

## Queue Architecture

Garage jobs continue on `database/default`; notification jobs continue on `database/notifications`. Platform marketing jobs use `platform-marketing` and `platform-marketing-high`, and the WebJob now listens to all four queues.

## OpenAI Status

The platform module uses a separate AI adapter, prompt builder, validator, fallback, and usage log. It reads the existing server-side OpenAI config but does not reuse or modify the garage `NlpService`.

## Demo Booking

Platform demo appointments are stored in `platform_marketing_appointments`. No records are written to garage bookings.

## Tenant And Platform Separation

Tenant companies keep their Meta configuration on company records. Platform channel configuration is stored in `platform_marketing_channels`, with token fields hidden and encrypted where written by the module.

## Security Risks

- Meta access token availability and approved template availability are external blockers for live sends.
- Template sync and full import preview can be expanded after Meta credentials and UAT test templates are confirmed.
- Super Admin destructive campaign actions require human confirmation in the UI flow and audit logging should be expanded for high-risk production operations.

## Proposed Architecture

Use a parallel platform marketing bounded context with its own models, services, jobs, views, and route group. Allow only narrow integration at webhook routing, queue worker registration, and shared server-side OpenAI/Meta configuration.
