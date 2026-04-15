<?php

use Illuminate\Support\Facades\Route;

// Rotas para Pacientes
Route::middleware(['auth', 'verified', 'patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Patient\PatientDashboardController::class, 'index'])->name('dashboard');
    Route::get('search-consultations', [App\Http\Controllers\Patient\PatientSearchConsultationsController::class, 'index'])->name('search-consultations');
    Route::get('schedule-consultation', [App\Http\Controllers\Patient\ScheduleConsultationController::class, 'index'])->name('schedule-consultation');
    Route::get('doctor-perfil', [App\Http\Controllers\Patient\DoctorPerfilController::class, 'index'])->name('doctor-perfil');
    Route::get('messages', [App\Http\Controllers\Patient\PatientMessagesController::class, 'index'])->name('messages');
    Route::get('video-call', [App\Http\Controllers\Patient\PatientVideoCallController::class, 'index'])->name('video-call');
    Route::get('history-consultations', [App\Http\Controllers\Patient\PatientHistoryConsultationsController::class, 'index'])->name('history-consultations');
    Route::get('consultation-details/{appointment}', [App\Http\Controllers\Patient\PatientConsultationDetailsController::class, 'show'])->name('consultation-details');
    Route::get('next-consultation', [App\Http\Controllers\Patient\PatientNextConsultationController::class, 'index'])->name('next-consultation');
    Route::get('medical-records', [App\Http\Controllers\Patient\PatientMedicalRecordController::class, 'index'])->name('medical-records');
    Route::post('medical-records/export', [App\Http\Controllers\Patient\PatientMedicalRecordController::class, 'export'])->middleware('throttle:5,1')->name('medical-records.export');
    Route::post('medical-records/documents', [App\Http\Controllers\MedicalRecordDocumentController::class, 'store'])->name('medical-records.documents.store');

    // Onboarding
    Route::post('tour/completed', [App\Http\Controllers\Patient\OnboardingController::class, 'completeTour'])->name('tour.completed');
    Route::post('onboarding/skip-welcome', [App\Http\Controllers\Patient\OnboardingController::class, 'skipWelcome'])->name('onboarding.skip-welcome');
});
