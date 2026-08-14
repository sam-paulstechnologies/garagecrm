# Business continuity security

During disruption, security controls remain in force. Do not disable 2FA, signatures, CSRF, tenant scoping, provider mapping, encryption or audit merely to restore service.

Minimum viable safe service prioritises authenticated read access and inbound capture. Billing changes, Meta connection, autonomous/outbound messaging, scheduler automation, destructive deletion and bulk exports may remain disabled until dependencies and monitoring are verified.

Maintain offline contact/escalation, source/IaC access, Key Vault recovery procedure, latest verified release/SBOM, supplier contacts, backup inventory and tenant communication templates. Temporary access is named, time-limited and reviewed. Manual workarounds must not copy customer data into personal tools.

Business owner reviews acceptable downtime/data loss annually; Operations tests; Security observes control preservation; Legal/Privacy owns notifications.
