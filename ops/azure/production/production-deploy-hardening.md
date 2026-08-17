# Production deployment hardening (Fable H1) — provisioning plan

**Status: PREPARED, NOT EXECUTED.** This document accompanies the hardened
`.github/workflows/main_app-sayaraforce.yml`. Nothing here has been run against
production during the staging remediation sprint. A human operator must run the
provisioning steps below **before** the hardened workflow is adopted on `main`.

## Why

The previous production workflow deployed with a static **publish profile**, had
**no `permissions:` block** (default read/write `GITHUB_TOKEN`), ran **no tests /
audit / secret scan / identity assertion**, and shipped `tests/`, `ops/`, `docs/`
into the production artifact. The hardened workflow closes all of these, matching
the proven `deploy-staging.yml` standard.

## 1. Create a production OIDC federated identity (replaces the publish profile)

Run as a subscription Owner (or a delegated identity with permission to create
app registrations and role assignments). **Staging identity is not reused.**

```bash
SUB=cd04aa2d-d63a-463c-98da-0c67228810f0
RG=rg-garagecrm-prod
APP=app-sayaraforce
ORG=sam-paulstechnologies
REPO=garagecrm

# 1a. App registration + service principal for GitHub Actions (production)
az ad app create --display-name "gh-actions-sayaraforce-prod-deploy"
APP_ID=$(az ad app list --display-name "gh-actions-sayaraforce-prod-deploy" --query "[0].appId" -o tsv)
az ad sp create --id "$APP_ID"

# 1b. Federated credential scoped to the main branch + production environment ONLY
az ad app federated-credential create --id "$APP_ID" --parameters '{
  "name": "gh-sayaraforce-prod-main",
  "issuer": "https://token.actions.githubusercontent.com",
  "subject": "repo:'"$ORG"'/'"$REPO"':environment:production",
  "audiences": ["api://AzureADTokenExchange"]
}'

# 1c. LEAST-PRIVILEGE role assignment: Website Contributor on the prod site ONLY
#     (NOT subscription-wide, NOT Owner/Contributor)
az role assignment create \
  --assignee "$APP_ID" \
  --role "Website Contributor" \
  --scope "/subscriptions/$SUB/resourceGroups/$RG/providers/Microsoft.Web/sites/$APP"

# 1d. If the workflow must write the DEPLOYED_* app settings, the Website
#     Contributor role already covers appsettings set on that site scope.
```

## 2. Add GitHub repository secrets (Settings -> Secrets -> Actions)

- `AZURE_PROD_CLIENT_ID`       = `$APP_ID`
- `AZURE_PROD_TENANT_ID`       = `eff5f854-dbca-43f0-9355-edf46758bf3d`
- `AZURE_PROD_SUBSCRIPTION_ID` = `cd04aa2d-d63a-463c-98da-0c67228810f0`

Then **delete** the `AZUREAPPSERVICE_PUBLISHPROFILE` secret so the static
credential can no longer be used.

## 3. Protect the `production` GitHub environment

- Require reviewers (at least one) for the `production` environment.
- Restrict deployment branches to `main`.

## 4. Pin third-party actions to commit SHAs

The workflow currently uses version tags with `# TODO(H1): pin to commit SHA`.
Resolve and replace each before main adoption:

```bash
gh api repos/actions/checkout/git/refs/tags/v4 --jq '.object.sha'
gh api repos/shivammathur/setup-php/git/refs/tags/v2 --jq '.object.sha'
gh api repos/actions/setup-node/git/refs/tags/v4 --jq '.object.sha'
gh api repos/actions/upload-artifact/git/refs/tags/v4 --jq '.object.sha'
gh api repos/Azure/login/git/refs/tags/v2 --jq '.object.sha'
# Replace `uses: org/action@v4` with `uses: org/action@<sha> # v4`
```

## 5. Adoption

Merging this workflow to `main` is **out of scope** for the staging remediation
sprint and must go through normal review. Until steps 1-3 are complete, the
workflow fails closed at `azure/login` (the OIDC secrets do not exist), which is
the intended safe default — it will not fall back to a weaker credential.

## Rollback

The workflow records `PREVIOUS_DEPLOYED_COMMIT` before deploying and, on a failed
post-deploy health check, instructs re-dispatch at that SHA. For a fuller
rollback story, consider App Service deployment slots (staging slot + swap) as a
follow-up DR improvement.
