# SayaraForce Phase 3 - Website and Lead Capture

Status: local website and demo capture readiness complete.

## Website Route / View Status

- Public landing page: `/`
- Demo request submit: `POST /book-demo`
- Thank-you page: `/thank-you`
- Privacy page: `/privacy-policy`
- Terms page: `/terms`

The public landing page positions SayaraForce as a WhatsApp-first lead recovery and retention CRM for UAE garages.

## Landing Page Status

The landing page includes:

- Hero and core value proposition.
- Problem section.
- Solution section.
- WhatsApp-first follow-up positioning.
- Lead capture / demo request form.
- Booking, job, invoice, and retention narrative.
- Founder pricing/launch offer copy.
- CTA sections.

## Book Demo Form Status

The Book Demo / Request Free Audit form captures:

- Garage name.
- Contact name.
- WhatsApp phone.
- Email.
- Monthly vehicle volume.
- Review request/message.

Validation is server-side and required contact fields are enforced.

## CRM Lead / Enquiry Creation Status

The form stores a local JSONL enquiry under Laravel storage for founder follow-up.

This avoids triggering existing lead-created WhatsApp automation or pretending the public site is tenant-wired before final approval.

FOUNDER ACTION REQUIRED: approve the production CRM lead-source routing tenant before the live website goes public.
FOUNDER ACTION REQUIRED: confirm the CRM routing path does not send real WhatsApp messages unless an approved test/live path is enabled.

## Thank-You Page Status

The thank-you page confirms the request was received and makes clear that founder follow-up happens before any campaign/outreach action.

## WhatsApp CTA Status

The landing page includes a WhatsApp CTA.

- If a public WhatsApp click URL is configured, the CTA opens it.
- If not configured, the CTA safely falls back to the audit/demo section.

FOUNDER ACTION REQUIRED: approve the public WhatsApp click-to-chat URL before launch.

## Privacy / Terms Status

- Privacy Policy page exists.
- Terms page exists.
- Both are basic launch documents and should be reviewed before production use.

FOUNDER ACTION REQUIRED: final legal review and approval.

## Mobile QA Notes

The landing page uses responsive CSS and stacks major sections/form content for smaller screens. Local automated tests confirm routes render, but final visual QA should be done in browser on mobile width.

FOUNDER ACTION REQUIRED: approve website mobile view.

## Domain / SSL / GA4 Founder Actions

- Conditional GA4 placeholders exist for page tracking and `generate_lead` on thank-you page when a measurement ID is configured.
- FOUNDER ACTION REQUIRED: connect final domain.
- FOUNDER ACTION REQUIRED: verify SSL.
- FOUNDER ACTION REQUIRED: approve GA4 measurement ID or analytics provider.
- FOUNDER ACTION REQUIRED: confirm conversion event names before paid traffic.
