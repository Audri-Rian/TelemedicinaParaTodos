<?php

use App\Http\Controllers\Settings\BugReportController;
use App\Http\Controllers\Settings\ConnectedAccountsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/bug-report', [BugReportController::class, 'index'])->name('bug-report.index');

    // 2FA settings
    Route::get('settings/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('settings/two-factor/enable', [TwoFactorController::class, 'enable'])
        ->middleware('password.confirm')
        ->name('two-factor.enable');
    Route::post('settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])
        ->middleware('password.confirm')
        ->name('two-factor.confirm');
    Route::delete('settings/two-factor', [TwoFactorController::class, 'destroy'])
        ->middleware('password.confirm')
        ->name('two-factor.destroy');
    Route::post('settings/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
        ->middleware('password.confirm')
        ->name('two-factor.recovery-codes.regenerate');

    // Contas conectadas
    Route::get('settings/connected-accounts', [ConnectedAccountsController::class, 'show'])
        ->name('connected-accounts.show');
    Route::post('settings/connected-accounts/google', [ConnectedAccountsController::class, 'redirectToGoogle'])
        ->middleware('password.confirm')
        ->name('connected-accounts.google.redirect');
    Route::delete('settings/connected-accounts/google', [ConnectedAccountsController::class, 'destroyGoogle'])
        ->middleware('password.confirm')
        ->name('connected-accounts.google.destroy');

    // Criar senha (conta social-only)
    Route::post('settings/password/create', [PasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.create');
});

// Rotas de avatar
Route::middleware('auth')->prefix('api/avatar')->name('avatar.')->group(function () {
    Route::post('upload', [\App\Http\Controllers\AvatarController::class, 'upload'])->name('upload');
    Route::delete('delete', [\App\Http\Controllers\AvatarController::class, 'delete'])->name('delete');
    Route::get('show', [\App\Http\Controllers\AvatarController::class, 'show'])->name('show');
});
