[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $SubscriptionId,
    [Parameter(Mandatory = $true)] [string] $GitHubOwner,
    [Parameter(Mandatory = $true)] [string] $GitHubRepository,
    [switch] $ConfirmStagingIdentityCreation
)

$ErrorActionPreference = 'Stop'
$resourceGroup = 'rg-sayaraforce-staging'
$webAppName = 'app-sayaraforce-staging'
$displayName = 'gha-sayaraforce-staging'

if (-not $ConfirmStagingIdentityCreation) {
    throw "Review complete. Re-run with -ConfirmStagingIdentityCreation to create '$displayName' with staging-app scope only."
}
if (-not (Get-Command az -ErrorAction SilentlyContinue)) { throw 'Azure CLI is required.' }

$account = az account show --query '{id:id,tenantId:tenantId}' --output json | ConvertFrom-Json
if (-not [string]::Equals($account.id, $SubscriptionId, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Subscription mismatch.'
}

$webId = (az webapp show --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName --query id --output tsv).Trim()
if ($webId -notmatch '/resourceGroups/rg-sayaraforce-staging/providers/Microsoft.Web/sites/app-sayaraforce-staging$') {
    throw 'Exact staging Web App identity was not verified.'
}

$existing = az ad app list --display-name $displayName --query '[0].id' --output tsv
if ($existing) { throw "Refused: an Entra application named '$displayName' already exists." }

$application = az ad app create --display-name $displayName --sign-in-audience AzureADMyOrg `
    --query '{appId:appId,id:id}' --output json | ConvertFrom-Json
$servicePrincipal = az ad sp create --id $application.appId --query '{id:id,appId:appId}' --output json | ConvertFrom-Json

$credential = @{
    name = 'github-environment-sayaraforce-staging'
    issuer = 'https://token.actions.githubusercontent.com'
    subject = "repo:$GitHubOwner/$GitHubRepository`:environment:sayaraforce-staging"
    description = 'SayaraForce staging GitHub environment only'
    audiences = @('api://AzureADTokenExchange')
}
$temporaryRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('sayaraforce-oidc-' + [guid]::NewGuid().ToString('N'))
$credentialFile = Join-Path $temporaryRoot 'credential.json'
New-Item -ItemType Directory -Path $temporaryRoot | Out-Null
try {
    $credential | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $credentialFile -Encoding utf8
    az ad app federated-credential create --id $application.id --parameters "@$credentialFile" --only-show-errors --output none
}
finally {
    if (Test-Path -LiteralPath $credentialFile) { [System.IO.File]::Delete($credentialFile) }
    if (Test-Path -LiteralPath $temporaryRoot) { [System.IO.Directory]::Delete($temporaryRoot, $true) }
}

az role assignment create --assignee-object-id $servicePrincipal.id --assignee-principal-type ServicePrincipal `
    --role 'Website Contributor' --scope $webId --only-show-errors --output none

$assignments = @(az role assignment list --assignee-object-id $servicePrincipal.id --all --output json | ConvertFrom-Json)
if ($assignments.Count -ne 1 -or $assignments[0].scope -ne $webId) {
    throw 'Deployment identity scope verification failed. Review and remove any unexpected assignment.'
}

Write-Host 'Created a secretless GitHub OIDC identity with staging-Web-App scope only.'
Write-Host "Set AZURE_STAGING_CLIENT_ID to: $($application.appId)"
Write-Host "Set AZURE_STAGING_TENANT_ID to: $($account.tenantId)"
Write-Host "Set AZURE_STAGING_SUBSCRIPTION_ID to: $SubscriptionId"
Write-Host 'These identifiers are not credentials; no client secret or publish profile was created.'
