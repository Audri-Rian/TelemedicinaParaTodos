<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
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

// Rota para capturar /dashboard e redirecionar baseado no papel do usuário
Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    $user = auth()->user();
    
    if ($user->isDoctor()) {
        return redirect()->route('doctor.dashboard');
    }
    
    if ($user->isPatient()) {
        return redirect()->route('patient.dashboard');
    }
    
    return redirect()->route('home');
})->name('dashboard');

// Rotas para Médicos
Route::middleware(['auth', 'verified', 'doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Doctor\DoctorDashboardController::class, 'index'])->name('dashboard');
    Route::get('appointments', [App\Http\Controllers\Doctor\DoctorAppointmentsController::class, 'index'])->name('appointments');
    Route::get('consultations', [App\Http\Controllers\Doctor\DoctorConsultationsController::class, 'index'])->name('consultations');
    Route::get('messages', [App\Http\Controllers\Doctor\DoctorMessagesController::class, 'index'])->name('messages');
    Route::get('history', [App\Http\Controllers\Doctor\DoctorHistoryController::class, 'index'])->name('history');
    Route::get('patients', [App\Http\Controllers\Doctor\DoctorPatientsController::class, 'index'])->name('patients');
    Route::get('documents', [App\Http\Controllers\Doctor\DoctorDocumentsController::class, 'index'])->name('documents');
    Route::get('patient/{id}', [App\Http\Controllers\Doctor\PatientDetailsController::class, 'show'])->name('patient.details');
    
    // Rotas para videoconferência (médicos)
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->name('video-call.request-status');
});

// Rotas para Pacientes
Route::middleware(['auth', 'verified', 'patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Patient\PatientDashboardController::class, 'index'])->name('dashboard');
    Route::get('search-consultations', [App\Http\Controllers\Patient\PatientSearchConsultationsController::class, 'index'])->name('search-consultations');
    Route::get('schedule-consultation', [App\Http\Controllers\Patient\ScheduleConsultationController::class, 'index'])->name('schedule-consultation');
    Route::get('doctor-perfil', [App\Http\Controllers\Patient\DoctorPerfilController::class, 'index'])->name('doctor-perfil');
    Route::get('messages', [App\Http\Controllers\Patient\PatientMessagesController::class, 'index'])->name('messages');
    Route::get('video-call', [App\Http\Controllers\Patient\PatientVideoCallController::class, 'index'])->name('video-call');
    Route::get('health-records', [App\Http\Controllers\Patient\PatientHealthRecordsController::class, 'index'])->name('health-records');
    
    // Rotas para videoconferência (pacientes)
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->name('video-call.request-status');
});

// Rotas compartilhadas (ambos os tipos de usuário)
Route::middleware(['auth', 'verified'])->group(function () {
    // Rotas para especializações
    Route::resource('specializations', SpecializationController::class);
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
