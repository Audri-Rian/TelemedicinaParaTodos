<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\ConsultationsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\VideoCall\VideoCallController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return Inertia::render('index');
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
    Route::get('availability', [App\Http\Controllers\Doctor\DoctorAvailabilityController::class, 'index'])->name('availability');
    Route::get('consultations', [App\Http\Controllers\Doctor\DoctorConsultationsController::class, 'index'])->name('consultations');
    Route::get('consultations/{appointment}', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'show'])->name('consultations.detail');
    Route::post('consultations/{appointment}/start', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'start'])->name('consultations.detail.start');
    Route::post('consultations/{appointment}/save-draft', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'saveDraft'])->name('consultations.detail.save-draft');
    Route::post('consultations/{appointment}/finalize', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'finalize'])->name('consultations.detail.finalize');
    Route::post('consultations/{appointment}/complement', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'complement'])->name('consultations.detail.complement');
    Route::get('consultations/{appointment}/pdf', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'generatePdf'])->name('consultations.detail.pdf');
    Route::get('messages', [App\Http\Controllers\Doctor\DoctorMessagesController::class, 'index'])->name('messages');
    Route::get('history', [App\Http\Controllers\Doctor\DoctorHistoryController::class, 'index'])->name('history');
    Route::get('patients', [App\Http\Controllers\Doctor\DoctorPatientsController::class, 'index'])->name('patients');
    Route::get('documents', [App\Http\Controllers\Doctor\DoctorDocumentsController::class, 'index'])->name('documents');
    Route::get('patient/{id}', [App\Http\Controllers\Doctor\PatientDetailsController::class, 'show'])->name('patient.details');
    Route::get('patients/{patient}/medical-record', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'show'])->name('patients.medical-record');
    Route::post('patients/{patient}/medical-record/export', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'export'])->name('patients.medical-record.export');
    Route::post('patients/{patient}/medical-record/documents', [App\Http\Controllers\MedicalRecordDocumentController::class, 'storeForPatient'])->name('patients.medical-record.documents.store');
    Route::post('patients/{patient}/medical-record/diagnoses', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeDiagnosis'])->name('patients.medical-record.diagnoses.store');
    Route::post('patients/{patient}/medical-record/prescriptions', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storePrescription'])->name('patients.medical-record.prescriptions.store');
    Route::post('patients/{patient}/medical-record/examinations', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeExamination'])->name('patients.medical-record.examinations.store');
    Route::post('patients/{patient}/medical-record/notes', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeClinicalNote'])->name('patients.medical-record.notes.store');
    Route::post('patients/{patient}/medical-record/certificates', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeMedicalCertificate'])->name('patients.medical-record.certificates.store');
    Route::post('patients/{patient}/medical-record/vital-signs', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeVitalSigns'])->name('patients.medical-record.vital-signs.store');
    Route::post('patients/{patient}/medical-record/consultations/pdf', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'generateConsultationPdf'])->name('patients.medical-record.consultations.pdf');
    
    // Rotas para videoconferência (médicos)
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->name('video-call.request-status');
    
    // Rotas para configuração de agenda (médicos)
    Route::get('schedule', function () {
        return redirect()->route('doctor.schedule.show', ['doctor' => auth()->user()->doctor->id]);
    })->name('schedule');
    Route::get('doctors/{doctor}/schedule', [App\Http\Controllers\Doctor\DoctorScheduleController::class, 'show'])->name('schedule.show');
    Route::post('doctors/{doctor}/schedule/save', [App\Http\Controllers\Doctor\DoctorScheduleController::class, 'save'])->name('schedule.save');
    
    // Rotas para locais de atendimento (médicos)
    Route::post('doctors/{doctor}/locations', [App\Http\Controllers\Doctor\DoctorServiceLocationController::class, 'store'])->name('locations.store');
    Route::put('doctors/{doctor}/locations/{location}', [App\Http\Controllers\Doctor\DoctorServiceLocationController::class, 'update'])->name('locations.update');
    Route::delete('doctors/{doctor}/locations/{location}', [App\Http\Controllers\Doctor\DoctorServiceLocationController::class, 'destroy'])->name('locations.destroy');
    
    // Rotas para slots de disponibilidade (médicos)
    Route::post('doctors/{doctor}/availability', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'store'])->name('availability.store');
    Route::put('doctors/{doctor}/availability/{slot}', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'update'])->name('availability.update');
    Route::delete('doctors/{doctor}/availability/{slot}', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'destroy'])->name('availability.destroy');
    
    // Rotas para datas bloqueadas (médicos)
    Route::post('doctors/{doctor}/blocked-dates', [App\Http\Controllers\Doctor\DoctorBlockedDateController::class, 'store'])->name('blocked-dates.store');
    Route::delete('doctors/{doctor}/blocked-dates/{blockedDate}', [App\Http\Controllers\Doctor\DoctorBlockedDateController::class, 'destroy'])->name('blocked-dates.destroy');
});

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
    Route::post('medical-records/export', [App\Http\Controllers\Patient\PatientMedicalRecordController::class, 'export'])->name('medical-records.export');
    Route::post('medical-records/documents', [App\Http\Controllers\MedicalRecordDocumentController::class, 'store'])->name('medical-records.documents.store');
    
    // Rotas para videoconferência (pacientes)
    Route::post('video-call/request/{user}', [VideoCallController::class, 'requestVideoCall'])->name('video-call.request');
    Route::post('video-call/request/status/{user}', [VideoCallController::class, 'requestVideoCallStatus'])->name('video-call.request-status');
});

