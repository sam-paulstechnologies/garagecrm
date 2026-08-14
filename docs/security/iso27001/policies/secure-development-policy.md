# Secure development lifecycle policy

1. Define security/privacy acceptance criteria and threat boundaries for material work.
2. Develop on a feature/release worktree without overwriting unrelated work; never use production for testing.
3. Apply tenant scope, server-side authorisation, validated input/output encoding, CSRF/signature/replay controls, private file handling, safe logging and fail-closed provider/AI behaviour.
4. Add positive, negative, cross-tenant, replay, privilege and failure-path tests. Do not make integration tests create missing operational schema ad hoc.
5. Run PHP lint, Pint, focused/full tests, dependency audits, secret scan, SBOM generation and frontend build.
6. Review diff, migration reversibility/data safety, configuration, dependency provenance, infrastructure targets and evidence before commit.
7. Push/deploy staging only, run guarded migration/cache runtime, automated verification and browser smoke/UAT.
8. Production preparation includes exact tested SHA, backup/rollback, change approval, monitoring and separation of duties; deployment remains explicitly authorised.

Critical/high security failures stop release. Generated secrets, `.env`, publish profiles, customer exports and provider payloads are never committed.
