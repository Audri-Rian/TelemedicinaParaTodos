<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\ConsultationsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\VideoCall\VideoCallController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('appointments', [AppointmentsController::class, 'index'])->name('appointments');
    Route::get('consultations', [ConsultationsController::class, 'index'])->name('consultations');
    Route::get('healthRecords', [HealthController::class, 'index'])->name('healthRecords');
    
    // Rotas para especializações
    Route::resource('specializations', SpecializationController::class);
    
    // Rotas para videoconferência
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->name('video-call.request-status');
    
});

// Rotas públicas para API de especializações
Route::prefix('api')->group(function () {
    Route::get('specializations/list', [SpecializationController::class, 'list'])->name('api.specializations.list');
    Route::get('specializations/options', [SpecializationController::class, 'options'])->name('api.specializations.options');
});


Route::get('font-test', function () {
    return Inertia::render('FontTest');
})->name('font-test');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
