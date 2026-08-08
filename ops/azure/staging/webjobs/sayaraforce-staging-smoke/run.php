<?php

declare(strict_types=1);

use App\Models\User;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

chdir('/home/site/wwwroot');
require '/home/site/wwwroot/vendor/autoload.php';
$app = require '/home/site/wwwroot/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (app()->environment() !== 'staging' || env('WEBSITE_SITE_NAME') !== 'app-sayaraforce-staging') {
    fwrite(STDERR, "Refused: staging smoke-test identity check failed.\n");
    exit(40);
}

$baseUrl = rtrim((string) config('app.url'), '/').'/';
$accounts = [
    'tenant_a' => [env('STAGING_GARAGE_ADMIN_EMAIL'), env('STAGING_GARAGE_ADMIN_PASSWORD')],
    'tenant_b' => [env('STAGING_TENANT_B_ADMIN_EMAIL'), env('STAGING_TENANT_B_ADMIN_PASSWORD')],
];
$originalPasswordFlags = [];

/** @return array{client: HttpClient, user: User} */
function authenticatedClient(string $baseUrl, string $email, string $password): array
{
    $user = User::query()->where('email', $email)->firstOrFail();
    $jar = new CookieJar();
    $client = new HttpClient([
        'base_uri' => $baseUrl,
        'cookies' => $jar,
        'allow_redirects' => false,
        'http_errors' => false,
        'timeout' => 30,
        'connect_timeout' => 15,
    ]);
    $login = $client->get('login');
    $loginHtml = (string) $login->getBody();
    $hasToken = preg_match('/name=["\']_token["\'][^>]*value=["\']([^"\']+)/', $loginHtml, $match)
        || preg_match('/name=["\']csrf-token["\'][^>]*content=["\']([^"\']+)/', $loginHtml, $match);
    if ($login->getStatusCode() !== 200 || ! $hasToken) {
        throw new RuntimeException('Synthetic login form or CSRF token was unavailable.');
    }
    $response = $client->post('login', [
        'form_params' => ['_token' => html_entity_decode($match[1]), 'email' => $email, 'password' => $password],
    ]);
    if (! in_array($response->getStatusCode(), [302, 303], true)) {
        throw new RuntimeException('Synthetic staging login did not redirect after authentication.');
    }

    return ['client' => $client, 'user' => $user];
}

$exitCode = 0;
try {
    foreach ($accounts as [$email, $password]) {
        if (! is_string($email) || ! is_string($password) || strlen($password) < 20) {
            throw new RuntimeException('A Key Vault-backed synthetic account credential is unavailable.');
        }
        $user = User::query()->where('email', $email)->firstOrFail();
        $originalPasswordFlags[$user->id] = (bool) $user->must_change_password;
        $user->forceFill(['must_change_password' => false])->saveQuietly();
    }

    $tenantA = authenticatedClient($baseUrl, (string) $accounts['tenant_a'][0], (string) $accounts['tenant_a'][1]);
    $tenantB = authenticatedClient($baseUrl, (string) $accounts['tenant_b'][0], (string) $accounts['tenant_b'][1]);
    $surfaces = [
        'dashboard' => 'admin/dashboard',
        'clients' => 'admin/clients',
        'leads' => 'admin/leads',
        'opportunities' => 'admin/opportunities',
        'bookings' => 'admin/bookings',
        'jobs' => 'admin/jobs',
        'invoices' => 'admin/invoices',
        'inbox' => 'admin/inbox',
        'reports' => 'admin/reports/garage-summary',
        'growth' => 'admin/growth/journey-mapping',
        'calendar' => 'admin/calendar',
        'settings' => 'admin/settings',
        'whatsapp_onboarding' => 'admin/messaging/whatsapp',
    ];
    $surfaceResults = [];
    $bannerSeen = false;
    foreach ($surfaces as $name => $path) {
        $response = $tenantA['client']->get($path);
        $status = $response->getStatusCode();
        $location = $response->getHeaderLine('Location');
        if ($status >= 500 || in_array($status, [401, 404], true)
            || ($status >= 300 && str_contains($location, '/login'))) {
            throw new RuntimeException("Application surface failed: {$name} ({$status}).");
        }
        if ($status === 200 && str_contains((string) $response->getBody(), 'STAGING ENVIRONMENT')) {
            $bannerSeen = true;
        }
        if (! str_contains(strtolower($response->getHeaderLine('X-Robots-Tag')), 'noindex')) {
            throw new RuntimeException("Noindex header missing from authenticated surface: {$name}.");
        }
        $surfaceResults[$name] = $status;
    }
    if (! $bannerSeen) {
        throw new RuntimeException('Persistent staging banner was not found on authenticated surfaces.');
    }

    $companyA = (int) $tenantA['user']->company_id;
    $companyB = (int) $tenantB['user']->company_id;
    $clientA = (int) DB::table('clients')->where('company_id', $companyA)->value('id');
    $clientB = (int) DB::table('clients')->where('company_id', $companyB)->value('id');
    if ($clientA < 1 || $clientB < 1 || $companyA === $companyB) {
        throw new RuntimeException('Synthetic tenant-isolation fixtures are invalid.');
    }
    $crossA = $tenantA['client']->get("admin/clients/{$clientB}")->getStatusCode();
    $crossB = $tenantB['client']->get("admin/clients/{$clientA}")->getStatusCode();
    $superAdmin = $tenantA['client']->get('super-admin/messaging-connections')->getStatusCode();
    if (! in_array($crossA, [403, 404], true) || ! in_array($crossB, [403, 404], true)
        || ! in_array($superAdmin, [403, 404], true)) {
        throw new RuntimeException('Live tenant or role isolation smoke test failed.');
    }

    if (config('staging.whatsapp_outbound_enabled') || config('staging.sms_outbound_enabled')
        || config('mail.default') !== 'log') {
        throw new RuntimeException('Outbound communication safeguards are not fail-closed.');
    }

    echo json_encode([
        'status' => 'passed',
        'environment' => 'staging',
        'login' => 200,
        'registration' => (new HttpClient(['base_uri' => $baseUrl, 'http_errors' => false, 'timeout' => 30]))->get('register')->getStatusCode(),
        'surfaces' => $surfaceResults,
        'staging_banner' => true,
        'noindex' => true,
        'tenant_a_cannot_view_tenant_b' => true,
        'tenant_b_cannot_view_tenant_a' => true,
        'tenant_cannot_view_super_admin' => true,
        'whatsapp_outbound' => false,
        'sms_outbound' => false,
        'mail_driver' => 'log',
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Staging smoke test failed: '.$exception->getMessage().PHP_EOL);
    $exitCode = 1;
} finally {
    foreach ($originalPasswordFlags as $userId => $value) {
        User::query()->whereKey($userId)->update(['must_change_password' => $value]);
    }
}

exit($exitCode);
