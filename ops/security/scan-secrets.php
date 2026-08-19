<?php

declare(strict_types=1);

// Fable remediation (Phase 6): the frontend build output (public/build/) is
// where a bundler-leaked secret would land, yet it was excluded from scanning.
// `--include-build` adds an additive filesystem pass over public/build using a
// high-confidence rule set tuned to avoid the base64 font-glyph false positives
// that a naive EAA/32-hex match produces in minified bundles.

$output = null;
$includeBuild = false;
$buildOutput = null;
$allowlistPath = __DIR__.'/secret-scan-allowlist.json';
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--json=')) {
        $output = substr($argument, 7);
    } elseif ($argument === '--include-build') {
        $includeBuild = true;
    } elseif (str_starts_with($argument, '--build-json=')) {
        $buildOutput = substr($argument, 13);
    } elseif (str_starts_with($argument, '--allowlist=')) {
        $allowlistPath = substr($argument, 12);
    }
}

// Narrow, justified exclusions: exact matched-value strings known to be safe
// (e.g. a documented public identifier). Kept in a reviewed JSON file.
$allowlist = [];
if (is_file($allowlistPath)) {
    $decoded = json_decode((string) file_get_contents($allowlistPath), true);
    if (is_array($decoded)) {
        $allowlist = array_flip(array_map('strval', $decoded));
    }
}

$tracked = shell_exec('git ls-files --cached --others --exclude-standard -z');
if (! is_string($tracked)) {
    fwrite(STDERR, "Unable to enumerate tracked files.\n");
    exit(2);
}

$rules = [
    'stripe-secret' => '/\b(?:sk|rk)_(?:live|test)_[A-Za-z0-9_-]{20,}\b/',
    'stripe-webhook-secret' => '/\bwhsec_[A-Za-z0-9_-]{20,}\b/',
    'meta-access-token' => '/\bEAA[A-Za-z0-9_-]{30,}\b/',
    'github-token' => '/\bgh[pousr]_[A-Za-z0-9_]{30,}\b/',
    'aws-access-key' => '/\b(?:AKIA|ASIA)[A-Z0-9]{16}\b/',
    'private-key' => '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    'laravel-app-key' => '/\bAPP_KEY\s*=\s*base64:[A-Za-z0-9+\/=]{40,}/i',
    'openai-key' => '/\bsk-(?:proj-)?[A-Za-z0-9_-]{20,}\b/',
];

// Build-output rules: high-confidence prefixed secrets that must never be in a
// browser bundle, plus an assignment-context rule that catches a secret bound to
// a telltale key name without matching random base64 font/asset data.
$buildRules = [
    'stripe-secret' => '/\b(?:sk|rk)_(?:live|test)_[A-Za-z0-9_-]{20,}\b/',
    'stripe-webhook-secret' => '/\bwhsec_[A-Za-z0-9_-]{20,}\b/',
    'github-token' => '/\bgh[pousr]_[A-Za-z0-9_]{30,}\b/',
    'aws-access-key' => '/\b(?:AKIA|ASIA)[A-Z0-9]{16}\b/',
    'private-key' => '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    'laravel-app-key' => '/base64:[A-Za-z0-9+\/=]{43}=?/',
    'openai-key' => '/\bsk-(?:proj-)?[A-Za-z0-9_-]{20,}\b/',
    // Bare Meta access token. A real leaked EAA token is long (~150+ chars); the
    // high minimum length + word boundary avoids the base64 font-glyph false
    // positives that a short EAA match produces in minified bundles, while still
    // catching a genuine token that is NOT adjacent to a telltale key name.
    'meta-access-token' => '/\bEAA[A-Za-z0-9_-]{80,}\b/',
    // Meta app secret / generic assigned secret bound to a sensitive key name.
    'assigned-secret' => '/(?:app_?secret|client_?secret|api[_-]?key|access_?token|webhook_?secret|private_?key)["\'\s:=]{1,4}["\']?[A-Za-z0-9_\-]{24,}["\']?/i',
];

$excludedPrefixes = ['vendor/', 'node_modules/', 'storage/', 'public/build/'];

/**
 * @param  array<string,string>  $rules
 * @param  array<string,int>  $allowlist
 * @return list<array{file:string,line:int,rule:string,match:string}>
 */
