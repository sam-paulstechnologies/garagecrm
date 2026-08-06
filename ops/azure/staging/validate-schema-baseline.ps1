[CmdletBinding()]
param(
    [string] $DatabaseName = '',
    [switch] $RunDatabaseCycles
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '../../..')).Path
$schemaPath = Join-Path $repositoryRoot 'database/schema/mysql-schema.sql'
$manifestPath = Join-Path $repositoryRoot 'database/schema/mysql-schema.manifest.json'

function Read-DotEnvValue([string] $Name) {
    $envPath = Join-Path $repositoryRoot '.env'
    if (-not (Test-Path -LiteralPath $envPath)) { return $null }
    $line = Get-Content -LiteralPath $envPath | Where-Object { $_ -match "^$([regex]::Escape($Name))=" } | Select-Object -Last 1
    if (-not $line) { return $null }
    $value = $line.Substring($line.IndexOf('=') + 1).Trim()
    if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
        $value = $value.Substring(1, $value.Length - 2)
    }
    return $value
}

function EnvironmentValue([string] $Name) {
    $value = [Environment]::GetEnvironmentVariable($Name, 'Process')
    if ([string]::IsNullOrWhiteSpace($value)) { return Read-DotEnvValue $Name }
    return $value
}

function Find-MySqlClient {
    $command = Get-Command mysql -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    $laragon = Get-ChildItem 'C:\laragon\bin\mysql' -Recurse -Filter mysql.exe -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($laragon) { return $laragon.FullName }
    throw 'mysql client is required for disposable-database validation.'
}

function Invoke-MySql([string] $Sql, [switch] $WithoutDatabase) {
    $arguments = @('--protocol=TCP', '-h', $script:databaseHost, '-P', $script:databasePort, '-u', $script:databaseUser, '--batch', '--skip-column-names', '--raw')
    if (-not $WithoutDatabase) { $arguments += $script:validationDatabase }
    $previousPassword = $env:MYSQL_PWD
    $env:MYSQL_PWD = $script:databasePassword
    try {
        $output = $Sql | & $script:mysqlClient @arguments 2>&1
        if ($LASTEXITCODE -ne 0) { throw 'A local MySQL validation command failed.' }
        return @($output)
    }
    finally {
        if ($null -eq $previousPassword) { Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue }
        else { $env:MYSQL_PWD = $previousPassword }
    }
}

function Set-LaravelValidationEnvironment {
    $values = @{
        APP_ENV = 'staging'
        APP_DEBUG = 'false'
        APP_URL = 'http://localhost'
        DB_CONNECTION = 'mysql'
        DB_HOST = $script:databaseHost
        DB_PORT = $script:databasePort
        DB_DATABASE = $script:validationDatabase
        DB_USERNAME = $script:databaseUser
        DB_PASSWORD = $script:databasePassword
        STAGING_SCHEMA_BASELINE_APPROVED = 'true'
        STAGING_SCHEMA_VALIDATION_MODE = 'true'
        STAGING_SCHEMA_INTEGRATION = 'true'
        STAGING_EXPECTED_HOST = 'localhost'
        STAGING_EXPECTED_DB_DATABASE = $script:validationDatabase
        CACHE_STORE = 'array'
        SESSION_DRIVER = 'array'
        QUEUE_CONNECTION = 'sync'
        MAIL_MAILER = 'array'
    }
    foreach ($item in $values.GetEnumerator()) {
        [Environment]::SetEnvironmentVariable($item.Key, [string] $item.Value, 'Process')
    }
}

function New-InitialPassword {
    $bytes = New-Object byte[] 24
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) }
    finally { $generator.Dispose() }
    return [Convert]::ToBase64String($bytes)
}

