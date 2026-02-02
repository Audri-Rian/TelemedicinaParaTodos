<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoCall\VideoCallController;

/*
|--------------------------------------------------------------------------
| Rotas do Paciente
|--------------------------------------------------------------------------
|
| Rotas para funcionalidades exclusivas de pacientes.
|
*/

Route::middleware(['auth', 'verified', 'patient'])->prefix('patient')->name('patient.')->group(function () {
    // Dashboard
    Route::get('dashboard', [App\Http\Controllers\Patient\PatientDashboardController::class, 'index'])->name('dashboard');

    // Busca e agendamento de consultas
    Route::get('search-consultations', [App\Http\Controllers\Patient\PatientSearchConsultationsController::class, 'index'])->name('search-consultations');
    Route::get('schedule-consultation', [App\Http\Controllers\Patient\ScheduleConsultationController::class, 'index'])->name('schedule-consultation');
    Route::get('doctor-perfil', [App\Http\Controllers\Patient\DoctorPerfilController::class, 'index'])->name('doctor-perfil');

    // Mensagens
    Route::get('messages', [App\Http\Controllers\Patient\PatientMessagesController::class, 'index'])->name('messages');

    // Videochamada
    Route::get('video-call', [App\Http\Controllers\Patient\PatientVideoCallController::class, 'index'])->name('video-call');
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->middleware('throttle:20,1')->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->middleware('throttle:30,1')->name('video-call.request-status');

    // Histórico de consultas
    Route::get('history-consultations', [App\Http\Controllers\Patient\PatientHistoryConsultationsController::class, 'index'])->name('history-consultations');
    Route::get('consultation-details/{appointment}', [App\Http\Controllers\Patient\PatientConsultationDetailsController::class, 'show'])->name('consultation-details');
    Route::get('next-consultation', [App\Http\Controllers\Patient\PatientNextConsultationController::class, 'index'])->name('next-consultation');

    // Prontuário médico
    Route::get('medical-records', [App\Http\Controllers\Patient\PatientMedicalRecordController::class, 'index'])->name('medical-records');
    Route::post('medical-records/export', [App\Http\Controllers\Patient\PatientMedicalRecordController::class, 'export'])->middleware('throttle:5,1')->name('medical-records.export');
    Route::post('medical-records/documents', [App\Http\Controllers\MedicalRecordDocumentController::class, 'store'])->name('medical-records.documents.store');

    // Onboarding
    Route::post('tour/completed', [App\Http\Controllers\Patient\OnboardingController::class, 'completeTour'])->name('tour.completed');
    Route::post('onboarding/skip-welcome', [App\Http\Controllers\Patient\OnboardingController::class, 'skipWelcome'])->name('onboarding.skip-welcome');
});
