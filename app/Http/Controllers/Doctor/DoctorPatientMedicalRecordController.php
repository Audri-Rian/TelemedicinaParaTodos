<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\MedicalRecords\StoreClinicalNoteRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreDiagnosisRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreExaminationRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreMedicalCertificateRequest;
use App\Http\Requests\Doctor\MedicalRecords\StorePrescriptionRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreVitalSignRequest;
use App\Models\Appointments;
use App\Models\Patient;
use App\Services\MedicalRecordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPatientMedicalRecordController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
    ) {
    }

    public function show(Request $request, Patient $patient): Response
    {
        $user = $request->user();

        if (!$user?->doctor) {
            abort(403, 'Apenas médicos podem visualizar prontuários de pacientes.');
        }

        $this->authorize('view', $patient);

        $filters = $this->extractFilters($request);
        $payload = $this->medicalRecordService->getDoctorPatientMedicalRecord($user->doctor, $patient, $filters);

        $this->medicalRecordService->logAccess($user, $patient, 'view', [
            'by' => 'doctor',
            'doctor_id' => $user->doctor->id,
        ]);

        $payload['context'] = [
            'viewer' => [
                'id' => $user->doctor->id,
                'name' => $user->name,
            ],
            'mode' => 'doctor',
        ];

        return Inertia::render('Doctor/PatientMedicalRecord', $payload);
    }

    public function export(Request $request, Patient $patient)
    {
        $user = $request->user();

        if (!$user?->doctor) {
            abort(403, 'Apenas médicos podem exportar prontuários de pacientes.');
        }

        $this->authorize('export', $patient);

        $filters = $this->extractFilters($request);

        $rateLimiterKey = sprintf('medical-record-export:%s:%s', $patient->id, $user->id);
        if (RateLimiter::tooManyAttempts($rateLimiterKey, 1)) {
            return back()->withErrors([
                'export' => 'Você já solicitou uma exportação recentemente. Tente novamente em alguns minutos.',
            ]);
        }

        RateLimiter::hit($rateLimiterKey, 3600);

        $document = $this->medicalRecordService->generatePdfDocument($patient, $user, $filters);

        $this->medicalRecordService->logAccess($user, $patient, 'export', [
            'by' => 'doctor',
            'doctor_id' => $user->doctor->id,
            'filters' => $filters,
        ]);

        return Storage::disk('public')->download($document['path'], $document['filename']);
    }

    public function storeDiagnosis(StoreDiagnosisRequest $request, Patient $patient)
    {
        $this->authorize('registerDiagnosis', $patient);

        $doctor = $request->user()->doctor;
        $appointment = $this->resolveAppointment($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('registerDiagnosis', $appointment);

        $this->medicalRecordService->registerDiagnosis($appointment, $doctor, $request->validated());

        return back()->with('status', 'Diagnóstico registrado com sucesso.');
    }

    public function storePrescription(StorePrescriptionRequest $request, Patient $patient)
    {
        $this->authorize('issuePrescription', $patient);

        $doctor = $request->user()->doctor;
        $appointment = $this->resolveAppointment($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('createPrescription', $appointment);

        $this->medicalRecordService->issuePrescription($doctor, $patient, $appointment, $request->validated());

        return back()->with('status', 'Prescrição emitida com sucesso.');
    }

    public function storeExamination(StoreExaminationRequest $request, Patient $patient)
    {
        $this->authorize('requestExamination', $patient);

        $doctor = $request->user()->doctor;
        $appointment = $this->resolveAppointment($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('requestExamination', $appointment);

        $this->medicalRecordService->requestExamination($doctor, $patient, $appointment, $request->validated());

        return back()->with('status', 'Exame solicitado com sucesso.');
    }

    public function storeClinicalNote(StoreClinicalNoteRequest $request, Patient $patient)
    {
        $this->authorize('createNote', $patient);

        $doctor = $request->user()->doctor;
        $appointment = $this->resolveAppointment($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('createNote', $appointment);

        $this->medicalRecordService->createClinicalNote($doctor, $patient, $appointment, $request->validated());

        return back()->with('status', 'Anotação registrada com sucesso.');
    }

    public function storeMedicalCertificate(StoreMedicalCertificateRequest $request, Patient $patient)
    {
        $this->authorize('issueCertificate', $patient);

        $doctor = $request->user()->doctor;
        $appointment = $this->resolveAppointment($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('issueCertificate', $appointment);

        $this->medicalRecordService->issueCertificate($doctor, $patient, $appointment, $request->validated());

        return back()->with('status', 'Atestado emitido com sucesso.');
    }

    public function storeVitalSigns(StoreVitalSignRequest $request, Patient $patient)
    {
        $this->authorize('registerVitalSigns', $patient);

        $doctor = $request->user()->doctor;
        $appointment = $this->resolveAppointment($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('registerVitalSigns', $appointment);

        $this->medicalRecordService->registerVitalSigns($appointment, $doctor, $request->validated());

        return back()->with('status', 'Sinais vitais registrados com sucesso.');
    }

    public function generateConsultationPdf(Request $request, Patient $patient)
    {
        $this->authorize('generateConsultationPdf', $patient);

        $doctor = $request->user()->doctor;
        $appointmentId = $request->string('appointment_id');
        $appointment = $this->resolveAppointment($appointmentId, $patient, $doctor->id);
        $this->authorize('generateConsultationPdf', $appointment);

        $document = $this->medicalRecordService->generateConsultationPdf($appointment, $request->user());

        return Storage::disk('public')->download($document['path'], $document['filename']);
    }

    private function extractFilters(Request $request): array
    {
        return array_filter([
            'search' => $request->string('search')->toString(),
            'doctor_id' => $request->string('doctor_id')->toString() ?: null,
            'date_from' => $request->date('date_from'),
            'date_to' => $request->date('date_to'),
            'appointment_status' => $request->input('appointment_status'),
            'prescription_status' => $request->input('prescription_status'),
            'examination_status' => $request->input('examination_status'),
            'examination_type' => $request->input('examination_type'),
            'document_category' => $request->input('document_category'),
            'vitals_limit' => $request->integer('vitals_limit'),
        ], static function ($value) {
            if (is_array($value)) {
                return count($value) > 0;
            }

            return $value !== null && $value !== '';
        });
    }

    private function resolveAppointment(?string $appointmentId, Patient $patient, string $doctorId): Appointments
    {
        return Appointments::query()
            ->where('id', $appointmentId)
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctorId)
            ->firstOrFail();
    }
}