function Assert-ForeignKeyIntegrity {
    $metadata = Invoke-MySql @'
SELECT k.TABLE_NAME,k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,k.ORDINAL_POSITION
FROM information_schema.KEY_COLUMN_USAGE k
WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY k.TABLE_NAME,k.CONSTRAINT_NAME,k.ORDINAL_POSITION;
'@
    $records = @($metadata | ForEach-Object {
        $fields = [string] $_ -split "`t"
        [pscustomobject] @{ Table=$fields[0]; Constraint=$fields[1]; Column=$fields[2]; ReferencedTable=$fields[3]; ReferencedColumn=$fields[4] }
    })
    $queries = @()
    foreach ($group in ($records | Group-Object Table,Constraint)) {
        $first = $group.Group[0]
        $join = ($group.Group | ForEach-Object { "c.``$($_.Column)``=p.``$($_.ReferencedColumn)``" }) -join ' AND '
        $notNull = ($group.Group | ForEach-Object { "c.``$($_.Column)`` IS NOT NULL" }) -join ' AND '
        $queries += "SELECT COUNT(*) FROM ``$($first.Table)`` c LEFT JOIN ``$($first.ReferencedTable)`` p ON $join WHERE $notNull AND p.``$($first.ReferencedColumn)`` IS NULL HAVING COUNT(*)>0;"
    }
    $violations = @(Invoke-MySql ($queries -join "`n") | Where-Object { $_ })
    if ($violations.Count -gt 0) { throw 'Foreign-key integrity validation failed.' }
    return $queries.Count
}

