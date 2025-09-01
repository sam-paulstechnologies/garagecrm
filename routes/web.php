<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\WelcomeController;

// Public welcome page
Route::get('/', function () {
    return view('welcome');
});

// ✅ Public API test route
Route::get('/test-connection', function () {
    return response()->json(['message' => 'Garage CRM public API test working!']);
});

// React app catch route for template module
Route::get('/admin/templates/{any?}', function () {
    return view('app'); // This should be your React container blade view
})->where('any', '.*');

// Authenticated redirections
Route::middleware('auth')->get('/dashboard', function () {
    $user = Auth::user();
    return match ($user->role) {
        'admin'    => redirect()->route('admin.dashboard'),
        'mechanic' => redirect()->route('mechanic.dashboard'),
        'tenant'   => redirect()->route('tenant.dashboard'),
        default    => abort(403, 'Unauthorized'),
    };
})->name('dashboard');

Route::middleware('auth')->get('/home', function () {
    $user = Auth::user();
    return match ($user->role) {
        'admin'    => redirect()->route('admin.dashboard'),
        'mechanic' => redirect()->route('mechanic.dashboard'),
        'tenant'   => redirect()->route('tenant.dashboard'),
        default    => abort(403, 'Unauthorized'),
    };
});

Route::get('/', function () {
    return view('coming-soon');   // resources/views/coming-soon.blade.php
});

// Role test route
Route::get('/test-role', function () {
    return 'You have access!';
})->middleware(['auth', 'role:admin']);

// Load modular route files
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/tenant.php';
require __DIR__.'/mechanic.php';
