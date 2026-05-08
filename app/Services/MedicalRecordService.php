<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\MedicalCertificate;
use App\Models\MedicalDocument;
use App\Models\MedicalRecordAuditLog;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VitalSign;
use App\Presenters\MedicalRecordPresenter;
use App\Services\Signatures\DigitalSignatureService;
use Illuminate\Support\Collection;

class MedicalRecordService
{
    protected MedicalRecordPresenter $presenter;

    protected MedicalRecordPdfService $pdfService;

    protected MedicalRecordAuditService $auditService;

    protected MedicalRecordAccessService $accessService;

    protected MedicalRecordFilterService $filterService;

    protected MedicalRecordReaderService $readerService;

    protected MedicalRecordClinicalActionService $clinicalActionService;

    public function __construct(
        protected ?DigitalSignatureService $signatureService = null,
        protected ?FileStorageManager $fileStorageManager = null,
        ?MedicalRecordPresenter $presenter = null,
        ?MedicalRecordPdfService $pdfService = null,
        ?MedicalRecordAuditService $auditService = null,
        ?MedicalRecordAccessService $accessService = null,
        ?MedicalRecordFilterService $filterService = null,
        ?MedicalRecordReaderService $readerService = null,
        ?MedicalRecordClinicalActionService $clinicalActionService = null,
    ) {
        $this->presenter = $presenter ?? app(MedicalRecordPresenter::class);
        $this->pdfService = $pdfService ?? new MedicalRecordPdfService(
            $this->fileStorageManager ?? app(FileStorageManager::class),
            $this->presenter,
        );
        $this->auditService = $auditService ?? app(MedicalRecordAuditService::class);
        $this->accessService = $accessService ?? app(MedicalRecordAccessService::class);
        $this->filterService = $filterService ?? app(MedicalRecordFilterService::class);
        $this->readerService = $readerService ?? new MedicalRecordReaderService(
            $this->presenter,
            $this->filterService,
        );
        $this->clinicalActionService = $clinicalActionService ?? new MedicalRecordClinicalActionService(
            $this->auditService,
            $this->pdfService,
            $this->signatureService,
        );
    }

    /**
     * Retorna o payload completo para o prontuário do paciente.
     */
    public function getPatientMedicalRecord(Patient $patient, array $filters = []): array
    {
        return $this->readerService->getPatientMedicalRecord($patient, $filters);
    }

    /**
     * Lista pacientes atendidos pelo médico com indicadores agregados.
     */
    public function getDoctorPatientList(Doctor $doctor, array $filters = []): Collection
    {
        return $this->readerService->getDoctorPatientList($doctor, $filters);
    }

    /**
     * Retorna o prontuário com filtros aplicados ao contexto do médico.
     */
    public function getDoctorPatientMedicalRecord(Doctor $doctor, Patient $patient, array $filters = []): array
    {
        return $this->readerService->getDoctorPatientMedicalRecord($doctor, $patient, $filters);
    }

    /**
     * Busca consultas com filtros.
     */
    public function getAppointmentsForRecord(Patient $patient, array $filters = []): Collection
    {
        return $this->readerService->getAppointmentsForRecord($patient, $filters);
    }

    /**
     * Busca prescrições do paciente.
     */
    public function getPrescriptionsForRecord(Patient $patient, array $filters = []): Collection
    {
        return $this->readerService->getPrescriptionsForRecord($patient, $filters);
    }

    /**
     * Busca exames do paciente.
     */
    public function getExaminationsForRecord(Patient $patient, array $filters = []): Collection
    {
        return $this->readerService->getExaminationsForRecord($patient, $filters);
    }

    /**
     * Busca documentos médicos do paciente.
     */
    public function getDocumentsForRecord(Patient $patient, array $filters = []): Collection
    {
        return $this->readerService->getDocumentsForRecord($patient, $filters);
    }

    /**
     * Busca sinais vitais registrados.
     */
    public function getVitalSignsForRecord(Patient $patient, array $filters = []): Collection
    {
        return $this->readerService->getVitalSignsForRecord($patient, $filters);
    }

    /**
     * Busca diagnósticos registrados para o paciente.
     */
    public function getDiagnosesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        return $this->readerService->getDiagnosesForRecord($patient, $filters, $doctor);
    }

    /**
     * Busca notas clínicas.
     */
    public function getClinicalNotesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        return $this->readerService->getClinicalNotesForRecord($patient, $filters, $doctor);
    }

    /**
     * Busca atestados emitidos.
     */
    public function getMedicalCertificatesForRecord(Patient $patient, array $filters = [], ?Doctor $doctor = null): Collection
    {
        return $this->readerService->getMedicalCertificatesForRecord($patient, $filters, $doctor);
    }

    /**
     * Retorna consultas futuras.
     */
    public function getUpcomingAppointments(Patient $patient): Collection
    {
        return $this->readerService->getUpcomingAppointments($patient);
    }

    /**
     * Prepara dados para exportação em PDF.
     */
    public function prepareDataForExport(Patient $patient, array $filters = []): array
    {
        return $this->readerService->prepareDataForExport($patient, $filters);
    }

    /**
     * Verifica se o médico tem permissão para visualizar o prontuário.
     */
    public function canDoctorViewPatientRecord(Doctor $doctor, Patient $patient): bool
    {
        return $this->accessService->canDoctorViewPatientRecord($doctor, $patient);
    }

    /**
     * Registra auditoria de acesso.
     */
    public function logAccess(User $user, Patient $patient, string $action, array $metadata = []): MedicalRecordAuditLog
    {
        return $this->auditService->logAccess($user, $patient, $action, $metadata);
    }

    /**
     * Normaliza filtros recebidos do controller.
     */
    public function normalizeFilters(array $filters): array
    {
        return $this->readerService->normalizeFilters($filters);
    }

    public function registerDiagnosis(Appointments $appointment, Doctor $doctor, array $payload): Diagnosis
    {
        return $this->clinicalActionService->registerDiagnosis($appointment, $doctor, $payload);
    }

    public function issuePrescription(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): Prescription
    {
        return $this->clinicalActionService->issuePrescription($doctor, $patient, $appointment, $payload);
    }

    public function requestExamination(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): Examination
    {
        return $this->clinicalActionService->requestExamination($doctor, $patient, $appointment, $payload);
    }

    public function createClinicalNote(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): ClinicalNote
    {
        return $this->clinicalActionService->createClinicalNote($doctor, $patient, $appointment, $payload);
    }

    public function issueCertificate(Doctor $doctor, Patient $patient, Appointments $appointment, array $payload): MedicalCertificate
    {
        return $this->clinicalActionService->issueCertificate($doctor, $patient, $appointment, $payload);
    }

    public function registerVitalSigns(Appointments $appointment, Doctor $doctor, array $payload): VitalSign
    {
        return $this->clinicalActionService->registerVitalSigns($appointment, $doctor, $payload);
    }

    public function generateConsultationPdf(Appointments $appointment, User $requester): array
    {
        return $this->pdfService->generateConsultationPdf($appointment, $requester);
    }

    public function generatePdfDocument(
        Patient $patient,
        User $requester,
        array $filters = [],
        string $visibility = MedicalDocument::VISIBILITY_PATIENT,
    ): array {
        $payload = $this->prepareDataForExport($patient, $filters);

        return $this->pdfService->generatePdfDocument($patient, $requester, $payload, $filters, $visibility);
    }

    public function buildMedicalCertificatePdf(MedicalCertificate $certificate): ?array
    {
        return $this->pdfService->buildMedicalCertificatePdf($certificate);
    }
}