function Invoke-ValidationCycle([string] $CycleName) {
    Invoke-MySql "DROP DATABASE IF EXISTS ``$script:validationDatabase``; CREATE DATABASE ``$script:validationDatabase`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" -WithoutDatabase | Out-Null
    $emptyCount = [int] (Invoke-MySql "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE();" | Select-Object -First 1)
    if ($emptyCount -ne 0) { throw "{$CycleName}: recreated database is not empty." }

    php artisan migrate --path="database/migrations/$($script:manifest.pending_migrations[0]).php" --force --no-interaction | Out-Host
    if ($LASTEXITCODE -ne 0) { throw "{$CycleName}: baseline load or pending migration failed." }

    foreach ($key in @('STAGING_PLATFORM_ADMIN_PASSWORD','STAGING_GARAGE_ADMIN_PASSWORD','STAGING_EMPLOYEE_PASSWORD','STAGING_TENANT_B_ADMIN_PASSWORD')) {
        [Environment]::SetEnvironmentVariable($key, (New-InitialPassword), 'Process')
    }
    try {
        php artisan db:seed --class=Database\Seeders\StagingSyntheticSeeder --force --no-interaction | Out-Host
        if ($LASTEXITCODE -ne 0) { throw "{$CycleName}: synthetic seeder failed." }
    }
    finally {
        foreach ($key in @('STAGING_PLATFORM_ADMIN_PASSWORD','STAGING_GARAGE_ADMIN_PASSWORD','STAGING_EMPLOYEE_PASSWORD','STAGING_TENANT_B_ADMIN_PASSWORD')) {
            Remove-Item "Env:$key" -ErrorAction SilentlyContinue
        }
    }

    Invoke-MySql @'
START TRANSACTION;
SET @company_id := (SELECT MIN(id) FROM companies);
SET @lead_id := (SELECT MIN(id) FROM leads WHERE company_id=@company_id);
SET @client_id := (SELECT client_id FROM leads WHERE id=@lead_id);
INSERT INTO message_logs (company_id,lead_id,direction,source,is_ai,channel,body,escalation_reason,ai_confidence,created_at,updated_at)
VALUES (@company_id,@lead_id,'in','ai',1,'whatsapp','Synthetic schema validation only','low_confidence',72.50,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
       (@company_id,@lead_id,'out','human',0,'whatsapp','Synthetic schema validation only',NULL,NULL,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
INSERT INTO opportunities (client_id,lead_id,company_id,title,stage,created_at,updated_at)
SELECT @client_id,@lead_id,@company_id,'Synthetic View Validation Opportunity','booking_confirmed',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
WHERE @client_id IS NOT NULL AND @lead_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM opportunities WHERE lead_id=@lead_id);
INSERT INTO journeys (name,trigger_key,company_id,is_active,created_at,updated_at)
VALUES ('Synthetic View Validation Journey','schema_validation',@company_id,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
SET @journey_id := LAST_INSERT_ID();
INSERT INTO journey_enrollments (company_id,journey_id,enrollable_type,enrollable_id,status,created_at,updated_at)
VALUES (@company_id,@journey_id,'App\\Models\\Client\\Lead',@lead_id,'active',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
COMMIT;
'@ | Out-Null

    $assertions = @(Invoke-MySql @'
SELECT COUNT(*)=110 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE';
SELECT COUNT(*)=2 FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE();
SELECT COUNT(*)=41 FROM migrations;
SELECT COUNT(*)=7 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('messaging_connections','messaging_phone_numbers','messaging_onboarding_sessions','messaging_consents','messaging_connection_checks','messaging_audit_logs','messaging_webhook_events');
SELECT COUNT(*)=2 FROM companies;
SELECT COUNT(*)=2 FROM garages;
SELECT COUNT(*)=4 FROM users;
SELECT COUNT(*)=1 FROM vw_ai_metrics_daily WHERE ai_count>=1 AND human_count>=1;
SELECT COUNT(*)=1 FROM vw_journey_summary WHERE journey_name='Synthetic View Validation Journey' AND total_enrollments=1 AND total_leads=1 AND total_opportunities=1 AND total_closed_won=1;
SELECT COUNT(*)=6 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='jobs' AND COLUMN_NAME IN ('company_id','client_id','booking_id','status','start_time','end_time');
SELECT COUNT(*)=0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='jobs' AND COLUMN_NAME IN ('queue','payload','attempts','reserved_at','available_at');
SELECT COUNT(*)=5 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='queue_jobs' AND COLUMN_NAME IN ('queue','payload','attempts','reserved_at','available_at');
'@)
    if (@($assertions | Where-Object { [string] $_ -ne '1' }).Count -gt 0) { throw "{$CycleName}: canonical schema assertion failed." }

    $foreignKeys = Assert-ForeignKeyIntegrity

    $routes = (php artisan route:list --json 2>&1) -join "`n"
    if ($LASTEXITCODE -ne 0) { throw "{$CycleName}: Laravel route discovery failed." }
    $routeObjects = $routes | ConvertFrom-Json
    $routeCount = $routeObjects.Count

    php artisan test tests/Feature/StagingSchemaBaselineTest.php --filter=disposable --no-ansi | Out-Host
    if ($LASTEXITCODE -ne 0) { throw "{$CycleName}: application-surface test failed." }

    $fingerprintOutput = php artisan staging:schema-fingerprint --no-ansi 2>&1
    if ($LASTEXITCODE -ne 0) { throw "{$CycleName}: structural fingerprint generation failed." }
    $match = [regex]::Match(($fingerprintOutput -join "`n"), '[0-9a-f]{64}')
    if (-not $match.Success) { throw "{$CycleName}: structural fingerprint was not returned." }

    return [ordered] @{
        cycle = $CycleName
        fingerprint = $match.Value
        base_tables = 110
        views = 2
        foreign_keys_checked = $foreignKeys
        routes = $routeCount
        synthetic_tenants = 2
    }
}

Push-Location $repositoryRoot
$savedEnvironment = @{}
$savedPath = $env:PATH
try {
    if (-not (Test-Path -LiteralPath $schemaPath) -or -not (Test-Path -LiteralPath $manifestPath)) {
        throw 'Canonical schema baseline or manifest is missing.'
    }
    $sql = [IO.File]::ReadAllText($schemaPath)
    $script:manifest = Get-Content -Raw -LiteralPath $manifestPath | ConvertFrom-Json

    if ([regex]::Matches($sql, '(?im)^CREATE TABLE `').Count -ne 103) { throw 'Static baseline check failed: expected 103 base tables.' }
    if ([regex]::Matches($sql, '(?im)SQL SECURITY INVOKER VIEW `').Count -ne 2) { throw 'Static baseline check failed: expected two INVOKER views.' }
    if ($sql -match '(?i)DEFINER=' -or $sql -match '(?i)AUTO_INCREMENT=\d+') { throw 'Static baseline check failed: environment identity or counters are present.' }
    if ($sql -match '(?im)^\s*(REPLACE|LOAD\s+DATA|COPY)\b') { throw 'Static baseline check failed: a data-loading statement is present.' }
    $insertTargets = @([regex]::Matches($sql, '(?im)^\s*INSERT\s+INTO\s+`?([^`\s(]+)') | ForEach-Object { $_.Groups[1].Value.ToLowerInvariant() } | Select-Object -Unique)
    if ($insertTargets.Count -ne 1 -or $insertTargets[0] -ne 'migrations') { throw 'Static baseline check failed: non-migration INSERT found.' }
    if ($script:manifest.included_base_table_count -ne 103 -or $script:manifest.included_view_count -ne 2) { throw 'Manifest object counts do not match the baseline.' }
    if (@($script:manifest.included_base_tables).Count -ne 103) { throw 'Manifest base-table inventory is incomplete.' }
    $baselineHash = (Get-FileHash -LiteralPath $schemaPath -Algorithm SHA256).Hash.ToLowerInvariant()
    if (-not [string]::Equals($baselineHash, [string] $script:manifest.baseline_sha256, [StringComparison]::Ordinal)) { throw 'Baseline hash does not match the manifest.' }
    $safetyPath = Join-Path $repositoryRoot 'database/schema/mysql-schema.safety.json'
    $safety = Get-Content -Raw -LiteralPath $safetyPath | ConvertFrom-Json
    if ($safety.status -ne 'passed' -or -not [string]::Equals([string] $safety.baseline_sha256, $baselineHash, [StringComparison]::Ordinal)) { throw 'Machine-readable schema safety report is missing or stale.' }
    if (@($script:manifest.pending_migrations).Count -ne 1 -or $script:manifest.pending_migrations[0] -ne '2026_08_05_000001_create_messaging_core_tables') { throw 'Manifest pending-migration cutoff is not approved.' }

    $trackedMigrations = @(git ls-files 'database/migrations/*.php' | ForEach-Object { [IO.Path]::GetFileNameWithoutExtension($_) })
    $classified = @($script:manifest.represented_migrations) + @($script:manifest.pending_migrations)
    $unclassified = @($trackedMigrations | Where-Object { $_ -notin $classified })
    if ($unclassified.Count -gt 0) { throw 'Tracked migration classification is incomplete.' }

    Write-Host 'Static schema-baseline safety and migration-cutoff checks passed.'
    if (-not $RunDatabaseCycles) { return }

    $script:validationDatabase = $DatabaseName.Trim()
    $script:databaseHost = [string] (EnvironmentValue 'DB_HOST')
    $script:databasePort = [string] (EnvironmentValue 'DB_PORT')
    $script:databaseUser = [string] (EnvironmentValue 'DB_USERNAME')
    $script:databasePassword = [string] (EnvironmentValue 'DB_PASSWORD')
    $developmentDatabase = [string] (Read-DotEnvValue 'DB_DATABASE')

    if ($script:validationDatabase -notmatch '^[A-Za-z0-9_]*staging_validation[A-Za-z0-9_]*$') { throw 'Validation database name must contain staging_validation and use safe identifier characters.' }
    if ($script:validationDatabase -eq $developmentDatabase) { throw 'Validation database must not equal the development database.' }
    if ($script:databaseHost -notin @('127.0.0.1','localhost','::1')) { throw 'Database-cycle validation refuses non-local hosts.' }
    if ([string]::IsNullOrWhiteSpace($script:databaseUser)) { throw 'Local database username is unavailable.' }

    $script:mysqlClient = Find-MySqlClient
    $env:PATH = "$(Split-Path $script:mysqlClient -Parent);$savedPath"
    foreach ($key in @('APP_ENV','APP_DEBUG','APP_URL','DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','STAGING_SCHEMA_BASELINE_APPROVED','STAGING_SCHEMA_VALIDATION_MODE','STAGING_SCHEMA_INTEGRATION','STAGING_EXPECTED_HOST','STAGING_EXPECTED_DB_DATABASE','CACHE_STORE','SESSION_DRIVER','QUEUE_CONNECTION','MAIL_MAILER')) {
        $savedEnvironment[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
    }
    Set-LaravelValidationEnvironment

    $cycleOne = Invoke-ValidationCycle 'cycle-one'
    $cycleTwo = Invoke-ValidationCycle 'cycle-two'
    if (-not [string]::Equals($cycleOne.fingerprint, $cycleTwo.fingerprint, [StringComparison]::Ordinal)) { throw 'Reproducibility failed: structural fingerprints differ.' }

    $result = [ordered] @{
        status = 'passed'
        fingerprint = $cycleTwo.fingerprint
        fingerprints_match = $true
        cycles = @($cycleOne, $cycleTwo)
    }
    $artifactRoot = Join-Path $env:TEMP 'SayaraForceSchemaValidation'
    New-Item -ItemType Directory -Path $artifactRoot -Force | Out-Null
    [IO.File]::WriteAllText((Join-Path $artifactRoot 'validation-result.json'), ($result | ConvertTo-Json -Depth 6), [Text.UTF8Encoding]::new($false))
    $result | ConvertTo-Json -Depth 6
}
finally {
    $env:PATH = $savedPath
    foreach ($item in $savedEnvironment.GetEnumerator()) {
        [Environment]::SetEnvironmentVariable($item.Key, $item.Value, 'Process')
    }
    Pop-Location
}
