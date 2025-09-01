<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public / simple pages
|--------------------------------------------------------------------------
*/

// HOME — Coming Soon (single root route)
Route::view('/', 'coming-soon');

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
| Keep a lightweight health endpoint so Azure App Service can probe the app.
| It returns 200 "OK" without touching the database.
| NOTE: If you want to protect it, you can set HEALTH_CHECK_TOKEN in App Settings
| and send header X-Health-Token, but Azure Health Check can't add headers.
| So leave HEALTH_CHECK_TOKEN unset if you use this for Health Check.
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
| Admin-only DB counts (kept)
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
Route::get('/test-role', fn () => 'You have access!')->middleware(['auth', 'role:admin']);

/*
|--------------------------------------------------------------------------
| Module route files
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/tenant.php';
require __DIR__.'/mechanic.php';
