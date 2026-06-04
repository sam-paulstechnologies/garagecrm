<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PasswordForceController;
use App\Http\Controllers\Webhooks\EmailInboundWebhookController;
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;
use App\Http\Middleware\VerifyCsrfToken;

/*
|--------------------------------------------------------------------------
| Public Landing Page / App Root Redirect
|--------------------------------------------------------------------------
| sayaraforce.com              => Public landing page
| www.sayaraforce.com          => Public landing page
| app.sayaraforce.com          => Redirect to /login
| Azure default app domain     => Redirect to /login
|
| Login remains available at /login.
| Logged-in users should use /dashboard.
|--------------------------------------------------------------------------
*/
Route::get('/', function (Request $request) {
    $host = strtolower((string) $request->getHost());

    $isAppDomain =
        $host === 'app.sayaraforce.com' ||
        str_contains($host, 'azurewebsites.net');

    if ($isAppDomain) {
        return redirect()->to('/login');
    }

    return view('public.home');
})->name('public.home');

/*
|--------------------------------------------------------------------------
| Public Legal Pages
|--------------------------------------------------------------------------
| These must remain publicly accessible without login.
| Required for Meta App Review / WhatsApp Tech Provider review.
|--------------------------------------------------------------------------
*/
Route::view('/privacy-policy', 'legal.privacy-policy')
    ->name('privacy-policy');

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('/healthz', function (Request $request) {
    $token = env('HEALTH_CHECK_TOKEN');

    if ($token && $request->header('X-Health-Token') !== $token) {
        abort(403);
    }

    return response('OK', 200);
});

/*
|--------------------------------------------------------------------------
| Ops Flush
|--------------------------------------------------------------------------
*/
Route::get('/_ops/flush', function (Request $r) {
    abort_unless(app()->environment('local'), 404);

    $token = env('OPS_TOKEN');

    abort_unless($token && hash_equals($token, (string) $r->query('t')), 403);

    Artisan::call('optimize:clear');

    return nl2br(e(Artisan::output() ?: 'Caches cleared'));
});

/*
|--------------------------------------------------------------------------
| DB Counts
|--------------------------------------------------------------------------
*/
Route::get('/db-counts', function () {
    $companyId = (int) (auth()->user()?->company_id ?? auth()->user()?->company?->id ?? 0);

    abort_if(! $companyId, 403);

    $counts = DB::selectOne("
        SELECT
          (SELECT COUNT(*) FROM users WHERE company_id = ?)     AS users,
          (SELECT COUNT(*) FROM clients WHERE company_id = ?)   AS clients,
          (SELECT COUNT(*) FROM leads WHERE company_id = ?)     AS leads,
          (SELECT COUNT(*) FROM bookings WHERE company_id = ?)  AS bookings
    ", [$companyId, $companyId, $companyId, $companyId]);

    return response()->json([
        'ok' => true,
        'counts' => $counts,
    ]);
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| Password Force
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'active'])->group(function () {
    Route::get('password/force', [PasswordForceController::class, 'edit'])
        ->name('password.force.edit');

    Route::post('password/force', [PasswordForceController::class, 'update'])
        ->name('password.force.update');
});

/*
|--------------------------------------------------------------------------
| Dashboard Routing
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'force_password'])->group(function () {

    Route::get('/dashboard', function () {
        $user = Auth::user();

        abort_if(! $user || blank($user->role), 403);

        return match (strtolower(trim((string) $user->role))) {
            'admin'    => redirect()->route('admin.dashboard'),
            'manager'  => redirect()->route('manager.dashboard'),
            'mechanic' => redirect()->route('mechanic.dashboard'),
            'tenant'   => redirect()->route('tenant.dashboard'),
            default    => abort(403),
        };
    })->name('dashboard');

    Route::get('/home', fn () => redirect()->route('dashboard'))
        ->name('home');
});

/*
|--------------------------------------------------------------------------
| Legacy Public Webhooks
|--------------------------------------------------------------------------
| Kept for backward compatibility with already-configured Twilio/email URLs.
|--------------------------------------------------------------------------
*/
Route::match(['GET', 'POST'], '/webhooks/twilio/whatsapp',
    [TwilioWhatsAppWebhookController::class, 'handle']
)->withoutMiddleware(VerifyCsrfToken::class);

Route::match(['GET', 'POST'], '/webhooks/twilio/whatsapp/status',
    [TwilioWhatsAppWebhookController::class, 'status']
)->withoutMiddleware(VerifyCsrfToken::class);

Route::post('/webhooks/email/inbound',
    [EmailInboundWebhookController::class, 'handle']
)->withoutMiddleware(VerifyCsrfToken::class);

/*
|--------------------------------------------------------------------------
| Includes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

if (file_exists(__DIR__.'/admin.php')) {
    require __DIR__.'/admin.php';
}

if (file_exists(__DIR__.'/manager.php')) {
    require __DIR__.'/manager.php';
}

if (file_exists(__DIR__.'/tenant.php')) {
    require __DIR__.'/tenant.php';
}

if (file_exists(__DIR__.'/mechanic.php')) {
    require __DIR__.'/mechanic.php';
}

if (file_exists(__DIR__.'/whatsapp.php')) {
    require __DIR__.'/whatsapp.php';
}
