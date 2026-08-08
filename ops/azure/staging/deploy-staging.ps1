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
    & (Join-Path $PSScriptRoot 'validate-schema-baseline.ps1')
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
        foreach ($jobName in @('sayaraforce-staging-postdeploy', 'sayaraforce-staging-verify', 'sayaraforce-staging-smoke')) {
            $jobTarget = Join-Path $packageRoot "App_Data\jobs\triggered\$jobName"
            New-Item -ItemType Directory -Path $jobTarget -Force | Out-Null
            Copy-Item "ops\azure\staging\webjobs\$jobName\*" -Destination $jobTarget -Force
        }
        # The scheduler is intentionally not packaged during initial staging bring-up.
        # The queue worker is uploaded only after the guarded post-deployment job succeeds.
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\framework\cache') -Force | Out-Null
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\framework\sessions') -Force | Out-Null
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\framework\views') -Force | Out-Null
        New-Item -ItemType Directory -Path (Join-Path $packageRoot 'storage\logs') -Force | Out-Null
        $tar = Get-Command tar.exe -ErrorAction SilentlyContinue
        if (-not $tar) { throw 'Portable ZIP packaging requires Windows bsdtar (tar.exe).' }
        & $tar.Source -a -c -f $zipPath -C $packageRoot .
        if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $zipPath)) {
            throw 'Portable staging ZIP packaging failed.'
        }
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        $archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
        try {
            if (@($archive.Entries | Where-Object { $_.FullName.Contains('\') }).Count -gt 0) {
                throw 'Portable staging ZIP contains Windows path separators.'
            }
        }
        finally {
            $archive.Dispose()
        }

        az webapp deploy --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
            --src-path $zipPath --type zip --clean true --restart false --track-status true --only-show-errors --output none

        $token = (az account get-access-token --resource https://management.azure.com/ --query accessToken --output tsv).Trim()
        if (-not $token) { throw 'Could not acquire a short-lived Entra token for staging Kudu.' }
        $headers = @{ Authorization = "Bearer $token" }
        $postDeployName = 'sayaraforce-staging-postdeploy'
        $jobDeadline = [DateTime]::UtcNow.AddMinutes(2)
        do {
            Start-Sleep -Seconds 5
            $triggeredJobResult = Invoke-RestMethod -Method Get -Uri "https://$webAppName.scm.azurewebsites.net/api/triggeredwebjobs" `
                -Headers $headers -TimeoutSec 60
            $triggeredJobs = @($triggeredJobResult | ForEach-Object { $_ })
            $postDeployJob = $triggeredJobs | Where-Object { $_.name -eq $postDeployName } | Select-Object -First 1
        } while (-not $postDeployJob -and [DateTime]::UtcNow -lt $jobDeadline)
        if (-not $postDeployJob) { throw 'Staging post-deployment WebJob was not discovered.' }

        $requestedAt = [DateTime]::UtcNow.AddSeconds(-5)
        $runUri = "https://management.azure.com/subscriptions/$SubscriptionId/resourceGroups/$resourceGroup/providers/Microsoft.Web/sites/$webAppName/triggeredwebjobs/$postDeployName/run?api-version=2024-11-01"
        az rest --method post --uri $runUri --output none
        if ($LASTEXITCODE -ne 0) { throw 'Staging post-deployment WebJob could not be started.' }

        $runDeadline = [DateTime]::UtcNow.AddMinutes(10)
        do {
            Start-Sleep -Seconds 5
            $history = Invoke-RestMethod -Method Get -Uri "https://$webAppName.scm.azurewebsites.net/api/triggeredwebjobs/$postDeployName/history" `
                -Headers $headers -TimeoutSec 60
            $run = $history.runs | Where-Object { [DateTime] $_.start_time -ge $requestedAt } |
                Sort-Object start_time -Descending | Select-Object -First 1
        } while ((-not $run -or $run.status -in @('Initializing', 'Running')) -and [DateTime]::UtcNow -lt $runDeadline)
        if (-not $run -or $run.status -ne 'Success') {
            throw 'Staging post-deployment WebJob failed or timed out.'
        }
        $postDeployOutput = (Invoke-WebRequest -Uri $run.output_url -Headers $headers -UseBasicParsing -TimeoutSec 60).Content
        if ($postDeployOutput -notmatch 'Schema fingerprint matches the approved canonical schema\.' `
            -or $postDeployOutput -notmatch 'Staging migration and Laravel cache rebuild completed\.') {
            throw 'Staging post-deployment output did not pass the schema and cache gates.'
        }

        $mkdirBody = @{ command = 'mkdir -p App_Data/jobs/continuous/sayaraforce-staging-queue'; dir = '/home/site/wwwroot' } | ConvertTo-Json
        $mkdirResult = Invoke-RestMethod -Method Post -Uri "https://$webAppName.scm.azurewebsites.net/api/command" `
            -Headers $headers -ContentType 'application/json' -Body $mkdirBody -TimeoutSec 60
        if ([int] $mkdirResult.ExitCode -ne 0) { throw 'Could not create the staging queue WebJob directory.' }
        foreach ($fileName in @('run.sh', 'settings.job')) {
            $sourcePath = Join-Path "ops\azure\staging\webjobs\sayaraforce-staging-queue" $fileName
            $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $sourcePath).Path)
            Invoke-RestMethod -Method Put `
                -Uri "https://$webAppName.scm.azurewebsites.net/api/vfs/site/wwwroot/App_Data/jobs/continuous/sayaraforce-staging-queue/$fileName" `
                -Headers ($headers + @{ 'If-Match' = '*' }) -ContentType 'application/octet-stream' -Body $fileBytes -TimeoutSec 60 | Out-Null
        }
        $token = $null

        $deployedAt = (Get-Date).ToUniversalTime().ToString('o')
        az webapp config appsettings set --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName `
            --settings DEPLOYED_BRANCH=staging DEPLOYED_COMMIT=$commit DEPLOYED_AT=$deployedAt --only-show-errors --output none

        az webapp restart --subscription $SubscriptionId --resource-group $resourceGroup --name $webAppName --only-show-errors
        $healthDeadline = [DateTime]::UtcNow.AddMinutes(8)
        do {
            Start-Sleep -Seconds 10
            try {
                $health = Invoke-WebRequest -Uri "https://$($web.host)/healthz" -UseBasicParsing -TimeoutSec 30
            }
            catch {
                $health = $null
            }
        } while ((!$health -or $health.StatusCode -ne 200) -and [DateTime]::UtcNow -lt $healthDeadline)
        if (-not $health -or $health.StatusCode -ne 200) { throw 'Staging health check failed.' }
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
