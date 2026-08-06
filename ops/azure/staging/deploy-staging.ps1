[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $SubscriptionId,
    [switch] $ConfirmStagingDeployment
)

$ErrorActionPreference = 'Stop'
$resourceGroup = 'rg-sayaraforce-staging'
$webAppName = 'app-sayaraforce-staging'
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..\..')).Path

if (-not $ConfirmStagingDeployment) {
    throw 'Deployment refused. Re-run with -ConfirmStagingDeployment after reviewing the exact staging commit.'
}
if (-not (Get-Command az -ErrorAction SilentlyContinue)) {
    throw 'Azure CLI is required. No deployment was attempted.'
}

Push-Location $repositoryRoot
$originalProcessAppKey = [Environment]::GetEnvironmentVariable('APP_KEY', 'Process')
try {
    $branch = (git branch --show-current).Trim()
    $commit = (git rev-parse HEAD).Trim()
    if ($branch -ne 'staging') {
        throw "Deployment refused: current branch is '$branch', not staging."
    }
    if (git status --porcelain) {
        throw 'Deployment refused: the working tree is not clean.'
    }

    $accountId = (az account show --query id --output tsv).Trim()
    if (-not [string]::Equals($accountId, $SubscriptionId, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Deployment refused: authenticated subscription does not match SubscriptionId.'
    }

    $web = az webapp show --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
        --query '{id:id,name:name,state:state,host:defaultHostName}' --output json | ConvertFrom-Json
    if (-not $web -or $web.name -ne $webAppName -or $web.id -notmatch '/resourceGroups/rg-sayaraforce-staging/') {
        throw 'Deployment refused: exact staging Web App identity was not verified.'
    }

    $schemaReady = (az webapp config appsettings list --subscription $SubscriptionId --resource-group $resourceGroup `
        --name $webAppName --query "[?name=='STAGING_SCHEMA_BASELINE_APPROVED'].value | [0]" --output tsv).Trim()
    if ($schemaReady -ne 'true') {
        throw 'Deployment refused: the reviewed, data-free staging schema baseline is not approved.'
    }

    $testKeyBytes = New-Object byte[] 32
    $testKeyGenerator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $testKeyGenerator.GetBytes($testKeyBytes)
    }
    finally {
        $testKeyGenerator.Dispose()
    }
    [Environment]::SetEnvironmentVariable('APP_KEY', 'base64:' + [Convert]::ToBase64String($testKeyBytes), 'Process')

    composer install --prefer-dist --no-interaction --optimize-autoloader
    php artisan config:clear
    php artisan test tests/Feature/SelfServiceWhatsAppOnboardingTest.php tests/Feature/StagingSafetyTest.php --no-ansi
    php artisan test --no-ansi
    npm ci --no-audit --no-fund
    npm run build
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

    $phpFiles = Get-ChildItem app, config, database, routes -Recurse -Filter *.php
    foreach ($file in $phpFiles) {
        php -l $file.FullName | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "PHP lint failed: $($file.FullName)" }
    }

    $temporaryRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('sayaraforce-stage-deploy-' + [guid]::NewGuid().ToString('N'))
    $packageRoot = Join-Path $temporaryRoot 'package'
    $zipPath = Join-Path $temporaryRoot 'release.zip'
    New-Item -ItemType Directory -Path $packageRoot | Out-Null

    try {
        foreach ($path in @('app','bootstrap','config','database','ops','public','resources\views','routes','vendor')) {
            if (Test-Path $path) { Copy-Item $path -Destination (Join-Path $packageRoot $path) -Recurse -Force }
        }
        foreach ($file in @('artisan','composer.json','composer.lock')) {
            Copy-Item $file -Destination $packageRoot -Force
        }
        $queueTarget = Join-Path $packageRoot 'App_Data\jobs\continuous\sayaraforce-staging-queue'
        New-Item -ItemType Directory -Path $queueTarget -Force | Out-Null
        Copy-Item 'ops\azure\staging\webjobs\sayaraforce-staging-queue\run.sh' -Destination $queueTarget -Force
        Copy-Item 'ops\azure\staging\webjobs\sayaraforce-staging-queue\settings.job' -Destination $queueTarget -Force
        $schedulerTarget = Join-Path $packageRoot 'App_Data\jobs\continuous\sayaraforce-staging-scheduler'
        New-Item -ItemType Directory -Path $schedulerTarget -Force | Out-Null
        Copy-Item 'ops\azure\staging\webjobs\sayaraforce-staging-scheduler\run.sh' -Destination $schedulerTarget -Force
        Copy-Item 'ops\azure\staging\webjobs\sayaraforce-staging-scheduler\settings.job' -Destination $schedulerTarget -Force
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\framework\cache') -Force | Out-Null
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\framework\sessions') -Force | Out-Null
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\framework\views') -Force | Out-Null
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\logs') -Force | Out-Null
        Compress-Archive -Path (Join-Path $packageRoot '*') -DestinationPath $zipPath -CompressionLevel Optimal

        az webapp deploy --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
            --src-path $zipPath --type zip --clean true --restart false --track-status true --only-show-errors --output none

        $token = (az account get-access-token --resource https://management.azure.com/ --query accessToken --output tsv).Trim()
        if (-not $token) { throw 'Could not acquire a short-lived Entra token for staging Kudu.' }
        $headers = @{ Authorization = "Bearer $token" }
        $body = @{ command = 'bash ops/azure/staging/post-deploy.sh'; dir = '/home/site/wwwroot' } | ConvertTo-Json
        $commandResult = Invoke-RestMethod -Method Post -Uri "https://$webAppName.scm.azurewebsites.net/api/command" `
            -Headers $headers -ContentType 'application/json' -Body $body
        $token = $null
        if ([int] $commandResult.ExitCode -ne 0) {
            Write-Error ($commandResult.Error -replace '(?i)(password|token|secret)=[^\s]+', '$1=[REDACTED]')
            throw 'Staging post-deployment command failed.'
        }

        $deployedAt = (Get-Date).ToUniversalTime().ToString('o')
        az webapp config appsettings set --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
            --settings DEPLOYED_BRANCH=staging DEPLOYED_COMMIT=$commit DEPLOYED_AT=$deployedAt --only-show-errors --output none

        az webapp restart --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName --only-show-errors
        $health = Invoke-WebRequest -Uri "https://$($web.host)/healthz" -UseBasicParsing -TimeoutSec 60
        if ($health.StatusCode -ne 200) { throw 'Staging health check failed.' }
    }
    finally {
        if (Test-Path -LiteralPath $temporaryRoot) {
            [System.IO.Directory]::Delete($temporaryRoot, $true)
        }
    }

    Write-Host "Staging deployment complete: $commit"
    Write-Host "Health: https://$($web.host)/healthz"
}
finally {
    [Environment]::SetEnvironmentVariable('APP_KEY', $originalProcessAppKey, 'Process')
    Pop-Location
}
