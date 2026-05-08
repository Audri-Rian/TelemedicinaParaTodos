<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\MedicalRecords\DoctorMedicalRecordFilterRequest;
use App\Http\Requests\Doctor\MedicalRecords\GenerateConsultationPdfRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreClinicalNoteRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreDiagnosisRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreExaminationRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreMedicalCertificateRequest;
use App\Http\Requests\Doctor\MedicalRecords\StorePrescriptionRequest;
use App\Http\Requests\Doctor\MedicalRecords\StoreVitalSignRequest;
use App\Jobs\GenerateMedicalRecordPDF;
use App\Models\Appointments;
use App\Models\PartnerIntegration;
use App\Models\Patient;
use App\Services\FileStorageManager;
use App\Services\MedicalRecordService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DoctorPatientMedicalRecordController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
        private readonly FileStorageManager $fileStorageManager,
    ) {}

    public function show(DoctorMedicalRecordFilterRequest $request, Patient $patient): Response
    {
        $user = $request->user();

        if (! $user?->doctor) {
            abort(403, 'Apenas médicos podem visualizar prontuários de pacientes.');
        }

        $this->authorize('view', $patient);

        $filters = $this->extractFilters($request->validated());
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

        $payload['lab_partners'] = Cache::remember(
            "doctor:{$user->doctor->id}:lab-partners",
            now()->addMinutes(5),
            fn () => $user->doctor->partnerIntegrations()
                ->where('partner_integrations.status', PartnerIntegration::STATUS_ACTIVE)
                ->where('partner_integrations.type', PartnerIntegration::TYPE_LABORATORY)
                ->get(['id', 'name', 'slug'])
                ->toArray()
        );

        return Inertia::render('Doctor/PatientMedicalRecord', $payload);
    }

    public function export(DoctorMedicalRecordFilterRequest $request, Patient $patient)
    {
        $user = $request->user();

        if (! $user?->doctor) {
            abort(403, 'Apenas médicos podem exportar prontuários de pacientes.');
        }

        $this->authorize('export', $patient);

        $filters = $this->extractFilters($request->validated());

        $rateLimiterKey = sprintf('medical-record-export:%s:%s', $patient->id, $user->id);
        if (RateLimiter::tooManyAttempts($rateLimiterKey, 1)) {
            return back()->withErrors([
                'export' => 'Você já solicitou uma exportação recentemente. Tente novamente em alguns minutos.',
            ]);
        }

        RateLimiter::hit($rateLimiterKey, 3600);

        $queueConnection = config('telemedicine.medical_records.export_queue_connection', config('queue.default'));
        $queueName = config('telemedicine.medical_records.export_queue_name', 'default');

        try {
            GenerateMedicalRecordPDF::dispatch($patient, $user, $filters, \App\Models\MedicalDocument::VISIBILITY_DOCTOR)
                ->onConnection($queueConnection)
                ->onQueue($queueName);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'export' => 'Não foi possível solicitar a exportação neste momento. Tente novamente.',
            ]);
        }

        $this->medicalRecordService->logAccess($user, $patient, 'export', [
            'by' => 'doctor',
            'doctor_id' => $user->doctor->id,
            'filters' => $filters,
        ]);

        return back()->with(
            'status',
            'Solicitação recebida. Estamos gerando o PDF em segundo plano. O arquivo aparecerá em Histórico de Documentos em instantes.'
        );
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

    public function generateConsultationPdf(GenerateConsultationPdfRequest $request, Patient $patient)
    {
        $this->authorize('generateConsultationPdf', $patient);

        $doctor = $request->user()->doctor;
        $appointmentId = $request->validated('appointment_id');
        $appointment = $this->resolveAppointment($appointmentId, $patient, $doctor->id);
        $this->authorize('generateConsultationPdf', $appointment);

        $document = $this->medicalRecordService->generateConsultationPdf($appointment, $request->user());

        return $this->fileStorageManager
            ->disk($document['disk_domain'])
            ->download($document['path'], $document['filename']);
    }

    private function extractFilters(array $validated): array
    {
        return array_filter([
            'search' => $validated['search'] ?? null,
            'doctor_id' => $validated['doctor_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'appointment_status' => $validated['appointment_status'] ?? null,
            'prescription_status' => $validated['prescription_status'] ?? null,
            'examination_status' => $validated['examination_status'] ?? null,
            'examination_type' => $validated['examination_type'] ?? null,
            'document_category' => $validated['document_category'] ?? null,
            'vitals_limit' => $validated['vitals_limit'] ?? null,
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
