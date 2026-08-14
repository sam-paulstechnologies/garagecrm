# Information classification and handling

| Class | Examples | Storage/transfer | Access | Logging/display | Disposal |
|---|---|---|---|---|---|
| Public | marketing pages, public price catalogue | approved public channels, TLS | public | may log minimally | normal content lifecycle |
| Internal | architecture, non-sensitive runbooks, aggregate metrics | approved corporate/repository systems | workforce need-to-know | no customer/provider identifiers | remove when obsolete |
| Confidential | customer/contact/vehicle/booking/invoice/message content, business data | encrypted platform storage, TLS | tenant-scoped authorised users/support under control | mask/minimise; no raw payloads in routine logs | approved retention/DSAR process |
| Restricted | passwords, TOTP/recovery, provider/payment secrets, signing keys, raw high-risk tokens, backups | Key Vault or approved encryption; never chat/Git | smallest named/system identity set | never print/display/log; status only | cryptographic deletion/rotation and evidenced purge |

## Special handling

- Passwords are one-way hashed; TOTP/recovery material uses framework protection; current OTPs are never stored.
- Stripe/Meta IDs are integration metadata, not application identity. Mask/hide them outside restricted diagnostics.
- Customer message text is Confidential and may be sent to an AI supplier only under approved purpose, data-processing terms, configured provider, quota and minimisation controls.
- Synthetic fixtures must use non-real identities and reserved test domains/numbers.
- Any suspected classification breach invokes the incident response plan.

The privacy/legal owner approves retention and lawful basis. Engineering does not decide legal classification alone.
