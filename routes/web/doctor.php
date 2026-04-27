<?php

use Illuminate\Support\Facades\Route;

// Rotas para Médicos
Route::middleware(['auth', 'verified', 'doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Doctor\DoctorDashboardController::class, 'index'])->name('dashboard');
    Route::get('appointments', [App\Http\Controllers\Doctor\DoctorAppointmentsController::class, 'index'])->name('appointments');
    Route::get('availability', [App\Http\Controllers\Doctor\DoctorAvailabilityController::class, 'index'])->name('availability');
    Route::get('consultations', [App\Http\Controllers\Doctor\DoctorConsultationsController::class, 'index'])->name('consultations');
    Route::get('consultations/{appointment}', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'show'])->name('consultations.detail');
    Route::post('consultations/{appointment}/start', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'start'])->middleware('throttle:10,1')->name('consultations.detail.start');
    Route::post('consultations/{appointment}/save-draft', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'saveDraft'])->middleware('throttle:30,1')->name('consultations.detail.save-draft');
    Route::post('consultations/{appointment}/finalize', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'finalize'])->middleware('throttle:10,1')->name('consultations.detail.finalize');
    Route::post('consultations/{appointment}/complement', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'complement'])->name('consultations.detail.complement');
    Route::get('consultations/{appointment}/pdf', [App\Http\Controllers\Doctor\DoctorConsultationDetailController::class, 'generatePdf'])->name('consultations.detail.pdf');
    Route::get('messages', [App\Http\Controllers\Doctor\DoctorMessagesController::class, 'index'])->name('messages');
    Route::get('history', [App\Http\Controllers\Doctor\DoctorHistoryController::class, 'index'])->name('history');
    Route::get('patients', [App\Http\Controllers\Doctor\DoctorPatientsController::class, 'index'])->name('patients');
    Route::get('documents', [App\Http\Controllers\Doctor\DoctorDocumentsController::class, 'index'])->name('documents');
    Route::get('documents/{document}/download', [App\Http\Controllers\Doctor\DoctorDocumentsController::class, 'download'])
        ->middleware('signed')
        ->name('documents.download');

    // Integrações (index antes do wildcard {partner})
    Route::get('integrations', [App\Http\Controllers\Doctor\DoctorIntegrationsController::class, 'index'])->name('integrations');
    Route::get('integrations/partners', [App\Http\Controllers\Doctor\DoctorIntegrationsController::class, 'partners'])->name('integrations.partners');
    Route::get('integrations/connect', [App\Http\Controllers\Doctor\DoctorIntegrationsController::class, 'connect'])->name('integrations.connect');
    Route::post('integrations/connect', [App\Http\Controllers\Doctor\DoctorIntegrationsController::class, 'store'])->middleware('throttle:10,1')->name('integrations.store');
    Route::get('integrations/metrics', [App\Integrations\Http\Controllers\IntegrationMetricsController::class, 'index'])->name('integrations.metrics');
    Route::get('integrations/{partner}', [App\Http\Controllers\Doctor\DoctorIntegrationsController::class, 'show'])->name('integrations.show');
    Route::post('integrations/{partner}/sync', [App\Http\Controllers\Doctor\DoctorIntegrationsController::class, 'sync'])->middleware('throttle:10,1')->name('integrations.sync');

    // Detalhes do paciente e prontuário
    Route::get('patients/{patient}/details', [App\Http\Controllers\Doctor\PatientDetailsController::class, 'show'])->name('patient.details');
    Route::get('patients/{patient}/medical-record', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'show'])->name('patients.medical-record');
    Route::post('patients/{patient}/medical-record/export', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'export'])->middleware('throttle:5,1')->name('patients.medical-record.export');
    Route::post('patients/{patient}/medical-record/documents', [App\Http\Controllers\MedicalRecordDocumentController::class, 'storeForPatient'])->name('patients.medical-record.documents.store');
    Route::post('patients/{patient}/medical-record/diagnoses', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeDiagnosis'])->name('patients.medical-record.diagnoses.store');
    Route::post('patients/{patient}/medical-record/prescriptions', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storePrescription'])->name('patients.medical-record.prescriptions.store');
    Route::post('patients/{patient}/medical-record/examinations', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeExamination'])->name('patients.medical-record.examinations.store');
    Route::post('patients/{patient}/medical-record/notes', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeClinicalNote'])->name('patients.medical-record.notes.store');
    Route::post('patients/{patient}/medical-record/certificates', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeMedicalCertificate'])->name('patients.medical-record.certificates.store');
    Route::post('patients/{patient}/medical-record/vital-signs', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'storeVitalSigns'])->name('patients.medical-record.vital-signs.store');
    Route::post('patients/{patient}/medical-record/consultations/pdf', [App\Http\Controllers\Doctor\DoctorPatientMedicalRecordController::class, 'generateConsultationPdf'])->name('patients.medical-record.consultations.pdf');

    // Agenda do médico
    Route::get('schedule', [App\Http\Controllers\Doctor\DoctorScheduleController::class, 'redirect'])->name('schedule');
    Route::get('doctors/{doctor}/schedule', [App\Http\Controllers\Doctor\DoctorScheduleController::class, 'show'])->name('schedule.show');
    Route::post('doctors/{doctor}/schedule/save', [App\Http\Controllers\Doctor\DoctorScheduleController::class, 'save'])->name('schedule.save');

    // Locais de atendimento
    Route::post('doctors/{doctor}/locations', [App\Http\Controllers\Doctor\DoctorServiceLocationController::class, 'store'])->name('locations.store');
    Route::put('doctors/{doctor}/locations/{location}', [App\Http\Controllers\Doctor\DoctorServiceLocationController::class, 'update'])->name('locations.update');
    Route::delete('doctors/{doctor}/locations/{location}', [App\Http\Controllers\Doctor\DoctorServiceLocationController::class, 'destroy'])->name('locations.destroy');

    // Slots de disponibilidade
    Route::post('doctors/{doctor}/availability', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'store'])->name('availability.store');
    Route::put('doctors/{doctor}/availability/{slot}', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'update'])->name('availability.update');
    Route::delete('doctors/{doctor}/availability/{slot}', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'destroy'])->name('availability.destroy');

    // Datas bloqueadas
    Route::post('doctors/{doctor}/blocked-dates', [App\Http\Controllers\Doctor\DoctorBlockedDateController::class, 'store'])->name('blocked-dates.store');
    Route::delete('doctors/{doctor}/blocked-dates/{blockedDate}', [App\Http\Controllers\Doctor\DoctorBlockedDateController::class, 'destroy'])->name('blocked-dates.destroy');

    // Onboarding (controller compartilhado entre doctor e patient)
    Route::post('tour/completed', [App\Http\Controllers\Patient\OnboardingController::class, 'completeTour'])->name('tour.completed');
    Route::post('onboarding/skip-welcome', [App\Http\Controllers\Patient\OnboardingController::class, 'skipWelcome'])->name('onboarding.skip-welcome');
});
