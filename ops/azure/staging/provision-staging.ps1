[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $SubscriptionId,
    [Parameter(Mandatory = $true)] [string] $TenantId,
    [Parameter(Mandatory = $true)] [string] $ProductionResourceGroup,
    [Parameter(Mandatory = $true)] [string] $Location,
    [Parameter(Mandatory = $true)] [string] $MySqlServerName,
    [Parameter(Mandatory = $true)] [string] $KeyVaultName,
    [string] $StorageAccountName = 'stsayaraforcestaging',
    [string] $DeploymentPrincipalObjectId = '',
    [switch] $ConfirmStagingProvision
)

$ErrorActionPreference = 'Stop'
$resourceGroup = 'rg-sayaraforce-staging'
$planName = 'plan-sayaraforce-staging'
$webAppName = 'app-sayaraforce-staging'
$productionAppName = 'app-sayaraforce'
$templatePath = Join-Path $PSScriptRoot 'main.bicep'

function Assert-StagingName([string] $Name, [string] $Label) {
    if ($Name -notmatch 'staging') {
        throw "$Label '$Name' is unsafe: every staging target must contain 'staging'."
    }
}

function New-RandomBase64([int] $Bytes = 48) {
    $buffer = New-Object byte[] $Bytes
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($buffer) }
    finally { $generator.Dispose() }
    return [Convert]::ToBase64String($buffer)
}

function New-StrongSecret([int] $Bytes = 48) {
    return 'Aa1!' + (New-RandomBase64 $Bytes)
}

function Read-ProductionSetting([string] $Name) {
    $value = az webapp config appsettings list --subscription $SubscriptionId `
        --resource-group $ProductionResourceGroup --name $productionAppName `
        --query "[?name=='$Name'].value | [0]" --output tsv
    if ($LASTEXITCODE -ne 0) {
        throw "Could not read the specific production setting name '$Name' for staging denylist construction."
    }
    if ($null -eq $value) { return '' }
    return ([string] $value).Trim()
}

if (-not (Get-Command az -ErrorAction SilentlyContinue)) {
    throw 'Azure CLI is required. No Azure action was taken.'
}

foreach ($target in @{
    'Resource group' = $resourceGroup
    'App Service Plan' = $planName
    'Web App' = $webAppName
    'MySQL server' = $MySqlServerName
    'Storage account' = $StorageAccountName
    'Key Vault' = $KeyVaultName
}.GetEnumerator()) {
    Assert-StagingName $target.Value $target.Key
}

