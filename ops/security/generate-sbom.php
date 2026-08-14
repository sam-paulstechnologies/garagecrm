<?php

declare(strict_types=1);

$output = 'storage/app/security/sbom.cdx.json';
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--output=')) {
        $output = substr($argument, 9);
    }
}

$components = [];
$composer = json_decode((string) file_get_contents('composer.lock'), true, flags: JSON_THROW_ON_ERROR);
foreach (array_merge($composer['packages'] ?? [], $composer['packages-dev'] ?? []) as $package) {
    $components[] = [
        'type' => 'library',
        'name' => $package['name'],
        'version' => $package['version'],
        'purl' => 'pkg:composer/'.str_replace('/', '%2F', $package['name']).'@'.rawurlencode($package['version']),
        'scope' => in_array($package, $composer['packages-dev'] ?? [], true) ? 'optional' : 'required',
    ];
}

$npm = json_decode((string) file_get_contents('package-lock.json'), true, flags: JSON_THROW_ON_ERROR);
foreach (($npm['packages'] ?? []) as $path => $package) {
    if ($path === '' || empty($package['version'])) {
        continue;
    }
    $name = $package['name'] ?? preg_replace('#^node_modules/#', '', $path);
    if (! is_string($name) || $name === '') {
        continue;
    }
    $components[] = [
        'type' => 'library',
        'name' => $name,
        'version' => $package['version'],
        'purl' => 'pkg:npm/'.str_replace('@', '%40', $name).'@'.rawurlencode($package['version']),
        'scope' => ! empty($package['dev']) ? 'optional' : 'required',
    ];
}

$uuid = bin2hex(random_bytes(16));
usort($components, fn (array $left, array $right): int => [$left['name'], $left['version']] <=> [$right['name'], $right['version']]);
$bom = [
    'bomFormat' => 'CycloneDX',
    'specVersion' => '1.5',
    'serialNumber' => 'urn:uuid:'.substr($uuid, 0, 8).'-'.substr($uuid, 8, 4).'-'.substr($uuid, 12, 4).'-'.substr($uuid, 16, 4).'-'.substr($uuid, 20, 12),
    'version' => 1,
    'metadata' => [
        'timestamp' => gmdate(DATE_ATOM),
        'component' => ['type' => 'application', 'name' => 'SayaraForce', 'version' => trim((string) shell_exec('git rev-parse HEAD'))],
    ],
    'components' => $components,
];

if (! is_dir(dirname($output))) {
    mkdir(dirname($output), 0770, true);
}
file_put_contents($output, json_encode($bom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
fwrite(STDOUT, sprintf("SBOM generated with %d components.\n", count($components)));
