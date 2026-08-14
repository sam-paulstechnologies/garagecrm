# Legal and regulatory readiness register

This register identifies review topics; it is not legal advice or a conclusion of legal compliance.

| Topic | Potential relevance | Current technical support | Required external decision/evidence |
|---|---|---|---|
| UAE Federal Decree-Law No. 45 of 2021 (PDPL) | personal data of garage customers/users | access controls, security, minimisation, consent/purge records | counsel confirms applicability, controller/processor roles, lawful basis, notices, rights, breach and transfer duties |
| UAE electronic transactions/cybercrime requirements | authentication, electronic records, misuse | audit logs, 2FA, integrity controls | counsel maps applicable record/evidence/reporting duties |
| Payment/card requirements | Stripe-hosted payment flow | SayaraForce does not handle card numbers; signed webhooks | confirm PCI scope/SAQ with Stripe/acquirer; retain evidence |
| Telecommunications/WhatsApp/marketing consent | messaging and follow-up | outbound guards, entitlement/consent concepts, templates | approve consent, opt-out, template and marketing rules per jurisdiction/provider |
| AI/privacy/transparency | customer conversation analysis | metering, observational/action separation, fail closed, no hidden reasoning exposure | approve notices, lawful basis, human review, supplier terms and risk assessment |
| Contracts/records/tax | subscriptions/invoices/service records | immutable invoice attribution/audit | legal/finance retention and customer contract terms |
| Cross-border processing | global suppliers/subprocessors | data-flow/supplier registers | document hosting/processing locations and approved transfer mechanism |
| Data-subject rights | access/correction/deletion/objection | tenant CRUD plus Quick Scan/history purge | implement approved request verification/export/delete workflow and exceptions |
| Incident/breach notification | security incidents involving personal data | IR plan/logging | counsel defines thresholds, authorities/customers and timeframes |

Open legal items are release risks and require named counsel/privacy-owner acceptance before production security sign-off.
