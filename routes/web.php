<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// HOME — Coming Soon (single root route)
Route::view('/', 'coming-soon');

// Public API test
Route::get('/test-connection', fn () =>
    response()->json(['message' => 'Garage CRM public API test working!'])
);

// React app catch route for templates module
Route::get('/admin/templates/{any?}', fn () => view('app'))
    ->where('any', '.*');

// Authenticated redirections
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

// Load modular route files
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/tenant.php';
require __DIR__.'/mechanic.php';
