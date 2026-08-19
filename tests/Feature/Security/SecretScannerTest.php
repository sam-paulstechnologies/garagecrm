<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * End-to-end self-tests for ops/security/scan-secrets.php.
 *
 * Synthetic, real-SHAPED credentials are constructed at runtime (never as
 * literals) so this test file itself never trips the repository secret scan.
 */
class SecretScannerTest extends TestCase
{
    private string $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = sys_get_temp_dir().'/sf-secret-scan-'.uniqid();
        mkdir($this->repo, 0777, true);
        $this->git('init -q');
        $this->git('config user.email test@example.test');
        $this->git('config user.name Test');
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->repo);
        parent::tearDown();
    }

    private function git(string $args): void
    {
        exec('git -C '.escapeshellarg($this->repo).' '.$args.' 2>&1');
    }

    private function write(string $relative, string $contents): void
    {
        $path = $this->repo.'/'.$relative;
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $contents);
    }

    /**
     * @return array{0:int,1:string}
     */
    private function runScanner(string $flags = ''): array
    {
        $scanner = base_path('ops/security/scan-secrets.php');
        $cwd = getcwd();
        chdir($this->repo);
        exec('php '.escapeshellarg($scanner).' '.$flags.' 2>&1', $out, $code);
        chdir($cwd);

        return [$code, implode("\n", $out)];
    }

    public function test_real_shaped_live_secret_fails_the_scan(): void
    {
        $liveKey = 'sk_'.'live_'.str_repeat('A', 30);
        $this->write('config.txt', 'stripe secret = '.$liveKey."\n");
        $this->git('add -A');

        [$code, $output] = $this->runScanner();

        $this->assertSame(1, $code, $output);
        $this->assertStringContainsString('stripe-secret', $output);
    }

    public function test_live_secret_is_not_excused_by_the_word_example_on_the_line(): void
    {
        // The previously-broad line skip would have excused this; the matched
        // value has no fixture marker and is a live key, so it must still fail.
        $liveKey = 'sk_'.'live_'.str_repeat('B', 28);
        $this->write('doc.md', '// example configuration: '.$liveKey."\n");
        $this->git('add -A');

        [$code, $output] = $this->runScanner();

        $this->assertSame(1, $code, $output);
        $this->assertStringContainsString('stripe-secret', $output);
    }

    public function test_explicit_test_fixture_marker_in_value_is_allowed(): void
    {
        $fixture = 'sk_'.'test_'.'fixture_'.str_repeat('c', 20);
        $this->write('BillingFixtureTest.php', '$key = "'.$fixture.'";'."\n");
        $this->git('add -A');

        [$code, $output] = $this->runScanner();

        $this->assertSame(0, $code, $output);
    }

    public function test_bare_meta_token_in_build_output_fails_the_build_scan(): void
    {
        $metaToken = 'EAA'.str_repeat('d', 90);
        $this->write('public/build/assets/app.js', 'const t="'.$metaToken.'";'."\n");
        $this->git('add -A');

        [$code, $output] = $this->runScanner('--include-build');

        $this->assertSame(1, $code, $output);
        $this->assertStringContainsString('meta-access-token', $output);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
