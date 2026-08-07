[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $SubscriptionId,
    [Parameter(Mandatory = $true)] [string] $ProductionResourceGroup,
    [string] $ExpectedCommit = ''
)

$ErrorActionPreference = 'Stop'
$resourceGroup = 'rg-sayaraforce-staging'
$webAppName = 'app-sayaraforce-staging'
$productionAppName = 'app-sayaraforce'

if (-not (Get-Command az -ErrorAction SilentlyContinue)) {
    throw 'Azure CLI is required. No verification was performed.'
}

$accountId = (az account show --query id --output tsv).Trim()
if (-not [string]::Equals($accountId, $SubscriptionId, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Verification refused: authenticated subscription does not match SubscriptionId.'
}

$production = az webapp show --subscription $SubscriptionId --resource-group $ProductionResourceGroup --name $productionAppName `
    --query '{id:id,name:name,plan:serverFarmId,host:defaultHostName}' --output json | ConvertFrom-Json
$staging = az webapp show --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
    --query '{id:id,name:name,plan:serverFarmId,host:defaultHostName,httpsOnly:httpsOnly,state:state}' --output json | ConvertFrom-Json

if ($production.name -ne $productionAppName -or $staging.name -ne $webAppName) { throw 'Exact app identities were not verified.' }
if ($staging.id -notmatch '/resourceGroups/rg-sayaraforce-staging/') { throw 'Staging app is outside the staging resource group.' }
if ($staging.plan -eq $production.plan) { throw 'Isolation failure: staging shares the production App Service Plan.' }
if (-not $staging.httpsOnly) { throw 'Isolation failure: HTTPS-only is disabled on staging.' }

$slots = @(az webapp deployment slot list --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName --output json | ConvertFrom-Json)
if ($slots.Count -gt 0) { throw 'Slot policy failure: staging slots exist.' }

$settings = @(az webapp config appsettings list --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName --output json | ConvertFrom-Json)
$settingMap = @{}
foreach ($setting in $settings) { $settingMap[$setting.name] = [string] $setting.value }

$productionSettings = @(az webapp config appsettings list --subscription $SubscriptionId --resource-group $ProductionResourceGroup `
    --name $productionAppName --output json | ConvertFrom-Json)
$productionSettingMap = @{}
foreach ($setting in $productionSettings) { $productionSettingMap[$setting.name] = [string] $setting.value }

$azureAppUrl = "https://$($staging.host)"
$customAppUrl = 'https://staging.sayaraforce.com'
$required = @{
    APP_ENV = 'staging'
    APP_DEBUG = 'false'
    APP_NAME = 'SayaraForce Staging'
    DB_DATABASE = 'sayaraforce_staging'
    QUEUE_CONNECTION = 'database'
    DB_QUEUE_CONNECTION = 'mysql'
    DB_QUEUE_TABLE = 'queue_jobs'
    QUEUE_FAILED_TABLE = 'failed_jobs'
    CACHE_STORE = 'database'
    FILESYSTEM_DISK = 'staging'
    TRUSTED_PROXIES = '*'
    MAIL_MAILER = 'log'
    STAGING_ALLOW_LEGACY_COMPANY_RESOLUTION = 'false'
    STAGING_WHATSAPP_OUTBOUND_ENABLED = 'false'
    STAGING_SMS_OUTBOUND_ENABLED = 'false'
    STAGING_SCHEMA_BASELINE_APPROVED = 'true'
}

if ($settingMap['APP_URL'] -notin @($azureAppUrl, $customAppUrl)) {
    throw 'APP_URL is neither the exact staging Azure hostname nor the approved staging custom domain.'
}
$expectedHost = ([Uri] $settingMap['APP_URL']).Host
if ($settingMap['STAGING_EXPECTED_HOST'] -ne $expectedHost -or $settingMap['SESSION_DOMAIN'] -ne $expectedHost) {
    throw 'Staging host, expected-host, and secure-cookie domain settings are inconsistent.'
}
foreach ($item in $required.GetEnumerator()) {
    if ($settingMap[$item.Key] -ne $item.Value) { throw "Unsafe or missing staging setting: $($item.Key)." }
}

if ($settingMap['DB_HOST'] -notmatch 'staging' -or $settingMap['DB_HOST'] -match 'app-sayaraforce($|\.)') {
    throw 'Database isolation check failed.'
}
if ($settingMap['CACHE_PREFIX'] -notmatch 'staging' -or $settingMap['SESSION_COOKIE'] -notmatch 'staging') {
    throw 'Cache/session namespace isolation check failed.'
}
foreach ($key in @(
    'DB_PASSWORD','APP_KEY','META_WHATSAPP_VERIFY_TOKEN',
    'STAGING_PLATFORM_ADMIN_PASSWORD','STAGING_GARAGE_ADMIN_PASSWORD',
    'STAGING_EMPLOYEE_PASSWORD','STAGING_TENANT_B_ADMIN_PASSWORD'
)) {
    if ($settingMap[$key] -notmatch '^@Microsoft.KeyVault\(') { throw "$key is not a Key Vault reference." }
}

