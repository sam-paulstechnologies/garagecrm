<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Security\SecurityStepUpController;
use App\Http\Controllers\Security\TwoFactorAdministrativeResetController;
use App\Http\Controllers\Security\TwoFactorController;
use App\Http\Middleware\EnsurePublicRegistrationEnabled;
use Illuminate\Support\Facades\Route;

Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
    ->name('two-factor.login');
Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('two-factor.login.store');

Route::middleware('guest')->group(function () {
    Route::middleware(EnsurePublicRegistrationEnabled::class)->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');

        Route::post('register', [RegisteredUserController::class, 'store'])
            ->middleware('throttle:5,1');
    });

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])
        ->middleware('security.step-up')->name('password.update');

    Route::prefix('security')->name('security.')->group(function () {
        Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
        Route::post('two-factor/enable', [TwoFactorController::class, 'enable'])
            ->middleware('password.confirm')->name('two-factor.enable');
        Route::post('two-factor/confirm', [TwoFactorController::class, 'confirm'])
            ->middleware('password.confirm')->name('two-factor.confirm');
        Route::get('two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])
            ->name('two-factor.recovery-codes');
        Route::post('two-factor/recovery-codes/acknowledge', [TwoFactorController::class, 'acknowledgeRecoveryCodes'])
            ->name('two-factor.recovery-codes.acknowledge');
        Route::post('two-factor/recovery-codes/regenerate', [TwoFactorController::class, 'regenerate'])
            ->middleware('security.step-up')->name('two-factor.recovery-codes.regenerate');
        Route::delete('two-factor', [TwoFactorController::class, 'disable'])
            ->middleware('security.step-up')->name('two-factor.disable');

        Route::get('step-up', [SecurityStepUpController::class, 'show'])->name('step-up.show');
        Route::post('step-up', [SecurityStepUpController::class, 'store'])
            ->middleware('throttle:10,1')->name('step-up.store');

        Route::post('users/{user}/two-factor/reset', TwoFactorAdministrativeResetController::class)
            ->middleware('security.step-up')->name('two-factor.admin-reset');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
