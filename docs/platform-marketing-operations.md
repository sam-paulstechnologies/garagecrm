# Platform Marketing Operations

- Use `/super-admin/marketing/dashboard` for health, funnel metrics, and current channel status.
- Keep campaigns in draft until the selected segment and approved template have been reviewed.
- Use Prepare Recipients to enforce consent, opt-out, invalid/blocked, and duplicate-recipient controls.
- Approve a campaign before queueing launch.
- Do not send bulk production campaigns during UAT.

Rollback for live sends: pause or stop the campaign from the campaign detail page, then inspect `platform_marketing_campaign_recipients` and `platform_marketing_conversation_messages`.