$account = az account show --query '{id:id,name:name,tenantId:tenantId,state:state}' --output json | ConvertFrom-Json
if (-not $account -or $account.state -ne 'Enabled') {
    throw 'Azure CLI is not authenticated to an enabled subscription.'
}
if (-not [string]::Equals($account.id, $SubscriptionId, [StringComparison]::OrdinalIgnoreCase)) {
    throw "Subscription ambiguity: authenticated subscription '$($account.id)' does not match the explicit SubscriptionId."
}
if (-not [string]::Equals($account.tenantId, $TenantId, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Tenant ambiguity: authenticated tenant does not match the explicit TenantId.'
}

$requiredProviders = @(
    'Microsoft.Web',
    'Microsoft.DBforMySQL',
    'Microsoft.Network',
    'Microsoft.Storage',
    'Microsoft.KeyVault',
    'Microsoft.ManagedIdentity',
    'Microsoft.Insights',
    'Microsoft.OperationalInsights'
)
$unregisteredProviders = @()
foreach ($provider in $requiredProviders) {
    $state = (az provider show --subscription $SubscriptionId --namespace $provider --query registrationState --output tsv).Trim()
    if ($LASTEXITCODE -ne 0) { throw "Could not verify resource-provider registration: $provider." }
    if ($state -ne 'Registered') { $unregisteredProviders += $provider }
}
if ($unregisteredProviders.Count -gt 0) {
    throw ('Provisioning refused before any Azure write. Subscription-level resource-provider registration requires separate approval: ' + ($unregisteredProviders -join ', '))
}

$production = az webapp show --subscription $SubscriptionId --resource-group $ProductionResourceGroup --name $productionAppName `
    --query '{id:id,name:name,location:location,plan:serverFarmId,host:defaultHostName,state:state,kind:kind}' --output json | ConvertFrom-Json
if (-not $production -or $production.name -ne $productionAppName) {
    throw 'The exact production App Service could not be confirmed read-only. No Azure action was taken.'
}
if (-not [string]::Equals(($production.location -replace '\s', ''), ($Location -replace '\s', ''), [StringComparison]::OrdinalIgnoreCase)) {
    throw "Region mismatch: staging location '$Location' differs from audited production location '$($production.location)'."
}

if ((az group exists --subscription $SubscriptionId --name $resourceGroup --output tsv) -eq 'true') {
    throw "Refused: $resourceGroup already exists. This first-provisioning script writes only newly created staging resources."
}

$existingApp = az webapp list --subscription $SubscriptionId --query "[?name=='$webAppName'].id | [0]" --output tsv
if ($existingApp) {
    throw "Refused: $webAppName already exists."
}

$storageAvailability = az storage account check-name --subscription $SubscriptionId --name $StorageAccountName --query nameAvailable --output tsv
if ($storageAvailability -ne 'true') {
    throw "Storage account name '$StorageAccountName' is unavailable. Choose a close staging-specific alternative."
}

Write-Host 'Read-only production baseline confirmed:'
Write-Host "  Subscription: $($account.name) ($($account.id))"
Write-Host "  Production app: $($production.name)"
Write-Host "  Production resource group: $ProductionResourceGroup"
Write-Host "  Production region: $($production.location)"
Write-Host 'Proposed NEW staging-only resources:'
Write-Host "  $resourceGroup / $planName / $webAppName"
Write-Host "  $MySqlServerName / sayaraforce_staging"
Write-Host "  $StorageAccountName / $KeyVaultName"
Write-Host '  vnet-sayaraforce-staging / log-sayaraforce-staging / appi-sayaraforce-staging'
Write-Host '  id-sayaraforce-staging-deploy (GitHub OIDC; staging Web App scope only)'
Write-Host 'No production command, slot, swap, DNS, callback, migration, restart, or deployment is included.'

if (-not $ConfirmStagingProvision) {
    throw 'Review complete. Re-run with -ConfirmStagingProvision to authorize creation of these new staging resources.'
}

$productionDatabaseHost = Read-ProductionSetting 'DB_HOST'
if ([string]::IsNullOrWhiteSpace($productionDatabaseHost)) {
    throw 'Production DB_HOST setting is unavailable; a safe staging database-host denylist cannot be created.'
}
$productionWabaIds = @('META_WABA_ID', 'WA_WABA_ID', 'WHATSAPP_WABA_ID') |
    ForEach-Object { Read-ProductionSetting $_ } |
    Where-Object { -not [string]::IsNullOrWhiteSpace($_) } |
    Select-Object -Unique
$productionPhoneNumberIds = @('META_PHONE_NUMBER_ID', 'WA_PHONE_NUMBER_ID', 'WHATSAPP_PHONE_NUMBER_ID') |
    ForEach-Object { Read-ProductionSetting $_ } |
    Where-Object { -not [string]::IsNullOrWhiteSpace($_) } |
    Select-Object -Unique

$secureParameters = @{
    '$schema' = 'https://schema.management.azure.com/schemas/2019-04-01/deploymentParameters.json#'
    contentVersion = '1.0.0.0'
    parameters = @{
        location = @{ value = $Location }
        mysqlServerName = @{ value = $MySqlServerName }
        keyVaultName = @{ value = $KeyVaultName }
        storageAccountName = @{ value = $StorageAccountName }
        deploymentPrincipalObjectId = @{ value = $DeploymentPrincipalObjectId }
        mysqlAdministratorPassword = @{ value = (New-StrongSecret) }
        appKey = @{ value = ('base64:' + (New-RandomBase64 32)) }
        webhookVerificationToken = @{ value = (New-StrongSecret 32) }
        platformAdminPassword = @{ value = (New-StrongSecret) }
        garageAdminPassword = @{ value = (New-StrongSecret) }
        employeePassword = @{ value = (New-StrongSecret) }
        tenantBAdminPassword = @{ value = (New-StrongSecret) }
        productionDatabaseHostDenylist = @{ value = $productionDatabaseHost }
        productionWabaIdDenylist = @{ value = ($productionWabaIds -join ',') }
        productionPhoneNumberIdDenylist = @{ value = ($productionPhoneNumberIds -join ',') }
    }
}

$temporaryRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('sayaraforce-staging-' + [guid]::NewGuid().ToString('N'))
$parameterFile = Join-Path $temporaryRoot 'secure.parameters.json'
New-Item -ItemType Directory -Path $temporaryRoot | Out-Null

try {
    $secureParameters | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $parameterFile -Encoding utf8

    az group create --subscription $SubscriptionId --name $resourceGroup --location $Location --tags `
        application=SayaraForce environment=staging dataClassification=synthetic-only --only-show-errors --output none

    az deployment group what-if --subscription $SubscriptionId --resource-group $resourceGroup `
        --template-file $templatePath --parameters "@$parameterFile" --only-show-errors

    az deployment group create --subscription $SubscriptionId --resource-group $resourceGroup `
        --name ('sayaraforce-staging-' + (Get-Date -Format 'yyyyMMddHHmmss')) `
        --template-file $templatePath --parameters "@$parameterFile" --only-show-errors --output none
}
finally {
    if (Test-Path -LiteralPath $parameterFile) {
        [System.IO.File]::Delete($parameterFile)
    }
    if (Test-Path -LiteralPath $temporaryRoot) {
        [System.IO.Directory]::Delete($temporaryRoot, $true)
    }
    $secureParameters = $null
}

$web = az webapp show --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
    --query '{id:id,name:name,host:defaultHostName,location:location,plan:serverFarmId,state:state}' --output json | ConvertFrom-Json
if (-not $web -or $web.name -ne $webAppName -or $web.id -notmatch '/resourceGroups/rg-sayaraforce-staging/') {
    throw 'Post-provision verification failed: staging Web App identity is not exact.'
}

Write-Host 'Staging infrastructure created and resource identity verified.'
Write-Host "Azure hostname: $($web.host)"
Write-Host "DNS record required: CNAME staging -> $($web.host)"
Write-Host 'GitHub OIDC identity: id-sayaraforce-staging-deploy (staging Web App scope only)'
Write-Host 'No application code was deployed and no production resource was changed.'
