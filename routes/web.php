<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\ConsultationsController;
use App\Http\Controllers\HealthController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('appointments', [AppointmentsController::class, 'index'])->name('appointments');
    Route::get('consultations', [ConsultationsController::class, 'index'])->name('consultations');
    Route::get('healthRecords', [HealthController::class, 'index'])->name('healthRecords');
});


Route::get('font-test', function () {
    return Inertia::render('FontTest');
})->name('font-test');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
