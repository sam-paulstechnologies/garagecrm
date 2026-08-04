# SayaraForce WhatsApp Business App onboarding — controlled live UAT

Status: **NO-GO until every prerequisite below is confirmed**.
Scope: one controlled founder-number test only. Do not start campaigns or bulk messaging.

## GO / NO-GO prerequisites

All items must be GO before entering the founder's number:

- [ ] The complete automated quality gate is green on the exact release revision.
- [ ] The hardening migration is deployed through the normal non-destructive release process.
- [ ] `META_WHATSAPP_BUSINESS_APP_CONFIG_ID` is the dedicated v4 Business App onboarding configuration, not the standard Cloud API configuration.
- [ ] The Meta app is Live and still approved for `whatsapp_business_management` and `whatsapp_business_messaging`.
- [ ] Production app domains, OAuth redirect settings, webhook callback and verify token are correct.
- [ ] The WABA is subscribed to the app and the required webhook fields are enabled: `messages`, `smb_message_echoes`, `smb_app_state_sync`, and approved history events.
- [ ] The HTTPS application and `/up` endpoint are healthy.
- [ ] The database/default queue worker is running; retry and failed-job monitoring are visible.
- [ ] The founder confirms the number is active in **WhatsApp Business**, not personal WhatsApp.
- [ ] The founder has completed a current WhatsApp Business chat backup.
- [ ] The founder approves optional contact/history sharing and understands what data will be synchronized.
- [ ] A second external phone and an authorised SayaraForce admin session are available for testing.
- [ ] No campaign, bot, template automation or bulk send is enabled for historical data.

If any prerequisite is NO or unknown, do not enter the number.

## Live UAT procedure

Record timestamps and outcomes, but never paste tokens, authorization codes, customer message bodies or full phone numbers into the test record.

1. [ ] Founder backs up WhatsApp Business chats.
2. [ ] Founder confirms the number is active in WhatsApp Business, not personal WhatsApp.
3. [ ] In SayaraForce, select **Connect an existing WhatsApp Business app number**.
4. [ ] Confirm Meta explicitly presents WhatsApp Business App onboarding/coexistence and indicates the mobile app can remain in use.
5. [ ] **Cancel immediately** if Meta asks to remove, migrate or deregister the number from the WhatsApp Business app. Record the safe cancellation only.
6. [ ] Complete signup only after the previous wording is confirmed.
7. [ ] Confirm SayaraForce shows a WABA, masked phone details, Business App mode and connected status.
8. [ ] Run diagnostics and confirm the WABA webhook subscription is `subscribed`.
9. [ ] Open the WhatsApp Business mobile app and confirm it still sends/receives normally.
10. [ ] Send one unique text from the external customer phone to the founder number.
11. [ ] Confirm the message appears exactly once in the correct SayaraForce company/conversation.
12. [ ] Send one reviewed reply from SayaraForce.
13. [ ] Confirm the external phone receives that reply exactly once.
14. [ ] Send one unique message directly from the founder's WhatsApp Business mobile app.
15. [ ] Confirm it appears once in SayaraForce as a mobile-app outbound echo and is not resent by Cloud API.
16. [ ] Repeat controlled tests for image, document and voice note; record whether preview/download metadata is available without exposing content in logs.
17. [ ] Confirm sent, delivered and read statuses advance without regression; run one safe failure case only if Meta provides a non-customer-impacting method.
18. [ ] If the founder approved it, request history sync and confirm diagnostics progress/completion.
19. [ ] Confirm synchronized history remains in the isolated history store and creates no new lead, opportunity, reply, reminder or campaign action.
20. [ ] With explicit approval, test one approved template outside the customer-service window; do not test bulk sending.
21. [ ] Keep disconnect/reconnect instructions available. Do not perform them unless diagnostics show a need and the founder authorises the action.

## Expected diagnostics

- Connection mode: `business_app_onboarding`
- Business app onboarding: yes
- Webhook subscription: `subscribed`
- Last inbound webhook advances after step 10
- Last mobile-app echo advances after step 14
- Last API outbound advances after step 12
- Contact/history status reflects only explicitly requested synchronization
- No access token or verify token is displayed

## Stop conditions

Stop immediately and mark NO-GO if:

- Meta presents number migration/removal instead of Business App onboarding;
- the mobile app stops working;
- a message appears in another tenant;
- an inbound, echo or history message is duplicated;
- history creates a lead, reply or automation side effect;
- an app echo is sent again through Cloud API;
- the webhook is unsigned/rejected, the queue is not processing, or application errors recur;
- tokens, codes, phone numbers or conversation bodies appear in normal application logs.

## Disconnect/reconnect recovery

1. Pause live testing and all outbound actions.
2. Capture only redacted diagnostics and internal reference IDs.
3. Confirm queue/webhook health before changing the connection.
4. Use **Disable SayaraForce connection locally** only with founder approval; this removes the local token and stops SayaraForce processing but does not claim to change the Meta subscription.
5. Re-run the same existing-number path with a new state/code. Never switch to the dedicated Cloud API path as a workaround for the founder's existing Business app number.

## Final UAT decision

- **GO:** all prerequisites and 21 steps pass, the mobile app remains operational, tenant isolation holds, and no duplicate/automation side effect occurs.
- **NO-GO:** any stop condition occurs or any prerequisite remains unknown. Preserve the number on the mobile app, disable SayaraForce locally if authorised, and investigate before another attempt.
