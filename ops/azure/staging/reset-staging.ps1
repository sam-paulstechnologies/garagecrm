[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $SubscriptionId,
    [Parameter(Mandatory = $true)] [ValidateSet('RESET-STAGING')] [string] $Confirmation
)

$ErrorActionPreference = 'Stop'
$resourceGroup = 'rg-sayaraforce-staging'
$webAppName = 'app-sayaraforce-staging'

if ($Confirmation -ne 'RESET-STAGING') { throw 'Reset confirmation did not match.' }
if (-not (Get-Command az -ErrorAction SilentlyContinue)) { throw 'Azure CLI is required.' }

$accountId = (az account show --query id --output tsv).Trim()
if (-not [string]::Equals($accountId, $SubscriptionId, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Subscription mismatch.'
}

$webId = (az webapp show --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName --query id --output tsv).Trim()
if ($webId -notmatch '/resourceGroups/rg-sayaraforce-staging/providers/Microsoft.Web/sites/app-sayaraforce-staging$') {
    throw 'Reset refused: exact staging target was not verified.'
}

$token = (az account get-access-token --resource https://management.azure.com/ --query accessToken --output tsv).Trim()
if (-not $token) { throw 'Could not acquire a short-lived Entra token for staging Kudu.' }
$headers = @{ Authorization = "Bearer $token" }
$body = @{ command = 'php artisan staging:reset --confirm=RESET-STAGING --no-interaction'; dir = '/home/site/wwwroot' } | ConvertTo-Json
$result = Invoke-RestMethod -Method Post -Uri "https://$webAppName.scm.azurewebsites.net/api/command" `
    -Headers $headers -ContentType 'application/json' -Body $body
$token = $null

if ([int] $result.ExitCode -ne 0) {
    Write-Error ($result.Error -replace '(?i)(password|token|secret)=[^\s]+', '$1=[REDACTED]')
    throw 'Staging reset failed closed. No production target was addressed.'
}

Write-Host 'Staging database reset and synthetic seed completed.'
Write-Host 'Initial passwords remain in the staging Key Vault and were not printed.'
