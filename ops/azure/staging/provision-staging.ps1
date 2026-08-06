[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $SubscriptionId,
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

function New-StrongSecret([int] $Bytes = 48) {
    $buffer = New-Object byte[] $Bytes
    [System.Security.Cryptography.RandomNumberGenerator]::Fill($buffer)
    return [Convert]::ToBase64String($buffer)
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
Write-Host 'No production command, slot, swap, DNS, callback, migration, restart, or deployment is included.'

if (-not $ConfirmStagingProvision) {
    throw 'Review complete. Re-run with -ConfirmStagingProvision to authorize creation of these new staging resources.'
}

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
        appKey = @{ value = ('base64:' + (New-StrongSecret 32)) }
        webhookVerificationToken = @{ value = (New-StrongSecret 32) }
        platformAdminPassword = @{ value = (New-StrongSecret) }
        garageAdminPassword = @{ value = (New-StrongSecret) }
        employeePassword = @{ value = (New-StrongSecret) }
        tenantBAdminPassword = @{ value = (New-StrongSecret) }
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
Write-Host 'No application code was deployed and no production resource was changed.'
