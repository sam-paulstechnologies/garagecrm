[CmdletBinding()]
param(
    [switch] $SkipTests
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
Push-Location $root
try {
    New-Item -ItemType Directory -Force storage/app/security | Out-Null
    php ops/security/scan-secrets.php --json=storage/app/security/secret-scan.json
    if ($LASTEXITCODE -ne 0) { throw 'Secret scan failed.' }
    composer validate --strict --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Composer manifest validation failed.' }
    composer audit --locked --format=json | Set-Content storage/app/security/composer-audit.json
    if ($LASTEXITCODE -ne 0) { throw 'Composer advisory scan failed.' }
    npm audit --json | Set-Content storage/app/security/npm-audit.json
    if ($LASTEXITCODE -ne 0) { throw 'npm advisory scan failed.' }
    php ops/security/generate-sbom.php --output=storage/app/security/sbom.cdx.json
    if ($LASTEXITCODE -ne 0) { throw 'SBOM generation failed.' }
    if (-not $SkipTests) {
        php artisan test --no-ansi
        if ($LASTEXITCODE -ne 0) { throw 'Automated tests failed.' }
    }
} finally {
    Pop-Location
}
