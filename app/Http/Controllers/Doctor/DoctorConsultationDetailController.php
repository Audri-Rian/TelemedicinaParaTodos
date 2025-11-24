<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Services\MedicalRecordService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DoctorConsultationDetailController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
    ) {
    }

    public function show(Request $request, Appointments $appointment)
    {
        $user = $request->user();

        if (!$user?->doctor) {
            abort(403, 'Apenas médicos podem acessar consultas.');
        }

        // Verificar se o médico é responsável pela consulta
        if ($appointment->doctor_id !== $user->doctor->id) {
            abort(403, 'Você não tem permissão para acessar esta consulta.');
        }

        $appointment->load([
            'patient.user',
            'doctor.user',
            'doctor.specializations',
            'prescriptions',
            'examinations',
            'diagnoses',
            'clinicalNotes',
            'vitalSigns',
            'medicalDocuments',
        ]);

        $patient = $appointment->patient;
        $isInProgress = $appointment->status === Appointments::STATUS_IN_PROGRESS;
        $isCompleted = $appointment->status === Appointments::STATUS_COMPLETED;

        // Calcular tempo decorrido se em andamento
        $elapsedTime = null;
        if ($isInProgress && $appointment->started_at) {
            $elapsedTime = $appointment->started_at->diffInMinutes(now());
        }

        // Dados do prontuário resumido para sidebar
        $patientSummary = [
            'id' => $patient->id,
            'name' => $patient->user->name,
            'age' => $patient->age,
            'gender' => $patient->gender,
            'blood_type' => $patient->blood_type,
            'allergies' => $patient->allergies ? explode(',', $patient->allergies) : [],
            'current_medications' => $patient->current_medications,
            'medical_history' => $patient->medical_history,
            'height' => $patient->height,
            'weight' => $patient->weight,
            'bmi' => $patient->bmi,
        ];

        // Últimas 3 consultas para histórico
        $recentConsultations = Appointments::where('patient_id', $patient->id)
            ->where('doctor_id', $user->doctor->id)
            ->where('id', '!=', $appointment->id)
            ->where('status', Appointments::STATUS_COMPLETED)
            ->orderByDesc('scheduled_at')
            ->limit(3)
            ->get()
            ->map(fn (Appointments $apt) => [
                'id' => $apt->id,
                'date' => $apt->scheduled_at->format('d/m/Y'),
                'diagnosis' => $apt->metadata['diagnosis'] ?? null,
                'cid10' => $apt->metadata['cid10'] ?? null,
            ]);

        // Dados da consulta atual
        $metadata = $appointment->metadata ?? [];
        $consultationData = [
            'id' => $appointment->id,
            'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
            'started_at' => $appointment->started_at?->toIso8601String(),
            'ended_at' => $appointment->ended_at?->toIso8601String(),
            'status' => $appointment->status,
            'notes' => $appointment->notes,
            'metadata' => $metadata,
            // Dados clínicos da consulta
            'chief_complaint' => $metadata['chief_complaint'] ?? '',
            'anamnesis' => $metadata['anamnesis'] ?? '',
            'physical_exam' => $metadata['physical_exam'] ?? '',
            'diagnosis' => $metadata['diagnosis'] ?? '',
            'cid10' => $metadata['cid10'] ?? '',
            'instructions' => $metadata['instructions'] ?? '',
            // Relacionamentos
            'prescriptions' => $appointment->prescriptions->map(fn ($p) => [
                'id' => $p->id,
                'medications' => $p->medications,
                'instructions' => $p->instructions,
                'valid_until' => $p->valid_until?->toDateString(),
                'status' => $p->status,
            ]),
            'examinations' => $appointment->examinations->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'type' => $e->type,
                'status' => $e->status,
                'priority' => $e->metadata['priority'] ?? 'normal',
            ]),
            'diagnoses' => $appointment->diagnoses->map(fn ($d) => [
                'id' => $d->id,
                'cid10_code' => $d->cid10_code,
                'cid10_description' => $d->cid10_description,
                'type' => $d->type,
                'description' => $d->description,
            ]),
            'vital_signs' => $appointment->vitalSigns->map(fn ($v) => [
                'id' => $v->id,
                'blood_pressure_systolic' => $v->blood_pressure_systolic,
                'blood_pressure_diastolic' => $v->blood_pressure_diastolic,
                'temperature' => $v->temperature,
                'heart_rate' => $v->heart_rate,
                'respiratory_rate' => $v->respiratory_rate,
                'oxygen_saturation' => $v->oxygen_saturation,
                'weight' => $v->weight,
                'height' => $v->height,
                'recorded_at' => $v->recorded_at?->toIso8601String(),
            ]),
            'clinical_notes' => $appointment->clinicalNotes->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'is_private' => $n->is_private,
                'category' => $n->category,
            ]),
        ];

        // Se for requisição AJAX explícita (não do Inertia), retornar JSON
        // O Inertia sempre envia o header X-Inertia, então verificamos se NÃO é Inertia
        $isInertiaRequest = $request->header('X-Inertia') !== null || $request->header('X-Inertia-Version') !== null;
        
        // Só retornar JSON se for AJAX explícito E não for Inertia E quiser JSON
        if (!$isInertiaRequest && $request->ajax() && $request->wantsJson()) {
            return response()->json([
                'appointment' => $consultationData,
                'patient' => $patientSummary,
                'isCompleted' => $isCompleted,
            ]);
        }

        return Inertia::render('Doctor/ConsultationDetail', [
            'appointment' => $consultationData,
            'patient' => $patientSummary,
            'recent_consultations' => $recentConsultations,
            'mode' => $isInProgress ? 'in_progress' : ($isCompleted ? 'completed' : 'scheduled'),
            'elapsed_time' => $elapsedTime,
            'can_edit' => true, // Sempre permitir edição
            'can_complement' => true, // Sempre permitir complementação
        ]);
    }

    public function start(Request $request, Appointments $appointment)
    {
        $user = $request->user();

        if ($appointment->doctor_id !== $user->doctor->id) {
            abort(403);
        }

        if ($appointment->status !== Appointments::STATUS_SCHEDULED && 
            $appointment->status !== Appointments::STATUS_RESCHEDULED) {
            return back()->withErrors(['status' => 'Apenas consultas agendadas podem ser iniciadas.']);
        }

        $appointment->update([
            'status' => Appointments::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $appointment->logEvent('consultation_started', [
            'started_by' => $user->id,
            'started_at' => now()->toIso8601String(),
        ], $user->id);

        return redirect()->route('doctor.consultations.detail', $appointment);
    }

    public function saveDraft(Request $request, Appointments $appointment)
    {
        $user = $request->user();

        if ($appointment->doctor_id !== $user->doctor->id) {
            abort(403);
        }

        // Permitir edição mesmo quando consulta está finalizada

        $validated = $request->validate([
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            'anamnesis' => ['nullable', 'string', 'max:5000'],
            'physical_exam' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:500'],
            'cid10' => ['nullable', 'string', 'max:10'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $metadata = $appointment->metadata ?? [];
        $metadata = array_merge($metadata, $validated);

        $appointment->update([
            'metadata' => $metadata,
            'notes' => $request->input('notes'),
        ]);

        $appointment->logEvent('draft_saved', [
            'saved_by' => $user->id,
            'saved_at' => now()->toIso8601String(),
        ], $user->id);

        // Se for requisição AJAX/JSON, retornar JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rascunho salvo com sucesso.',
                'saved_at' => now()->toIso8601String(),
            ]);
        }

        // Caso contrário, redirecionar de volta
        return back()->with('success', 'Rascunho salvo com sucesso.');
    }

    public function finalize(Request $request, Appointments $appointment)
    {
        $user = $request->user();

        if ($appointment->doctor_id !== $user->doctor->id) {
            abort(403);
        }

        if ($appointment->status !== Appointments::STATUS_IN_PROGRESS) {
            return back()->withErrors(['status' => 'Apenas consultas em andamento podem ser finalizadas.']);
        }

        // Validação de campos essenciais
        $metadata = $appointment->metadata ?? [];
        $errors = [];

        if (empty($metadata['chief_complaint'])) {
            $errors['chief_complaint'] = 'Queixa principal é obrigatória.';
        }

        if (empty($metadata['diagnosis']) && $appointment->diagnoses->isEmpty()) {
            $errors['diagnosis'] = 'Diagnóstico é obrigatório.';
        }

        if (!empty($errors)) {
            return back()->withErrors($errors);
        }

        $appointment->update([
            'status' => Appointments::STATUS_COMPLETED,
            'ended_at' => now(),
        ]);

        $appointment->logEvent('consultation_completed', [
            'completed_by' => $user->id,
            'completed_at' => now()->toIso8601String(),
            'duration_minutes' => $appointment->started_at ? $appointment->started_at->diffInMinutes(now()) : null,
        ], $user->id);

        // Log de auditoria no prontuário
        $this->medicalRecordService->logAccess(
            $user,
            $appointment->patient,
            'consultation_completed',
            [
                'appointment_id' => $appointment->id,
                'doctor_id' => $user->doctor->id,
            ]
        );

        return redirect()->route('doctor.consultations.detail', $appointment)
            ->with('status', 'Consulta finalizada com sucesso.');
    }

    public function complement(Request $request, Appointments $appointment)
    {
        $user = $request->user();

        if ($appointment->doctor_id !== $user->doctor->id) {
            abort(403);
        }

        if ($appointment->status !== Appointments::STATUS_COMPLETED) {
            return back()->withErrors(['status' => 'Apenas consultas finalizadas podem ser complementadas.']);
        }

        // Permitir apenas complementação de campos não críticos
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'complementary_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $metadata = $appointment->metadata ?? [];
        if (!empty($validated['complementary_notes'])) {
            $metadata['complementary_notes'] = $validated['complementary_notes'];
            $metadata['complementary_notes_added_at'] = now()->toIso8601String();
            $metadata['complementary_notes_added_by'] = $user->id;
        }

        $appointment->update([
            'metadata' => $metadata,
            'notes' => $validated['notes'] ?? $appointment->notes,
        ]);

        $appointment->logEvent('consultation_complemented', [
            'complemented_by' => $user->id,
            'complemented_at' => now()->toIso8601String(),
        ], $user->id);

        return back()->with('status', 'Complementação salva com sucesso.');
    }

    public function generatePdf(Request $request, Appointments $appointment)
    {
        $user = $request->user();

        if ($appointment->doctor_id !== $user->doctor->id) {
            abort(403);
        }

        $result = $this->medicalRecordService->generateConsultationPdf($appointment, $user);

        return response()->download(
            storage_path("app/public/{$result['path']}"),
            $result['filename']
        );
    }
}

