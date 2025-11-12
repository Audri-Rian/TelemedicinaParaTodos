<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

// Rotas de avatar
Route::middleware('auth')->prefix('api/avatar')->name('avatar.')->group(function () {
    Route::post('upload', [\App\Http\Controllers\AvatarController::class, 'upload'])->name('upload');
    Route::delete('delete', [\App\Http\Controllers\AvatarController::class, 'delete'])->name('delete');
    Route::get('show', [\App\Http\Controllers\AvatarController::class, 'show'])->name('show');
});