if ($ExpectedCommit -and $settingMap['DEPLOYED_COMMIT'] -ne $ExpectedCommit) {
    throw 'Deployment marker does not match the reviewed staging commit.'
}
if ($settingMap['DEPLOYED_BRANCH'] -ne 'staging' -or $settingMap['DEPLOYED_COMMIT'] -notmatch '^[0-9a-f]{40}$') {
    throw 'Deployment marker is absent or invalid.'
}

foreach ($key in @(
    'DB_HOST','DB_DATABASE','DB_USERNAME','DB_PASSWORD',
    'REDIS_HOST','REDIS_USERNAME','REDIS_PASSWORD',
    'AWS_ACCESS_KEY_ID','AWS_SECRET_ACCESS_KEY','AWS_BUCKET',
    'AZURE_STORAGE_ACCOUNT','AZURE_STORAGE_KEY','AZURE_STORAGE_CONNECTION_STRING',
    'META_ACCESS_TOKEN','META_SYSTEM_USER_ACCESS_TOKEN','META_APP_SECRET','META_WABA_ID','META_PHONE_NUMBER_ID',
    'TWILIO_SID','TWILIO_TOKEN','TWILIO_WHATSAPP_FROM'
)) {
    $stagingValue = [string] $settingMap[$key]
    $productionValue = [string] $productionSettingMap[$key]
    if ($stagingValue -and $productionValue -and [string]::Equals($stagingValue, $productionValue, [StringComparison]::Ordinal)) {
        throw "Production isolation failure: staging reuses production setting $key."
    }
}

$allValues = ($settings | ForEach-Object { [string] $_.value }) -join "`n"
if ($allValues -match '(?i)smart[ -]?matrix') { throw 'A Smart Matrix reference was detected in staging settings.' }

$config = az webapp config show --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName --output json | ConvertFrom-Json
if ($config.autoSwapSlotName) { throw 'Auto-swap must not be configured.' }
if ($config.minTlsVersion -ne '1.2' -or $config.ftpsState -ne 'Disabled') { throw 'TLS/FTPS policy check failed.' }

$health = Invoke-WebRequest -Uri "https://$($staging.host)/healthz" -UseBasicParsing -TimeoutSec 60
if ($health.StatusCode -ne 200) { throw 'Health endpoint failed.' }
$robots = Invoke-WebRequest -Uri "https://$($staging.host)/robots.txt" -UseBasicParsing -TimeoutSec 60
if ($robots.Content -notmatch 'Disallow:\s*/' -or $robots.Headers['X-Robots-Tag'] -notmatch 'noindex') {
    throw 'Robots/noindex policy failed.'
}

$token = (az account get-access-token --resource https://management.azure.com/ --query accessToken --output tsv).Trim()
if (-not $token) { throw 'Could not acquire a short-lived Entra token for staging verification.' }
$headers = @{ Authorization = "Bearer $token" }
$body = @{
    command = 'php artisan staging:schema-fingerprint --verify --no-interaction && php artisan staging:verify-live --json'
    dir = '/home/site/wwwroot'
} | ConvertTo-Json
$liveResult = Invoke-RestMethod -Method Post -Uri "https://$webAppName.scm.azurewebsites.net/api/command" `
    -Headers $headers -ContentType 'application/json' -Body $body
$token = $null
if ([int] $liveResult.ExitCode -ne 0 -or $liveResult.Output -notmatch '"status"\s*:\s*"passed"') {
    throw 'Live staging database and application verification failed.'
}

$continuousJobs = @(az webapp webjob continuous list --subscription $SubscriptionId --resource-group $resourceGroup `
    --name $webAppName --query '[].{name:name,status:status}' --output json | ConvertFrom-Json)
foreach ($jobName in @('sayaraforce-staging-queue', 'sayaraforce-staging-scheduler')) {
    $job = $continuousJobs | Where-Object { $_.name -eq $jobName } | Select-Object -First 1
    if (-not $job -or $job.status -notin @('Running', 'Initializing', 'PendingRestart')) {
        throw "Staging continuous WebJob is absent or unhealthy: $jobName."
    }
}

$assetGuardsReady = $settingMap['STAGING_PRODUCTION_DB_HOST_DENYLIST'] `
    -and $settingMap['STAGING_META_ALLOWED_WABA_IDS'] `
    -and $settingMap['STAGING_META_ALLOWED_PHONE_NUMBER_IDS'] `
    -and $settingMap['STAGING_META_PRODUCTION_WABA_ID_DENYLIST'] `
    -and $settingMap['STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST']

if ($settingMap['STAGING_WHATSAPP_OUTBOUND_ENABLED'] -eq 'true' `
    -and (-not $assetGuardsReady -or -not $settingMap['STAGING_MESSAGE_RECIPIENT_ALLOWLIST'])) {
    throw 'WhatsApp outbound is enabled without complete staging asset and recipient guards.'
}

$uatReady = $settingMap['STAGING_SCHEMA_BASELINE_APPROVED'] -eq 'true' -and $assetGuardsReady

Write-Host 'Staging infrastructure and configuration isolation checks passed.'
Write-Host "Staging host: https://$($staging.host)"
Write-Host "WhatsApp UAT guard configuration complete: $([bool] $uatReady)"
Write-Host 'No secret values were printed.'
