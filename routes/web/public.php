<?php

use App\Http\Controllers\AvatarFileController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\TermsOfServiceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('index');
})->name('home');

// Dashboard redirect baseado no papel do usuário
Route::middleware(['auth', 'verified'])->get('/dashboard', DashboardRedirectController::class)->name('dashboard');

// Termos de Serviço e Política de Privacidade
Route::get('terms', [TermsOfServiceController::class, 'index'])->name('terms');
Route::get('privacy', [PrivacyPolicyController::class, 'index'])->name('privacy');

// Servir avatars do storage
Route::get('storage/avatars/{userId}/{filename}', [AvatarFileController::class, 'show'])
    ->where(['userId' => '[^/]+', 'filename' => '[^/]+'])
    ->name('storage.avatar');

// API pública de especializações e disponibilidade
Route::prefix('api')->middleware('throttle:60,1')->group(function () {
    Route::get('specializations/list', [SpecializationController::class, 'list'])->name('api.specializations.list');
    Route::get('specializations/options', [SpecializationController::class, 'options'])->name('api.specializations.options');
    Route::get('doctors/{doctor}/availability/{date}', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'getByDate'])->name('api.doctors.availability.date');
});
