# Security roles and responsibilities

| Role | Responsibilities | Must not self-approve |
|---|---|---|
| Accountable executive | scope, policy, risk acceptance, resources, production launch | own high-risk technical implementation |
| Security owner | risk/SoA/evidence, access review, incidents, assurance | independent audit of own programme |
| Engineering owner | secure design/code/tests/dependency response | high-risk production release alone |
| Operations owner | Azure/KV/network/backup/restore/monitoring | restore/production switch without approval |
| Privacy/legal owner | lawful basis/notices/rights/retention/contracts/breach decisions | technical control effectiveness |
| Commercial/payment owner | catalogue/billing/provider lifecycle | bypass signed payment evidence |
| Integration/AI owner | supplier config, assets, model/action safety | allow production asset in staging |
| Independent reviewer/tester | VAPT/control effectiveness/retest | implement the control being independently assessed |

Named people, deputies and contacts belong in a restricted organisational record, not this public-capable repository.
