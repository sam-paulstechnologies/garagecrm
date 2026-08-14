<?php

declare(strict_types=1);

$output = null;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--json=')) {
        $output = substr($argument, 7);
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
];

$excludedPrefixes = ['vendor/', 'node_modules/', 'storage/', 'public/build/'];
$findings = [];

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
        $findings[] = ['file' => $normalized, 'line' => 1, 'rule' => 'forbidden-secret-file'];

        continue;
    }
    if (! is_file($file) || filesize($file) > 2 * 1024 * 1024) {
        continue;
    }
    $contents = file_get_contents($file);
    if (! is_string($contents) || str_contains($contents, "\0")) {
        continue;
    }
    foreach (preg_split('/\R/', $contents) ?: [] as $index => $line) {
        foreach ($rules as $rule => $pattern) {
            if (in_array($rule, ['stripe-secret', 'stripe-webhook-secret'], true)
                && preg_match('/(?:fixture|example|placeholder)/i', $line) === 1) {
                continue;
            }
            if (preg_match($pattern, $line) === 1) {
                $findings[] = ['file' => $normalized, 'line' => $index + 1, 'rule' => $rule];
            }
        }
    }
}

$report = [
    'schema' => 'sayaraforce-secret-scan/v1',
    'generated_at' => gmdate(DATE_ATOM),
    'tracked_files_scanned' => count(array_filter(explode("\0", $tracked))),
    'finding_count' => count($findings),
    'findings' => $findings,
];

if ($output) {
    $directory = dirname($output);
    if (! is_dir($directory)) {
        mkdir($directory, 0770, true);
    }
    file_put_contents($output, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
}

if ($findings !== []) {
    foreach ($findings as $finding) {
        fwrite(STDERR, sprintf("Secret scan finding: %s:%d (%s)\n", $finding['file'], $finding['line'], $finding['rule']));
    }
    exit(1);
}

fwrite(STDOUT, "Secret scan passed; no high-confidence secret patterns were found.\n");
