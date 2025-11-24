<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
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
        $payload = $this->medicalRecordService->getPatientMedicalRecord($patient, $filters);

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
}

