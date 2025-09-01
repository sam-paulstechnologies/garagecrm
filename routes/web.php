<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
| Health / DB checks
|--------------------------------------------------------------------------
*/

// Lightweight DB ping (public for quick smoke test)
// Remove or protect after UAT.
Route::get('/db-ping', function () {
    try {
        DB::connection()->getPdo(); // forces connection
        $db  = DB::getDatabaseName();
        $ver = optional(DB::select('SELECT VERSION() AS v')[0])->v;

        return response()->json([
            'ok'       => true,
            'database' => $db,
            'version'  => $ver,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok'    => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Quick counts of key tables (behind auth)
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
