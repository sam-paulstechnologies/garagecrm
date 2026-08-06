[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $SubscriptionId,
    [switch] $ConfirmStagingDomainBinding
)

$ErrorActionPreference = 'Stop'
$resourceGroup = 'rg-sayaraforce-staging'
$webAppName = 'app-sayaraforce-staging'
$hostname = 'staging.sayaraforce.com'

if (-not $ConfirmStagingDomainBinding) {
    throw 'Review the staging CNAME first, then re-run with -ConfirmStagingDomainBinding.'
}
if (-not (Get-Command az -ErrorAction SilentlyContinue)) { throw 'Azure CLI is required.' }

$accountId = (az account show --query id --output tsv).Trim()
if (-not [string]::Equals($accountId, $SubscriptionId, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Subscription mismatch.'
}

$web = az webapp show --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
    --query '{id:id,name:name,host:defaultHostName}' --output json | ConvertFrom-Json
if (-not $web -or $web.name -ne $webAppName -or $web.id -notmatch '/resourceGroups/rg-sayaraforce-staging/') {
    throw 'Exact staging Web App identity was not verified.'
}

$cname = Resolve-DnsName -Name $hostname -Type CNAME -ErrorAction Stop | Select-Object -First 1
if (-not [string]::Equals($cname.NameHost.TrimEnd('.'), $web.host, [StringComparison]::OrdinalIgnoreCase)) {
    throw "DNS is not ready. Create CNAME 'staging' -> '$($web.host)' without changing any production record."
}

az webapp config hostname add --subscription $SubscriptionId --resource-group $resourceGroup `
    --webapp-name $webAppName --hostname $hostname --only-show-errors --output none

$thumbprint = (az webapp config ssl create --subscription $SubscriptionId --resource-group $resourceGroup `
    --name $webAppName --hostname $hostname --query thumbprint --output tsv).Trim()
if (-not $thumbprint) { throw 'Azure managed certificate creation did not return a thumbprint.' }

az webapp config ssl bind --subscription $SubscriptionId --resource-group $resourceGroup `
    --name $webAppName --certificate-thumbprint $thumbprint --ssl-type SNI --only-show-errors --output none

Write-Host "Staging-only hostname and managed TLS certificate configured: https://$hostname"
Write-Host 'No production DNS record, hostname, or certificate was changed.'
