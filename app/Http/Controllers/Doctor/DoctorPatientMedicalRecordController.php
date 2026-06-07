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
use App\Http\Requests\Doctor\MedicalRecords\UpdateClinicalNoteRequest;
use App\Http\Requests\Doctor\MedicalRecords\UpdateMedicalCertificateRequest;
use App\Http\Requests\Doctor\MedicalRecords\UpdatePrescriptionRequest;
use App\Jobs\GenerateMedicalRecordPDF;
use App\Jobs\LogMedicalRecordAccessJob;
use App\Models\Appointments;
use App\Models\ClinicalNote;
use App\Models\MedicalCertificate;
use App\Models\PartnerIntegration;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\FileStorageManager;
use App\Services\MedicalRecordService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $auditKey = "audit:view:doctor:{$user->id}:patient:{$patient->id}";
        if (! Cache::has($auditKey)) {
            LogMedicalRecordAccessJob::dispatch($user, $patient, 'view', [
                'by' => 'doctor',
                'doctor_id' => $user->doctor->id,
            ])->onQueue('low');
            Cache::put($auditKey, true, now()->addMinutes(5));
        }

        $payload['context'] = [
            'viewer' => [
                'id' => $user->doctor->id,
                'name' => $user->name,
            ],
            'mode' => 'doctor',
        ];

        $payload['lab_partners'] = Cache::remember(
            "doctor:{$user->doctor->id}:lab-partners",
            now()->addHour(),
            fn () => $user->doctor->partnerIntegrations()
                ->where('partner_integrations.status', PartnerIntegration::STATUS_ACTIVE)
                ->where('partner_integrations.type', PartnerIntegration::TYPE_LABORATORY)
                ->get(['id', 'name', 'slug'])
                ->toArray()
        );

        $patient->load('user');
        $recentConsultations = Appointments::where('doctor_id', $user->doctor->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn (Appointments $a) => [
                'id' => $a->id,
                'date' => $a->scheduled_at?->format('d/m/Y'),
                'time' => $a->scheduled_at?->format('H:i'),
                'status' => match ($a->status) {
                    Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED => 'Agendada',
                    Appointments::STATUS_COMPLETED => 'Concluída',
                    Appointments::STATUS_NO_SHOW => 'Falta',
                    Appointments::STATUS_IN_PROGRESS => 'Em andamento',
                    Appointments::STATUS_CANCELLED => 'Cancelada',
                    default => 'Agendada',
                },
                'statusClass' => match ($a->status) {
                    Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED => 'bg-primary/20 text-primary',
                    Appointments::STATUS_COMPLETED => 'bg-green-100 text-green-800',
                    Appointments::STATUS_NO_SHOW, Appointments::STATUS_CANCELLED => 'bg-red-100 text-red-800',
                    Appointments::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
                    default => 'bg-gray-100 text-gray-800',
                },
                'notes' => $a->notes,
            ])
            ->values()
            ->all();

        $payload['patient_profile'] = [
            'email' => $patient->user?->email,
            'phone' => $patient->phone_number,
            'cpf' => $patient->cpf ? preg_replace('/(\d{3})\.\d{3}\.\d{3}-(\d{2})/', '$1.***.***-$2', $patient->cpf) : null,
            'emergency_contact' => $patient->emergency_contact
                ? trim($patient->emergency_contact.' - '.($patient->emergency_phone ?? ''), ' -')
                : null,
            'medical_history' => array_values(array_filter(
                array_map('trim', preg_split('/[\r\n;]+/', $patient->medical_history ?? '') ?: []),
                fn ($l) => $l !== ''
            )),
            'recent_consultations' => $recentConsultations,
            'total_consultations_with_doctor' => Appointments::where('doctor_id', $user->doctor->id)
                ->where('patient_id', $patient->id)
                ->where('status', Appointments::STATUS_COMPLETED)
                ->count(),
        ];

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
        $appointment = $this->resolveAppointmentForIssuance($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('createPrescription', $appointment);

        $this->medicalRecordService->issuePrescription($doctor, $patient, $appointment, $request->validated());

        return back()->with('status', 'Prescrição emitida com sucesso.');
    }

    public function storeExamination(StoreExaminationRequest $request, Patient $patient)
    {
        $this->authorize('requestExamination', $patient);

        $doctor = $request->user()->doctor;
        $appointment = $this->resolveAppointmentForIssuance($request->validated('appointment_id'), $patient, $doctor->id);
        $this->authorize('requestExamination', $appointment);

        $this->medicalRecordService->requestExamination($doctor, $patient, $appointment, $request->validated());

        return back()->with('status', 'Exame solicitado com sucesso.');
    }

    public function storeExaminationBatch(Request $request, Patient $patient)
    {
        $this->authorize('requestExamination', $patient);

        $doctor = $request->user()->doctor;

        $validated = $request->validate([
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'examinations' => ['required', 'array', 'min:1', 'max:20'],
            'examinations.*.name' => ['required', 'string', 'max:255'],
            'examinations.*.type' => ['required', Rule::in(['lab', 'image', 'other'])],
            'examinations.*.justification' => ['required', 'string'],
            'examinations.*.instructions' => ['nullable', 'string'],
            'examinations.*.priority' => ['nullable', Rule::in(['normal', 'urgent'])],
        ]);

        $appointment = $this->resolveAppointmentForIssuance($validated['appointment_id'] ?? null, $patient, $doctor->id);
        $this->authorize('requestExamination', $appointment);

        foreach ($validated['examinations'] as $exam) {
            $this->medicalRecordService->requestExamination($doctor, $patient, $appointment, $exam);
        }

        $count = count($validated['examinations']);

        return back()->with('status', $count.' '.($count === 1 ? 'exame solicitado' : 'exames solicitados').' com sucesso.');
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
        $appointment = $this->resolveAppointmentForIssuance($request->validated('appointment_id'), $patient, $doctor->id);
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

    public function updateClinicalNote(UpdateClinicalNoteRequest $request, Patient $patient, ClinicalNote $note)
    {
        if ($note->patient_id !== $patient->id) {
            abort(404);
        }

        if ($note->doctor_id !== $request->user()->doctor?->id) {
            abort(403);
        }

        $this->authorize('updateClinicalRecord', $patient);

        $this->medicalRecordService->updateClinicalNote(
            $request->user()->doctor,
            $patient,
            $note,
            $request->validated(),
        );

        return back()->with('status', 'Anotação atualizada com sucesso.');
    }

    public function updatePrescription(UpdatePrescriptionRequest $request, Patient $patient, Prescription $prescription)
    {
        if ($prescription->patient_id !== $patient->id) {
            abort(404);
        }

        if ($prescription->doctor_id !== $request->user()->doctor?->id) {
            abort(403);
        }

        $this->authorize('updateClinicalRecord', $patient);

        $this->medicalRecordService->updatePrescription(
            $request->user()->doctor,
            $patient,
            $prescription,
            $request->validated(),
        );

        return back()->with('status', 'Prescrição atualizada com sucesso.');
    }

    public function updateMedicalCertificate(UpdateMedicalCertificateRequest $request, Patient $patient, MedicalCertificate $certificate)
    {
        if ($certificate->patient_id !== $patient->id) {
            abort(404);
        }

        if ($certificate->doctor_id !== $request->user()->doctor?->id) {
            abort(403);
        }

        $this->authorize('updateClinicalRecord', $patient);

        $this->medicalRecordService->updateMedicalCertificate(
            $request->user()->doctor,
            $patient,
            $certificate,
            $request->validated(),
        );

        return back()->with('status', 'Atestado atualizado com sucesso.');
    }

    public function showVersionHistory(Request $request, Patient $patient, string $type, string $record): JsonResponse
    {
        $user = $request->user();

        if (! $user?->doctor) {
            abort(403, 'Apenas médicos podem visualizar histórico de versões.');
        }

        $this->authorize('viewVersionHistory', $patient);

        $model = match ($type) {
            'notes' => ClinicalNote::where('patient_id', $patient->id)->findOrFail($record),
            'prescriptions' => Prescription::where('patient_id', $patient->id)->findOrFail($record),
            'certificates' => MedicalCertificate::where('patient_id', $patient->id)->findOrFail($record),
            default => abort(404),
        };

        if ($model->doctor_id !== $user->doctor->id) {
            abort(403, 'Acesso restrito ao médico responsável pelo registro.');
        }

        $this->medicalRecordService->logAccess($user, $patient, 'view_version_history', [
            'by' => 'doctor',
            'doctor_id' => $user->doctor->id,
            'record_type' => $type,
            'record_id' => $record,
        ]);

        $versions = $model->versions()
            ->with('changedBy:id,name')
            ->latest('version_number')
            ->limit(100)
            ->get()
            ->map(fn ($v) => [
                'version_number' => $v->version_number,
                'changed_by' => $v->changedBy?->name ?? 'Sistema',
                'change_reason' => $v->change_reason,
                'changed_fields' => $v->changed_fields,
                'old_values' => $v->old_values,
                'new_values' => $v->new_values,
                'created_at' => $v->created_at?->toIso8601String(),
            ]);

        return response()->json(['versions' => $versions]);
    }

    public function eligibleAppointmentsForDocuments(Request $request, Patient $patient): JsonResponse
    {
        $user = $request->user();

        if (! $user?->doctor) {
            abort(403, 'Apenas médicos podem emitir documentos clínicos.');
        }

        $this->authorize('view', $patient);

        $completedWindowDays = (int) config('telemedicine.medical_records.document_eligible_completed_days', 30);

        $appointments = Appointments::query()
            ->where('doctor_id', $user->doctor->id)
            ->where('patient_id', $patient->id)
            ->where(function ($query) use ($completedWindowDays) {
                $query->where('status', Appointments::STATUS_IN_PROGRESS)
                    ->orWhere(function ($query) {
                        $query->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
                            ->whereDate('scheduled_at', now()->toDateString());
                    })
                    ->orWhere(function ($query) use ($completedWindowDays) {
                        $query->where('status', Appointments::STATUS_COMPLETED)
                            ->where('scheduled_at', '>=', now()->subDays($completedWindowDays));
                    });
            })
            ->orderByDesc('scheduled_at')
            ->get(['id', 'scheduled_at', 'status']);

        return response()->json([
            'appointments' => $appointments->map(fn (Appointments $appointment) => [
                'id' => $appointment->id,
                'scheduled_at' => $appointment->scheduled_at->format('Y-m-d H:i:s'),
                'label' => $appointment->scheduled_at->format('d/m/Y H:i'),
                'status' => $appointment->status,
            ])->values()->all(),
        ]);
    }

    public function eligiblePatientsForDocuments(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user?->doctor) {
            abort(403, 'Apenas médicos podem emitir documentos clínicos.');
        }

        $relationshipDays = (int) config('telemedicine.medical_records.document_eligible_relationship_days', 10);

        $patientIds = Appointments::query()
            ->where('doctor_id', $user->doctor->id)
            ->where($this->eligibleForHubIssuance($relationshipDays))
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::query()
            ->select(['id', 'user_id', 'cpf', 'date_of_birth', 'gender'])
            ->with('user:id,name')
            ->whereIn('id', $patientIds)
            ->get()
            ->map(fn (Patient $patient) => [
                'id' => $patient->id,
                'name' => $patient->user->name ?? '',
                'cpf' => $this->safePatientCpf($patient),
                'age' => $patient->age,
                'sex' => match (strtolower((string) $patient->gender)) {
                    Patient::GENDER_MALE => 'M',
                    Patient::GENDER_FEMALE => 'F',
                    default => null,
                },
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return response()->json([
            'patients' => $patients,
            'relationship_days' => $relationshipDays,
        ]);
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

    private function resolveAppointmentForIssuance(?string $appointmentId, Patient $patient, string $doctorId): Appointments
    {
        if ($appointmentId !== null && $appointmentId !== '') {
            return $this->resolveAppointment($appointmentId, $patient, $doctorId);
        }

        return $this->resolveEligibleAppointment($patient, $doctorId);
    }

    /**
     * Resolução automática (hub emite sem appointment_id). Prioridade:
     * in_progress > agendada/reagendada de hoje > completed mais recente na janela
     * de relacionamento; desempate por scheduled_at desc. 422 se nada elegível.
     */
    private function resolveEligibleAppointment(Patient $patient, string $doctorId): Appointments
    {
        $relationshipDays = (int) config('telemedicine.medical_records.document_eligible_relationship_days', 10);

        $candidates = Appointments::query()
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->where($this->eligibleForHubIssuance($relationshipDays))
            ->orderByDesc('scheduled_at')
            ->get();

        $inProgress = $candidates->where('status', Appointments::STATUS_IN_PROGRESS);
        if ($inProgress->count() > 1) {
            Log::warning('Múltiplas consultas in_progress ao resolver vínculo de documento clínico', [
                'doctor_id' => $doctorId,
                'patient_id' => $patient->id,
                'appointment_ids' => $inProgress->pluck('id')->all(),
            ]);
        }

        [$appointment, $matchRule] = match (true) {
            $inProgress->isNotEmpty() => [$inProgress->first(), 'in_progress'],
            $candidates->contains(fn (Appointments $a) => $a->status !== Appointments::STATUS_COMPLETED) => [
                $candidates->first(fn (Appointments $a) => $a->status !== Appointments::STATUS_COMPLETED),
                'today',
            ],
            $candidates->isNotEmpty() => [$candidates->first(), 'completed'],
            default => [null, null],
        };

        if ($appointment === null) {
            Log::warning('Tentativa de emissão de documento clínico sem consulta elegível', [
                'doctor_id' => $doctorId,
                'patient_id' => $patient->id,
            ]);

            throw ValidationException::withMessages([
                'appointment_id' => 'Nenhuma consulta elegível encontrada para este paciente nos últimos '.$relationshipDays.' dias.',
            ]);
        }

        Log::info('Consulta resolvida automaticamente para emissão de documento clínico', [
            'doctor_id' => $doctorId,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'match_rule' => $matchRule,
        ]);

        return $appointment;
    }

    /**
     * Critério de elegibilidade do hub: consulta ativa agora, agendada/reagendada de hoje
     * ou completed dentro da janela de relacionamento (P1 — não substitui a janela de 30d
     * do vínculo explícito em eligibleAppointmentsForDocuments).
     */
    private function eligibleForHubIssuance(int $relationshipDays): \Closure
    {
        return function ($query) use ($relationshipDays) {
            $query->where('status', Appointments::STATUS_IN_PROGRESS)
                ->orWhere(function ($query) {
                    $query->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED])
                        ->whereDate('scheduled_at', now()->toDateString());
                })
                ->orWhere(function ($query) use ($relationshipDays) {
                    $query->where('status', Appointments::STATUS_COMPLETED)
                        ->where('scheduled_at', '>=', now()->subDays($relationshipDays));
                });
        };
    }

    private function safePatientCpf(Patient $patient): ?string
    {
        try {
            return $patient->cpf;
        } catch (DecryptException) {
            Log::warning('Paciente com CPF inválido para decrypt na listagem de elegíveis para documentos', [
                'patient_id' => $patient->id,
            ]);

            return null;
        }
    }
}
