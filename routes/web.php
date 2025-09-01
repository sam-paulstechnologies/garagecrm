<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public / simple pages
|--------------------------------------------------------------------------
*/

// ▶ ROOT: send users to the app (dashboard if signed in, else login)
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect('/login'); // or ->route('login') if named
});

// Public API test
Route::get('/test-connection', fn () =>
    response()->json(['message' => 'Garage CRM public API test working!'])
);

// React app catch route for templates module
Route::get('/admin/templates/{any?}', fn () => view('app'))
    ->where('any', '.*');

/*
|--------------------------------------------------------------------------
| Health (no DB)
|--------------------------------------------------------------------------
|
| Lightweight health endpoint for Azure App Service.
| Optional token check using HEALTH_CHECK_TOKEN (leave unset for public).
|
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
| One-time Ops: clear caches to remove old "Coming Soon" route
|--------------------------------------------------------------------------
| 1) Set OPS_TOKEN in Azure App Settings (long random string)
| 2) Hit /_ops/flush?t=YOUR_TOKEN once after deploy
| 3) REMOVE this route after use
*/
Route::get('/_ops/flush', function (Request $r) {
    $token = env('OPS_TOKEN');
    abort_unless($token && hash_equals($token, (string) $r->query('t')), 403);

    // Clear all Laravel caches
    Artisan::call('optimize:clear');
    return nl2br(e(Artisan::output() ?: 'Caches cleared'));
});

/*
|--------------------------------------------------------------------------
| Admin-only DB counts (optional)
|--------------------------------------------------------------------------
*/
Route::get('/db-counts', function () {
    try {
        $counts = DB::selectOne("
            SELECT
              (SELECT COUNT(*) FROM users)     AS users,
              (SELECT COUNT(*) FROM clients)   AS clients,
              (SELECT COUNT(*) FROM leads)     AS leads,
              (SELECT COUNT(*) FROM bookings)  AS bookings
        ");
        return response()->json(['ok' => true, 'counts' => $counts]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated redirects
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();
        return match ($user->role) {
            'admin'    => redirect()->route('admin.dashboard'),
            'mechanic' => redirect()->route('mechanic.dashboard'),
            'tenant'   => redirect()->route('tenant.dashboard'),
            default    => abort(403, 'Unauthorized'),
        };
    })->name('dashboard');

    Route::get('/home', function () {
        $user = Auth::user();
        return match ($user->role) {
            'admin'    => redirect()->route('admin.dashboard'),
            'mechanic' => redirect()->route('mechanic.dashboard'),
            'tenant'   => redirect()->route('tenant.dashboard'),
            default    => abort(403, 'Unauthorized'),
        };
    });
});

// Role test route
Route::get('/test-role', fn () => 'You have access!')
    ->middleware(['auth', 'role:admin']);

/*
|--------------------------------------------------------------------------
| Module route files
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/tenant.php';
require __DIR__.'/mechanic.php';
