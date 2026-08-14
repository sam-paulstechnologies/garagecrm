# SayaraForce security programme

Status date: 2026-08-15
Scope: SayaraForce application, staging Azure resources, source repository, CI/CD, third-party integrations, and operational procedures. Production was out of scope for change and customer-data access.

This pack is an ISO/IEC 27001:2022-aligned implementation baseline prepared for independent review. It is not a certification, compliance attestation, penetration-test report, legal opinion, or claim that the system is fully secure.

## Scope and boundaries

- In scope: Laravel web/API application, MySQL, App Service, Key Vault, Storage, Application Insights/Log Analytics, staging deployment identity, Stripe Sandbox, Meta/WhatsApp integration design, Quick Scan, AI analysis, repository and CI.
- Out of scope for this execution: production changes, production customer-data inspection, production restore, live Meta changes, real outbound communication, physical-office controls, employee background checks, and supplier contractual review.
- Security objectives: tenant confidentiality, least privilege, authenticated and authorised access, provider-signature validation, secret protection, auditable change, recoverability, safe AI degradation, and privacy-aware processing.

## Pack index

- `security-current-state.md` and `security-gap-register.md`
- `asset-register.md`, `information-classification-register.md`, `data-flow-register.md`
- `risk-register.md`, `risk-treatment-plan.md`, `statement-of-applicability.md`
- `security-controls-matrix.md`, `security-evidence-register.md`
- `supplier-register.md`, `legal-regulatory-register.md`
- policies under `policies/`
- runbooks under `runbooks/`
- review handoffs under `assurance/`

## Governance

The accountable executive approves risk acceptance. The security owner maintains this pack, risk register, evidence, incidents, access reviews and supplier reviews. Engineering owns remediation and secure delivery. Operations owns Azure, backup, restore and monitoring. Privacy/legal counsel must validate applicable UAE and customer-contract obligations. An independent certification body and independent penetration tester remain external dependencies.

Review quarterly, after a material incident, after significant architecture/provider change, or before a production security release.