// Rotas compartilhadas (ambos os tipos de usuário)
Route::middleware(['auth', 'verified'])->group(function () {
    // Rotas para especializações
    Route::resource('specializations', SpecializationController::class);
    
    // Rotas para appointments
    Route::resource('appointments', AppointmentsController::class);
    
    // Rotas customizadas para appointments
    Route::post('appointments/{appointment}/start', [AppointmentsController::class, 'start'])->name('appointments.start');
    Route::post('appointments/{appointment}/end', [AppointmentsController::class, 'end'])->name('appointments.end');
    Route::post('appointments/{appointment}/cancel', [AppointmentsController::class, 'cancel'])->name('appointments.cancel');
    Route::post('appointments/{appointment}/reschedule', [AppointmentsController::class, 'reschedule'])->name('appointments.reschedule');

    Route::get('api/appointments/availability', [AppointmentsController::class, 'availability'])->name('appointments.availability');
    
    // Rotas para timeline events (educação, cursos, certificados, projetos)
    Route::prefix('api')->group(function () {
        Route::get('timeline-events', [App\Http\Controllers\TimelineEventController::class, 'index'])->name('api.timeline-events.index');
        Route::post('timeline-events', [App\Http\Controllers\TimelineEventController::class, 'store'])->name('api.timeline-events.store');
        Route::get('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'show'])->name('api.timeline-events.show');
        Route::put('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'update'])->name('api.timeline-events.update');
        Route::delete('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'destroy'])->name('api.timeline-events.destroy');
    });
});

// Rotas públicas para API de especializações
Route::prefix('api')->group(function () {
    Route::get('specializations/list', [SpecializationController::class, 'list'])->name('api.specializations.list');
    Route::get('specializations/options', [SpecializationController::class, 'options'])->name('api.specializations.options');
    
    // Rotas públicas para disponibilidade de médicos (pacientes podem consultar)
    Route::get('doctors/{doctor}/availability/{date}', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'getByDate'])->name('api.doctors.availability.date');
});


Route::get('font-test', function () {
    return Inertia::render('FontTest');
})->name('font-test');

// Rotas públicas para Termos de Serviço e Política de Privacidade
Route::get('terms', [TermsOfServiceController::class, 'index'])->name('terms');
Route::get('privacy', [PrivacyPolicyController::class, 'index'])->name('privacy');

// Rota para servir arquivos do storage (avatars)
Route::get('storage/avatars/{userId}/{filename}', function ($userId, $filename) {
    $path = "avatars/{$userId}/{$filename}";
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    
    if (!$disk->exists($path)) {
        abort(404);
    }
    
    $file = $disk->get($path);
    $mimeType = $disk->mimeType($path);
    
    return response($file, 200)
        ->header('Content-Type', $mimeType)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where(['userId' => '[^/]+', 'filename' => '[^/]+'])->name('storage.avatar');

// Rotas de desenvolvimento (apenas local/dev)
Route::middleware([\App\Http\Middleware\EnsureDevelopmentEnvironment::class])->prefix('dev')->group(function () {
    Route::get('video-test', [App\Http\Controllers\Dev\VideoTestController::class, 'index'])->name('dev.video-test');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
