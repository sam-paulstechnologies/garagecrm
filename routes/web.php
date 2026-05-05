<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\PasswordForceController;
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\EmailInboundWebhookController;

/*
|--------------------------------------------------------------------------
| Root Redirect
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect('/login');
});

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

    abort_if(!$companyId, 403);

    $counts = DB::selectOne("
        SELECT
          (SELECT COUNT(*) FROM users WHERE company_id = ?)     AS users,
          (SELECT COUNT(*) FROM clients WHERE company_id = ?)   AS clients,
          (SELECT COUNT(*) FROM leads WHERE company_id = ?)     AS leads,
          (SELECT COUNT(*) FROM bookings WHERE company_id = ?)  AS bookings
    ", [$companyId, $companyId, $companyId, $companyId]);

    return response()->json(['ok' => true, 'counts' => $counts]);
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| Password Force
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'active'])->group(function () {
    Route::get('password/force',  [PasswordForceController::class, 'edit'])->name('password.force.edit');
    Route::post('password/force', [PasswordForceController::class, 'update'])->name('password.force.update');
});

/*
|--------------------------------------------------------------------------
| Dashboard Routing
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'force_password'])->group(function () {

    Route::get('/dashboard', function () {

        $user = Auth::user();

        abort_if(!$user || blank($user->role), 403);

        return match (strtolower(trim((string) $user->role))) {
            'admin'    => redirect()->route('admin.dashboard'),
            'manager'  => redirect()->route('manager.dashboard'),
            'mechanic' => redirect()->route('mechanic.dashboard'),
            'tenant'   => redirect()->route('tenant.dashboard'),
            default    => abort(403),
        };

    })->name('dashboard');

    Route::get('/home', fn () => redirect()->route('dashboard'));
});

/*
|--------------------------------------------------------------------------
| Webhooks
|--------------------------------------------------------------------------
*/
Route::match(['GET','POST'], '/webhooks/twilio/whatsapp',
    [TwilioWhatsAppWebhookController::class, 'handle']
)->withoutMiddleware(VerifyCsrfToken::class);

Route::match(['GET','POST'], '/webhooks/twilio/whatsapp/status',
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
require __DIR__.'/admin.php';
require __DIR__.'/manager.php';
require __DIR__.'/tenant.php';
require __DIR__.'/mechanic.php';
require __DIR__.'/whatsapp.php';