function scanContents(string $normalized, string $contents, array $rules, array $allowlist): array
{
    $findings = [];
    foreach (preg_split('/\R/', $contents) ?: [] as $index => $line) {
        foreach ($rules as $rule => $pattern) {
            if (preg_match($pattern, $line, $m) === 1) {
                $matched = $m[0];

                // Narrowly-scoped fixture handling for the Stripe rules: skip
                // ONLY when the matched VALUE itself embeds an explicit test
                // fixture marker (e.g. sk_test_fixture_...). A real secret is
                // never skipped merely because the surrounding line mentions
                // "example", and live-mode keys are never skipped at all.
                if (in_array($rule, ['stripe-secret', 'stripe-webhook-secret'], true)
                    && stripos($matched, '_live_') === false
                    && preg_match('/(?:fixture|example|placeholder|sandbox_guard|forbidden|dummy|sample)/i', $matched) === 1) {
                    continue;
                }

                if (isset($allowlist[$matched])) {
                    continue;
                }
                $findings[] = [
                    'file' => $normalized,
                    'line' => $index + 1,
                    'rule' => $rule,
                    // Never emit the full secret; record a short redacted fingerprint only.
                    'match' => substr($matched, 0, 6).'…('.strlen($matched).')',
                ];
            }
        }
    }

    return $findings;
}

$findings = [];
$scannedCount = 0;

foreach (array_filter(explode("\0", $tracked)) as $file) {
    $normalized = str_replace('\\', '/', $file);
    $excluded = false;
    foreach ($excludedPrefixes as $prefix) {
        if (str_starts_with($normalized, $prefix)) {
            $excluded = true;
            break;
        }
    }
    if ($excluded) {
        continue;
    }
    if (basename($normalized) === '.env' || str_ends_with(strtolower($normalized), '.publishsettings')) {
        $findings[] = ['file' => $normalized, 'line' => 1, 'rule' => 'forbidden-secret-file', 'match' => ''];

        continue;
    }
    if (! is_file($file) || filesize($file) > 2 * 1024 * 1024) {
        continue;
    }
    $contents = file_get_contents($file);
    if (! is_string($contents) || str_contains($contents, "\0")) {
        continue;
    }
    $scannedCount++;
    $findings = array_merge($findings, scanContents($normalized, $contents, $rules, $allowlist));
}

// Additive build-output pass (public/build is gitignored, so walk the filesystem).
$buildFindings = [];
$buildScanned = 0;
if ($includeBuild) {
    $buildRoot = 'public/build';
    if (is_dir($buildRoot)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($buildRoot, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }
            $path = str_replace('\\', '/', $fileInfo->getPathname());
            $ext = strtolower($fileInfo->getExtension());
            // Scan text-like bundle assets; skip binary fonts/images that drive FPs.
            if (! in_array($ext, ['js', 'mjs', 'cjs', 'css', 'json', 'map', 'html', 'txt'], true)) {
                continue;
            }
            if ($fileInfo->getSize() > 8 * 1024 * 1024) {
                continue;
            }
            $contents = file_get_contents($fileInfo->getPathname());
            if (! is_string($contents) || str_contains($contents, "\0")) {
                continue;
            }
            $buildScanned++;
            $buildFindings = array_merge($buildFindings, scanContents($path, $contents, $buildRules, $allowlist));
        }
    } else {
        fwrite(STDERR, "Note: --include-build set but public/build does not exist (run the frontend build first).\n");
    }
}

$allFindings = array_merge($findings, $buildFindings);

$report = [
    'schema' => 'sayaraforce-secret-scan/v2',
    'generated_at' => gmdate(DATE_ATOM),
    'tracked_files_scanned' => $scannedCount,
    'build_files_scanned' => $buildScanned,
    'include_build' => $includeBuild,
    'finding_count' => count($allFindings),
    'findings' => $allFindings,
];

foreach ([$output, $buildOutput] as $target) {
    if ($target) {
        $directory = dirname($target);
        if (! is_dir($directory)) {
            mkdir($directory, 0770, true);
        }
        file_put_contents($target, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }
}

if ($allFindings !== []) {
    foreach ($allFindings as $finding) {
        fwrite(STDERR, sprintf(
            "Secret scan finding: %s:%d (%s) %s\n",
            $finding['file'],
            $finding['line'],
            $finding['rule'],
            $finding['match'] ?? ''
        ));
    }
    exit(1);
}

fwrite(STDOUT, sprintf(
    "Secret scan passed; no high-confidence secret patterns were found (%d source%s files scanned).\n",
    $scannedCount + $buildScanned,
    $includeBuild ? ' + build' : ''
));
